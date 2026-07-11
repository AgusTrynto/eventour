<?php

namespace App\Jobs;

use App\Models\Payout;
use App\Services\XenditEOPayoutService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessEOPayout implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public int $payoutId) {}

    public function handle(XenditEOPayoutService $payoutService): void
    {
        $payout = Payout::find($this->payoutId);

        if (! $payout) {
            return;
        }

        $payoutService->send($payout);
    }
}
