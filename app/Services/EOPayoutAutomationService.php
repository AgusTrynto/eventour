<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Payout;
use Illuminate\Support\Facades\Log;

class EOPayoutAutomationService
{
    public function createDuePayouts(?int $organizerId = null): int
    {
        $created = 0;

        Event::query()
            ->where('status', 'approved')
            ->when($organizerId, fn ($query) => $query->where('event_organizer_id', $organizerId))
            ->where(function ($query) {
                $query->where(function ($query) {
                    $query->whereNotNull('end_date')->where('end_date', '<', now());
                })->orWhere(function ($query) {
                    $query->whereNull('end_date')->where('start_date', '<', now());
                });
            })
            ->whereDoesntHave('payouts', function ($query) {
                $query->whereIn('status', ['pending', 'processing', 'completed', 'failed']);
            })
            ->with('organizer')
            ->get()
            ->each(function (Event $event) use (&$created) {
                if ($this->createPayoutForEvent($event)) {
                    $created++;
                }
            });

        return $created;
    }

    public function createPayoutForEvent(Event $event): ?Payout
    {
        $event->loadMissing('organizer');

        if (! $event->hasEnded()
            || ! $event->organizer?->bank_channel_code
            || ! $event->organizer?->bank_account_number
        ) {
            return null;
        }

        if ($event->payouts()->whereIn('status', ['pending', 'processing', 'completed', 'failed'])->exists()) {
            return null;
        }

        $net = $event->escrow_amount;

        if ($net <= 0) {
            return null;
        }

        $payout = Payout::create([
            'event_id' => $event->id,
            'event_organizer_id' => $event->event_organizer_id,
            'gross_amount' => $event->escrow_gross_amount,
            'platform_fee' => $event->escrow_admin_fee,
            'net_amount' => $net,
            'status' => 'pending',
            'request_reason' => 'Pencairan otomatis setelah event berakhir.',
            'requested_at' => now(),
        ]);

        try {
            app(XenditEOPayoutService::class)->queue($payout);
        } catch (\Throwable $e) {
            Log::warning('Unable to queue automatic EO payout', [
                'payout_id' => $payout->id,
                'event_id' => $event->id,
                'message' => $e->getMessage(),
            ]);
        }

        return $payout;
    }
}
