<?php

namespace App\Services;

use App\Models\Event;
use App\Models\RecommendationFeatureSnapshot;
use Illuminate\Support\Collection;

class NcbfFeatureVectorService
{
    private const TEXT_VECTOR_SIZE = 32;
    private const DEFAULT_HOUR = 12;
    private const DEFAULT_DURATION_LEVEL = 0.25;
    private const DEFAULT_DISTANCE_CLOSENESS = 0.5;

    private const CATEGORIES = [
        'musik',
        'seni',
        'olahraga',
        'kuliner',
        'teknologi',
        'travel',
        'gaming',
        'workshop',
        'seminar',
        'fashion_beauty',
        'komunitas',
        'bazaar',
        'otomotif',
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

    public function userProfileVector(Collection $snapshots, float $maxPrice): array
    {
        $weightedVector = [];
        $totalWeight = 0.0;

        foreach ($snapshots as $snapshot) {
            if (!$snapshot instanceof RecommendationFeatureSnapshot || !$snapshot->event) {
                continue;
            }

            $weight = $this->recencyWeight($snapshot->paid_at ?? $snapshot->order?->paid_at);
            $vector = $this->snapshotVector($snapshot, $maxPrice);

            foreach ($vector as $index => $value) {
                $weightedVector[$index] = ($weightedVector[$index] ?? 0.0) + ($value * $weight);
            }

            $totalWeight += $weight;
        }

        if ($totalWeight <= 0.0) {
            return array_fill(0, $this->eventVectorSize(), 0.0);
        }

        return array_map(
            fn (float $value) => $value / $totalWeight,
            $weightedVector
        );
    }

    public function snapshotVector(RecommendationFeatureSnapshot $snapshot, float $maxPrice): array
    {
        $event = $snapshot->event;

        return $this->eventVector(
            $event,
            $maxPrice,
            $this->normalizeCategory($snapshot->event_category ?? $event->category),
            (float) ($snapshot->event_price ?? $event->price),
            $snapshot->paid_at ?? $snapshot->order?->paid_at
        );
    }

    public function eventVector(
        Event $event,
        float $maxPrice,
        ?string $category = null,
        ?float $price = null,
        $paidAt = null
    ): array {
        $category = $this->normalizeCategory($category ?? $event->category);
        $price = $price ?? (float) $event->price;
        $hour = $this->hourFromPaidAt($paidAt);
        $isWeekend = $this->isWeekendPaidAt($paidAt);

        $categoryVector = array_fill(0, count(self::CATEGORIES), 0.0);
        $categoryVector[array_search($category, self::CATEGORIES, true)] = 1.0;

        $hourRadians = (2 * M_PI * $hour) / 24;
        $priceLevel = $maxPrice > 0 ? min($price / $maxPrice, 1.0) : 0.0;

        return array_merge($categoryVector, [
            ...$this->contentVector($event, $category),
            sin($hourRadians),
            cos($hourRadians),
            $isWeekend ? 1.0 : 0.0,
            $priceLevel,
            1.0 - $priceLevel,
            self::DEFAULT_DURATION_LEVEL,
            self::DEFAULT_DISTANCE_CLOSENESS,
        ]);
    }

    public function pairVector(array $userVector, array $eventVector): array
    {
        $size = min(count($userVector), count($eventVector));
        $diff = [];
        $product = [];

        for ($i = 0; $i < $size; $i++) {
            $diff[] = abs($userVector[$i] - $eventVector[$i]);
            $product[] = $userVector[$i] * $eventVector[$i];
        }

        return array_merge($userVector, $eventVector, $diff, $product);
    }

    public function eventVectorSize(): int
    {
        return count(self::CATEGORIES) + self::TEXT_VECTOR_SIZE + 7;
    }

    public function pairVectorSize(): int
    {
        return $this->eventVectorSize() * 4;
    }

    public function categoryCount(): int
    {
        return count(self::CATEGORIES);
    }

    public function textVectorSize(): int
    {
        return self::TEXT_VECTOR_SIZE;
    }

    private function contentVector(Event $event, string $category): array
    {
        $vector = array_fill(0, self::TEXT_VECTOR_SIZE, 0.0);

        foreach ($this->contentTokens($event, $category) as $token) {
            $slot = (int) (hexdec(hash('crc32b', $token)) % self::TEXT_VECTOR_SIZE);
            $vector[$slot] += 1.0 + (min(strlen($token), 12) / 24);
        }

        return $this->normalizeVector(array_map(fn (float $value) => log(1 + $value), $vector));
    }

    private function contentTokens(Event $event, string $category): array
    {
        $text = strtolower(trim(implode(' ', array_filter([
            $event->title,
            $category,
        ]))));

        $normalized = preg_replace('/[^a-z0-9]+/', ' ', $text) ?? '';
        $tokens = preg_split('/\s+/', trim($normalized), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return collect($tokens)
            ->filter(fn (string $token) => strlen($token) >= 3)
            ->reject(fn (string $token) => in_array($token, self::STOPWORDS, true))
            ->values()
            ->all();
    }

    private function normalizeCategory(?string $category): string
    {
        $category = strtolower((string) $category);

        return in_array($category, self::CATEGORIES, true) ? $category : self::CATEGORIES[0];
    }

    private function normalizeVector(array $vector): array
    {
        $norm = sqrt(array_sum(array_map(fn (float $value) => $value ** 2, $vector)));

        if ($norm <= 0.0) {
            return $vector;
        }

        return array_map(fn (float $value) => $value / $norm, $vector);
    }

    private function recencyWeight($paidAt): float
    {
        if (!$paidAt) {
            return 1.0;
        }

        $days = max(0, $paidAt->diffInDays(now()));

        return max(0.35, 1.0 - min($days, 120) / 180);
    }

    private function hourFromPaidAt($paidAt): int
    {
        if ($paidAt instanceof \DateTimeInterface) {
            return (int) $paidAt->format('G');
        }

        return self::DEFAULT_HOUR;
    }

    private function isWeekendPaidAt($paidAt): bool
    {
        if (!$paidAt instanceof \DateTimeInterface) {
            return false;
        }

        return (int) $paidAt->format('N') >= 6;
    }
}
