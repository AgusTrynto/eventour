<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Order;
use App\Models\RecommendationFeatureSnapshot;
use App\Models\Ticket;
use App\Models\User;

class RecommendationFeatureSnapshotService
{
    private const INTEREST_PAYMENT_STATUSES = [
        'pending',
        'paid',
        'disbursed',
        'expired',
        'refund_pending',
        'refund_manual_pending',
        'refund_manual_processing',
        'refund_payout_pending',
        'refund_payout_failed',
        'refunded',
        'cancelled',
        'canceled',
        'cancel',
        'failed',
    ];

    private const CATEGORIES = [
        'musik',
        'seni',
        'olahraga',
        'kuliner',
        'teknologi',
        'lainnya',
    ];

    private const DISTANCE_CLAMP_METERS = 100000.0;

    public static function interestPaymentStatuses(): array
    {
        return self::INTEREST_PAYMENT_STATUSES;
    }

    public function recordPurchasedOrder(Order $order): void
    {
        if (! $this->isInterestOrder($order)) {
            return;
        }

        $order->loadMissing(['event', 'user']);

        if (! $order->event || ! $order->user) {
            return;
        }

        $tickets = Ticket::query()
            ->where('order_id', $order->id)
            ->get();

        if ($tickets->isEmpty()) {
            $this->recordOrderSnapshot($order);

            return;
        }

        RecommendationFeatureSnapshot::query()
            ->where('order_id', $order->id)
            ->whereNull('ticket_id')
            ->delete();

        $now = now();
        $rows = $tickets
            ->map(fn (Ticket $ticket) => $this->snapshotDatabasePayload(
                $this->snapshotPayload($order->user, $order->event, $order, $ticket),
                $now
            ))
            ->all();

        if ($rows === []) {
            return;
        }

        RecommendationFeatureSnapshot::upsert(
            $rows,
            ['ticket_id'],
            [
                'user_id',
                'event_id',
                'order_id',
                'interaction_type',
                'label',
                'event_category',
                'event_price',
                'distance_meters',
                'event_start_at',
                'event_hour',
                'event_day_of_week',
                'is_weekend',
                'order_quantity',
                'paid_at',
                'feature_vector',
                'neural_score',
                'updated_at',
            ]
        );
    }

    public function recordPurchasedTicket(Ticket $ticket): ?RecommendationFeatureSnapshot
    {
        $ticket->loadMissing(['event', 'order', 'user']);

        if (! $ticket->event || ! $ticket->order || ! $ticket->user || ! $this->isInterestOrder($ticket->order)) {
            return null;
        }

        RecommendationFeatureSnapshot::query()
            ->where('order_id', $ticket->order_id)
            ->whereNull('ticket_id')
            ->delete();

        return RecommendationFeatureSnapshot::updateOrCreate(
            ['ticket_id' => $ticket->id],
            $this->snapshotPayload($ticket->user, $ticket->event, $ticket->order, $ticket)
        );
    }

    public function syncPurchasedTicketsForUser(User $user): void
    {
        Order::query()
            ->where('user_id', $user->id)
            ->whereIn('payment_status', self::INTEREST_PAYMENT_STATUSES)
            ->with(['event', 'user'])
            ->get()
            ->each(fn (Order $order) => $this->recordPurchasedOrder($order));
    }

    private function recordOrderSnapshot(Order $order): ?RecommendationFeatureSnapshot
    {
        $order->loadMissing(['event', 'user']);

        if (! $order->event || ! $order->user || ! $this->isInterestOrder($order)) {
            return null;
        }

        return RecommendationFeatureSnapshot::updateOrCreate(
            [
                'order_id' => $order->id,
                'ticket_id' => null,
            ],
            $this->snapshotPayload($order->user, $order->event, $order, null)
        );
    }

    private function snapshotPayload(User $user, Event $event, Order $order, ?Ticket $ticket): array
    {
        $category = $this->normalizeCategory($event->category);
        $price = (float) ($event->price ?? $order->unit_price ?? 0);
        $distanceMeters = $this->distanceFromUser($user, $event);
        $startAt = $event->start_date;
        $hour = $startAt ? (int) $startAt->format('G') : null;
        $dayOfWeek = $startAt ? (int) $startAt->dayOfWeek : null;
        $isWeekend = $startAt ? $startAt->isWeekend() : false;

        return [
            'user_id' => $user->id,
            'event_id' => $event->id,
            'order_id' => $order->id,
            'ticket_id' => $ticket?->id,
            'interaction_type' => 'purchased',
            'label' => 1,
            'event_category' => $category,
            'event_price' => $price,
            'distance_meters' => $distanceMeters,
            'event_start_at' => $startAt,
            'event_hour' => $hour,
            'event_day_of_week' => $dayOfWeek,
            'is_weekend' => $isWeekend,
            'order_quantity' => max(1, (int) $order->quantity),
            'paid_at' => $order->paid_at ?? $order->created_at,
            'feature_vector' => $this->featureVector($category, $price, $distanceMeters, $hour, $dayOfWeek, $isWeekend),
        ];
    }

    private function featureVector(
        string $category,
        float $price,
        ?float $distanceMeters,
        ?int $hour,
        ?int $dayOfWeek,
        bool $isWeekend
    ): array {
        $categoryVector = array_fill_keys(self::CATEGORIES, 0.0);
        $categoryVector[$category] = 1.0;

        $hour = $hour ?? 12;
        $hourRadians = (2 * M_PI * $hour) / 24;
        $distanceCloseness = $distanceMeters === null
            ? 0.5
            : max(0.0, 1.0 - min($distanceMeters, self::DISTANCE_CLAMP_METERS) / self::DISTANCE_CLAMP_METERS);

        return [
            'category' => $categoryVector,
            'price' => $price,
            'distance_meters' => $distanceMeters,
            'distance_closeness' => $distanceCloseness,
            'event_hour' => $hour,
            'hour_sin' => sin($hourRadians),
            'hour_cos' => cos($hourRadians),
            'event_day_of_week' => $dayOfWeek,
            'is_weekend' => $isWeekend ? 1.0 : 0.0,
        ];
    }

    private function snapshotDatabasePayload(array $payload, $now): array
    {
        return [
            ...$payload,
            'is_weekend' => $payload['is_weekend'] ? 1 : 0,
            'event_start_at' => $payload['event_start_at']?->format('Y-m-d H:i:s'),
            'paid_at' => $payload['paid_at']?->format('Y-m-d H:i:s'),
            'feature_vector' => json_encode($payload['feature_vector']),
            'neural_score' => $payload['neural_score'] ?? null,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    public function distanceFromUser(User $user, Event $event): ?float
    {
        if (! $user->last_location || $event->lat === null || $event->lng === null) {
            return null;
        }

        $latFrom = deg2rad((float) $user->last_location->latitude);
        $lngFrom = deg2rad((float) $user->last_location->longitude);
        $latTo = deg2rad((float) $event->lat);
        $lngTo = deg2rad((float) $event->lng);

        $latDelta = $latTo - $latFrom;
        $lngDelta = $lngTo - $lngFrom;

        $angle = 2 * asin(sqrt(
            (sin($latDelta / 2) ** 2)
            + cos($latFrom) * cos($latTo) * (sin($lngDelta / 2) ** 2)
        ));

        return 6371000 * $angle;
    }

    private function isInterestOrder(Order $order): bool
    {
        return in_array($order->payment_status, self::INTEREST_PAYMENT_STATUSES, true);
    }

    private function normalizeCategory(?string $category): string
    {
        $category = strtolower((string) $category);

        return in_array($category, self::CATEGORIES, true) ? $category : 'lainnya';
    }
}
