<?php

namespace App\Services;

use App\Models\Event;
use App\Models\RecommendationFeatureSnapshot;
use Illuminate\Support\Collection;

class NcbfFeatureVectorService
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
            $snapshot->distance_meters !== null ? (float) $snapshot->distance_meters : null,
            $this->normalizeCategory($snapshot->event_category ?? $event->category),
            (float) ($snapshot->event_price ?? $event->price),
            (int) ($snapshot->event_hour ?? $event->start_date?->format('G') ?? 12),
            (bool) ($snapshot->is_weekend ?? ($event->start_date && $event->start_date->isWeekend()))
        );
    }

    public function eventVector(
        Event $event,
        float $maxPrice,
        ?float $distanceMeters = null,
        ?string $category = null,
        ?float $price = null,
        ?int $hour = null,
        ?bool $isWeekend = null
    ): array {
        $category = $this->normalizeCategory($category ?? $event->category);
        $price = $price ?? (float) $event->price;
        $hour = $hour ?? (int) ($event->start_date?->format('G') ?? 12);
        $isWeekend = $isWeekend ?? (bool) ($event->start_date && $event->start_date->isWeekend());

        $categoryVector = array_fill(0, count(self::CATEGORIES), 0.0);
        $categoryVector[array_search($category, self::CATEGORIES, true)] = 1.0;

        $hourRadians = (2 * M_PI * $hour) / 24;
        $priceLevel = $maxPrice > 0 ? min($price / $maxPrice, 1.0) : 0.0;
        $durationHours = $event->end_date && $event->start_date
            ? max(0.0, $event->start_date->diffInMinutes($event->end_date) / 60)
            : 2.0;
        $durationLevel = min($durationHours / 8.0, 1.0);
        $distanceCloseness = $distanceMeters === null
            ? 0.5
            : max(0.0, 1.0 - min($distanceMeters, self::DISTANCE_CLAMP_METERS) / self::DISTANCE_CLAMP_METERS);

        return array_merge($categoryVector, [
            ...$this->contentVector($event),
            sin($hourRadians),
            cos($hourRadians),
            $isWeekend ? 1.0 : 0.0,
            $priceLevel,
            1.0 - $priceLevel,
            $durationLevel,
            $distanceCloseness,
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

    private function contentVector(Event $event): array
    {
        $vector = array_fill(0, self::TEXT_VECTOR_SIZE, 0.0);

        foreach ($this->contentTokens($event) as $token) {
            $slot = hexdec(substr(hash('crc32b', $token), -4)) % self::TEXT_VECTOR_SIZE;
            $vector[$slot] += 1.0 + (min(strlen($token), 12) / 24);
        }

        return $this->normalizeVector(array_map(fn (float $value) => log(1 + $value), $vector));
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

    private function normalizeCategory(?string $category): string
    {
        $category = strtolower((string) $category);

        return in_array($category, self::CATEGORIES, true) ? $category : 'lainnya';
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
}
