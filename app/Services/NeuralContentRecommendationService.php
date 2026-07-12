<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Order;
use App\Models\RecommendationFeatureSnapshot;
use App\Models\User;
use Illuminate\Support\Collection;

class NeuralContentRecommendationService
{
    private const TEXT_VECTOR_SIZE = 12;
    private const DISTANCE_CLAMP_METERS = 100000.0;

    private const CATEGORIES = [
        'musik',
        'seni',
        'olahraga',
        'kuliner',
        'teknologi',
        'lainnya',
    ];

    private const STOPWORDS = [
        'acara',
        'akan',
        'antar',
        'atau',
        'bagi',
        'bersama',
        'dan',
        'dengan',
        'dari',
        'dalam',
        'event',
        'ini',
        'untuk',
        'yang',
    ];

    public function __construct(
        private readonly RecommendationFeatureSnapshotService $snapshotService,
        private readonly H5NcbfRecommendationService $h5ModelService
    ) {
    }

    public function recommendForUser(User $user, int $limit = 3): Collection
    {
        $historySnapshots = $this->getPurchasedSnapshots($user);

        if ($historySnapshots->isEmpty() && $this->userHasInterestOrders($user)) {
            $this->snapshotService->syncPurchasedTicketsForUser($user);
            $historySnapshots = $this->getPurchasedSnapshots($user);
        }

        $purchasedEventIds = $historySnapshots
            ->pluck('event_id')
            ->unique()
            ->values();

        $candidates = Event::query()
            ->where('status', 'approved')
            ->notEnded()
            ->when($purchasedEventIds->isNotEmpty(), function ($query) use ($purchasedEventIds) {
                $query->whereNotIn('id', $purchasedEventIds->all());
            })
            ->get();

        if ($candidates->isEmpty()) {
            return collect();
        }

        if ($historySnapshots->isEmpty()) {
            return $this->coldStartRecommendations($user, $candidates, $limit);
        }

        $maxPrice = max(
            1.0,
            (float) $historySnapshots->max('event_price'),
            (float) $candidates->max('price')
        );
        $userEmbedding = $this->buildUserEmbedding($historySnapshots, $maxPrice);
        $h5Scores = $this->h5ModelService->scoreCandidates($user, $historySnapshots, $candidates, $maxPrice);

        if ($h5Scores !== null) {
            return $candidates
                ->map(function (Event $event) use ($user, $maxPrice, $h5Scores) {
                    $features = $this->eventFeatures(
                        $event,
                        $maxPrice,
                        $this->snapshotService->distanceFromUser($user, $event)
                    );

                    return $this->formatRecommendation(
                        $event,
                        $h5Scores[$event->id] ?? 0.0,
                        $features,
                        'NCBF .h5'
                    );
                })
                ->sortByDesc('score')
                ->take($limit)
                ->values();
        }

        return $candidates
            ->map(function (Event $event) use ($user, $userEmbedding, $maxPrice) {
                $features = $this->eventFeatures(
                    $event,
                    $maxPrice,
                    $this->snapshotService->distanceFromUser($user, $event)
                );
                $embedding = $this->neuralEncode($features['vector']);
                $score = $this->cosineSimilarity($userEmbedding, $embedding);

                return $this->formatRecommendation($event, $score, $features);
            })
            ->sortByDesc('score')
            ->take($limit)
            ->values();
    }

    private function getPurchasedSnapshots(User $user): Collection
    {
        return RecommendationFeatureSnapshot::query()
            ->where('user_id', $user->id)
            ->where('interaction_type', 'purchased')
            ->where('label', 1)
            ->whereHas('order', function ($query) {
                $query->whereIn('payment_status', RecommendationFeatureSnapshotService::interestPaymentStatuses());
            })
            ->with(['event', 'order'])
            ->get()
            ->filter(fn (RecommendationFeatureSnapshot $snapshot) => $snapshot->event !== null)
            ->values();
    }

    private function userHasInterestOrders(User $user): bool
    {
        return Order::query()
            ->where('user_id', $user->id)
            ->whereIn('payment_status', RecommendationFeatureSnapshotService::interestPaymentStatuses())
            ->exists();
    }

    private function buildUserEmbedding(Collection $historySnapshots, float $maxPrice): array
    {
        $weightedEmbeddings = [];
        $totalWeight = 0.0;

        foreach ($historySnapshots as $snapshot) {
            $recencyWeight = $this->recencyWeight($snapshot->paid_at ?? $snapshot->order?->paid_at);
            $weight = $recencyWeight;

            $features = $this->snapshotFeatures($snapshot, $maxPrice);
            $embedding = $this->neuralEncode($features['vector']);

            foreach ($embedding as $index => $value) {
                $weightedEmbeddings[$index] = ($weightedEmbeddings[$index] ?? 0.0) + ($value * $weight);
            }

            $totalWeight += $weight;
        }

        if ($totalWeight <= 0.0) {
            return [];
        }

        return array_map(
            fn (float $value) => $value / $totalWeight,
            $weightedEmbeddings
        );
    }

    private function coldStartRecommendations(User $user, Collection $candidates, int $limit): Collection
    {
        $maxPrice = $this->maxPrice($candidates);

        return $candidates
            ->map(function (Event $event) use ($user, $maxPrice) {
                $features = $this->eventFeatures(
                    $event,
                    $maxPrice,
                    $this->snapshotService->distanceFromUser($user, $event)
                );
                $soonness = $event->start_date
                    ? max(0.0, 1.0 - min(now()->diffInDays($event->start_date), 30) / 30)
                    : 0.0;

                $categoryConfidence = $features['category'] === 'lainnya' ? 0.55 : 1.0;
                $score = (0.34 * $soonness)
                    + (0.26 * $features['content_strength'])
                    + (0.18 * $categoryConfidence)
                    + (0.14 * $features['price_affinity'])
                    + (0.08 * $features['distance_closeness']);

                return $this->formatRecommendation($event, max(0.0, min(1.0, $score)), $features);
            })
            ->sortByDesc('score')
            ->take($limit)
            ->values();
    }

    private function snapshotFeatures(RecommendationFeatureSnapshot $snapshot, float $maxPrice): array
    {
        $event = $snapshot->event;
        $startAt = $snapshot->event_start_at ?? $event->start_date;
        $hour = (int) ($snapshot->event_hour ?? $startAt?->format('G') ?? 12);
        $isWeekend = (bool) ($snapshot->is_weekend ?? ($startAt && $startAt->isWeekend()));

        return $this->buildFeatures(
            $event,
            $this->normalizeCategory($snapshot->event_category ?? $event->category),
            (float) ($snapshot->event_price ?? $event->price),
            $hour,
            $isWeekend,
            $maxPrice,
            $snapshot->distance_meters !== null ? (float) $snapshot->distance_meters : null
        );
    }

    private function eventFeatures(Event $event, float $maxPrice, ?float $distanceMeters = null): array
    {
        $category = $this->normalizeCategory($event->category);
        $hour = (int) ($event->start_date?->format('G') ?? 12);
        $isWeekend = $event->start_date && $event->start_date->isWeekend();

        return $this->buildFeatures(
            $event,
            $category,
            (float) $event->price,
            $hour,
            $isWeekend,
            $maxPrice,
            $distanceMeters
        );
    }

    private function buildFeatures(
        Event $event,
        string $category,
        float $price,
        int $hour,
        bool $isWeekend,
        float $maxPrice,
        ?float $distanceMeters
    ): array {
        $categoryVector = array_fill(0, count(self::CATEGORIES), 0.0);
        $categoryVector[array_search($category, self::CATEGORIES, true)] = 1.8;
        $contentTokens = $this->contentTokens($event);
        $contentVector = $this->contentVector($contentTokens);

        $hourRadians = (2 * M_PI * $hour) / 24;
        $priceLevel = $maxPrice > 0 ? min($price / $maxPrice, 1.0) : 0.0;
        $durationHours = $event->end_date && $event->start_date
            ? max(0.0, $event->start_date->diffInMinutes($event->end_date) / 60)
            : 2.0;
        $durationLevel = min($durationHours / 8.0, 1.0);
        $distanceCloseness = $distanceMeters === null
            ? 0.5
            : max(0.0, 1.0 - min($distanceMeters, self::DISTANCE_CLAMP_METERS) / self::DISTANCE_CLAMP_METERS);

        $vector = array_merge($categoryVector, [
            ...array_map(fn (float $value) => $value * 1.2, $contentVector),
            sin($hourRadians) * 1.1,
            cos($hourRadians) * 1.1,
            $isWeekend ? 1.0 : 0.0,
            $priceLevel,
            1.0 - $priceLevel,
            $durationLevel,
            $distanceCloseness * 0.35,
        ]);

        return [
            'vector' => $vector,
            'category' => $category,
            'hour' => $hour,
            'price_level' => $priceLevel,
            'price_affinity' => 1.0 - $priceLevel,
            'content_strength' => min(count($contentTokens) / 10, 1.0),
            'distance_meters' => $distanceMeters,
            'distance_closeness' => $distanceCloseness,
        ];
    }

    private function neuralEncode(array $vector): array
    {
        $hidden = [];
        $count = count($vector);

        for ($i = 0; $i < $count; $i++) {
            $previous = $vector[($i - 1 + $count) % $count];
            $next = $vector[($i + 1) % $count];
            $mirror = $vector[$count - 1 - $i];
            $hidden[] = max(0.0, (0.68 * $vector[$i]) + (0.16 * $previous) + (0.14 * $next) + (0.08 * $mirror) - 0.04);
        }

        $categorySignal = array_sum(array_slice($vector, 0, count(self::CATEGORIES)));
        $contentStart = count(self::CATEGORIES);
        $contentSignal = array_sum(array_slice($vector, $contentStart, self::TEXT_VECTOR_SIZE)) / self::TEXT_VECTOR_SIZE;
        $timeStart = $contentStart + self::TEXT_VECTOR_SIZE;
        $timeSignal = ($vector[$timeStart] + $vector[$timeStart + 1] + $vector[$timeStart + 2]) / 3;
        $priceLevel = $vector[$timeStart + 3];
        $priceAffinity = $vector[$timeStart + 4];
        $durationLevel = $vector[$timeStart + 5];
        $distanceCloseness = $vector[$timeStart + 6];

        $hidden[] = $this->sigmoid(($categorySignal * 0.45) + ($contentSignal * 1.15) - ($priceLevel * 0.18));
        $hidden[] = $this->sigmoid(($contentSignal * 0.85) + ($timeSignal * 0.35) + ($durationLevel * 0.22));
        $hidden[] = $this->sigmoid(($categorySignal * 0.22) + ($priceAffinity * 0.38) + ($durationLevel * 0.18) + ($distanceCloseness * 0.12));

        return $this->normalizeVector($hidden);
    }

    private function formatRecommendation(Event $event, float $score, array $features, string $modelLabel = 'Neural content'): array
    {
        return [
            'event' => $event,
            'score' => $score,
            'score_label' => number_format($score * 100, 0) . '% cocok',
            'category_label' => $this->categoryLabel($features['category']),
            'time_label' => $this->timeLabel($features['hour']),
            'model_label' => $modelLabel,
            'price_label' => $event->price > 0
                ? 'Rp ' . number_format((float) $event->price, 0, ',', '.')
                : 'Gratis',
        ];
    }

    private function normalizeCategory(?string $category): string
    {
        $category = strtolower((string) $category);

        return in_array($category, self::CATEGORIES, true) ? $category : 'lainnya';
    }

    private function categoryLabel(string $category): string
    {
        return ucfirst(str_replace('_', ' ', $category));
    }

    private function timeLabel(int $hour): string
    {
        return match (true) {
            $hour >= 5 && $hour < 11 => 'Pagi',
            $hour >= 11 && $hour < 15 => 'Siang',
            $hour >= 15 && $hour < 18 => 'Sore',
            default => 'Malam',
        };
    }

    private function contentTokens(Event $event): array
    {
        $text = strtolower(trim(implode(' ', array_filter([
            $event->title,
            $event->description,
            $event->category,
        ]))));

        $normalized = preg_replace('/[^a-z0-9]+/', ' ', $text) ?? '';
        $tokens = preg_split('/\s+/', trim($normalized), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return collect($tokens)
            ->filter(fn (string $token) => strlen($token) >= 3)
            ->reject(fn (string $token) => in_array($token, self::STOPWORDS, true))
            ->values()
            ->all();
    }

    private function contentVector(array $tokens): array
    {
        $vector = array_fill(0, self::TEXT_VECTOR_SIZE, 0.0);

        foreach ($tokens as $token) {
            $slot = hexdec(substr(hash('crc32b', $token), -4)) % self::TEXT_VECTOR_SIZE;
            $vector[$slot] += 1.0 + (min(strlen($token), 12) / 24);
        }

        $vector = array_map(fn (float $value) => log(1 + $value), $vector);

        return $this->normalizeVector($vector);
    }

    private function maxPrice(Collection $events): float
    {
        return max(1.0, (float) $events->max('price'));
    }

    private function recencyWeight($paidAt): float
    {
        if (!$paidAt) {
            return 1.0;
        }

        $days = max(0, $paidAt->diffInDays(now()));

        return max(0.35, 1.0 - min($days, 120) / 180);
    }

    private function cosineSimilarity(array $a, array $b): float
    {
        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;
        $count = min(count($a), count($b));

        for ($i = 0; $i < $count; $i++) {
            $dot += $a[$i] * $b[$i];
            $normA += $a[$i] ** 2;
            $normB += $b[$i] ** 2;
        }

        if ($normA <= 0.0 || $normB <= 0.0) {
            return 0.0;
        }

        return max(0.0, min(1.0, $dot / (sqrt($normA) * sqrt($normB))));
    }

    private function normalizeVector(array $vector): array
    {
        $norm = sqrt(array_sum(array_map(fn (float $value) => $value ** 2, $vector)));

        if ($norm <= 0.0) {
            return $vector;
        }

        return array_map(fn (float $value) => $value / $norm, $vector);
    }

    private function sigmoid(float $value): float
    {
        return 1 / (1 + exp(-$value));
    }
}
