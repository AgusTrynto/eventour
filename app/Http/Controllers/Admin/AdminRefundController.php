<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Order;
use App\Services\RecommendationFeatureSnapshotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Xendit\Configuration;
use Xendit\Refund\CreateRefund;
use Xendit\Refund\RefundApi;

class AdminRefundController extends Controller
{
    public function __construct()
    {
        Configuration::setXenditKey(config('services.xendit.secret_key'));
    }

    // =========================================================
    // Refund SEMUA order paid pada sebuah event sekaligus
    // (dipakai saat event terbukti palsu / dibatalkan)
    // =========================================================
    public function refundEvent(Request $request, Event $event)
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        if ($event->payouts()->whereIn('status', ['pending', 'processing', 'completed'])->exists()) {
            return back()->with('error', 'Event ini masih memiliki payout aktif. Tolak atau selesaikan payout dulu sebelum refund.');
        }

        $orders = Order::where('event_id', $event->id)
            ->where('payment_status', 'paid')
            ->get();

        if ($orders->isEmpty()) {
            return back()->with('error', 'Tidak ada transaksi yang bisa direfund pada event ini.');
        }

        $processed = 0;
        $failed = [];

        foreach ($orders as $order) {
            try {
                $this->startRefund($order, $request->reason, CreateRefund::REASON_CANCELLATION);
                $processed++;
            } catch (\Throwable $e) {
                Log::error('Xendit refund request failed', [
                    'order_id' => $order->id,
                    'event_id' => $event->id,
                    'message' => $e->getMessage(),
                ]);

                $failed[] = "#{$order->id}: {$e->getMessage()}";
            }
        }

        if ($processed > 0 && empty($failed)) {
            // Event otomatis ditolak/dinonaktifkan agar tidak tampil lagi di map.
            $event->update([
                'status' => 'rejected',
                'reject_reason' => 'Event dibatalkan & refund Xendit diproses: '.$request->reason,
            ]);
        }

        if ($processed === 0) {
            return back()->with('error', 'Refund gagal dimulai. '.implode(' ', array_slice($failed, 0, 3)));
        }

        if (! empty($failed)) {
            return back()->with('error', "Refund berhasil diajukan untuk {$processed} transaksi, tapi ".count($failed).' transaksi gagal: '.implode(' | ', array_slice($failed, 0, 3)));
        }

        return back()->with('success', "Refund Xendit diproses untuk {$processed} transaksi pada event \"{$event->title}\". Status final akan diperbarui lewat webhook.");
    }

    // =========================================================
    // Refund satu order tertentu (kasus per-user, bukan massal)
    // =========================================================
    public function refundOrder(Request $request, Order $order)
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        if ($order->payment_status !== 'paid') {
            return back()->with('error', 'Order ini tidak bisa direfund (status bukan "paid").');
        }

        try {
            $state = $this->startRefund($order, $request->reason, CreateRefund::REASON_REQUESTED_BY_CUSTOMER);
        } catch (\Throwable $e) {
            Log::error('Xendit refund request failed', [
                'order_id' => $order->id,
                'message' => $e->getMessage(),
            ]);

            return back()->with('error', 'Refund gagal dimulai: '.$e->getMessage());
        }

        if ($state === 'refunded') {
            return back()->with('success', 'Refund order lokal berhasil. Tiket terkait sudah dibatalkan.');
        }

        return back()->with('success', 'Refund Xendit diproses. Status final akan diperbarui lewat webhook.');
    }

    private function startRefund(Order $order, string $adminReason, string $xenditReason): string
    {
        if (! $order->relationLoaded('tickets')) {
            $order->load('tickets');
        }

        if ((float) $order->total_amount <= 0) {
            $this->markRefundSucceeded($order, $adminReason, null, null);

            return 'refunded';
        }

        if (! $order->xendit_invoice_id) {
            throw new RuntimeException('Order tidak punya invoice_id Xendit.');
        }

        $referenceId = $this->refundReferenceId($order);
        $refund = (new RefundApi)->createRefund(
            $referenceId,
            null,
            new CreateRefund([
                'invoice_id' => $order->xendit_invoice_id,
                'reference_id' => $referenceId,
                'amount' => (float) $order->total_amount,
                'currency' => 'IDR',
                'reason' => $xenditReason,
                'metadata' => (object) [
                    'order_id' => (string) $order->id,
                    'event_id' => (string) $order->event_id,
                    'admin_reason' => $adminReason,
                ],
            ])
        );

        DB::transaction(function () use ($order, $adminReason, $referenceId, $refund) {
            $order->update([
                'payment_status' => 'refund_pending',
                'refund_requested_at' => now(),
                'refund_reason' => $adminReason,
                'xendit_refund_id' => $refund->getId(),
                'xendit_refund_reference_id' => $referenceId,
                'xendit_refund_status' => 'PENDING',
                'xendit_refund_failure_code' => null,
            ]);

            $this->cancelTickets($order);

            app(RecommendationFeatureSnapshotService::class)->recordPurchasedOrder($order->refresh());
        });

        return 'pending';
    }

    private function refundReferenceId(Order $order): string
    {
        if ($order->xendit_refund_reference_id && $order->xendit_refund_status !== 'FAILED') {
            return $order->xendit_refund_reference_id;
        }

        if ($order->xendit_refund_status === 'FAILED') {
            return 'REFUND-ORDER-'.$order->id.'-'.Str::upper(Str::random(8));
        }

        return 'REFUND-ORDER-'.$order->id;
    }

    private function markRefundSucceeded(Order $order, string $adminReason, ?string $refundId, ?string $referenceId): void
    {
        DB::transaction(function () use ($order, $adminReason, $refundId, $referenceId) {
            $order->update([
                'payment_status' => 'refunded',
                'refunded_at' => now(),
                'refund_requested_at' => $order->refund_requested_at ?? now(),
                'refund_reason' => $adminReason,
                'xendit_refund_id' => $refundId,
                'xendit_refund_reference_id' => $referenceId,
                'xendit_refund_status' => 'SUCCEEDED',
                'xendit_refund_failure_code' => null,
            ]);

            $this->cancelTickets($order);

            app(RecommendationFeatureSnapshotService::class)->recordPurchasedOrder($order->refresh());
        });
    }

    private function cancelTickets(Order $order): void
    {
        $order->tickets()->update([
            'status' => 'cancelled',
            'checked_in_at' => null,
            'checked_in_by' => null,
        ]);
    }
}
