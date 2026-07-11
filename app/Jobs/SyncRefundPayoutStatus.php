<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\XenditRefundPayoutService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncRefundPayoutStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public int $orderId,
        public int $attempt = 1,
    ) {}

    public function handle(XenditRefundPayoutService $payoutService): void
    {
        $order = Order::find($this->orderId);

        if (! $order) {
            return;
        }

        $payoutService->syncStatusFromXendit($order, true, $this->attempt);
    }
}
