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
use Xendit\XenditSdkException;

class AdminRefundController extends Controller
{
    public function __construct()
    {
        Configuration::setXenditKey(config('services.xendit.secret_key'));
    }

    public function manualIndex()
    {
        $awaitingDestination = Order::where('payment_status', 'refund_manual_pending')
            ->with(['user', 'event'])
            ->latest('refund_requested_at')
            ->get();

        $readyToTransfer = Order::where('payment_status', 'refund_manual_processing')
            ->with(['user', 'event'])
            ->latest('refund_destination_submitted_at')
            ->get();

        $completedManualRefunds = Order::where('payment_status', 'refunded')
            ->whereNotNull('manual_refunded_at')
            ->with(['user', 'event'])
            ->latest('manual_refunded_at')
            ->get();

        return view('admin.refunds', compact(
            'awaitingDestination',
            'readyToTransfer',
            'completedManualRefunds'
        ));
    }

    public function completeManualRefund(Request $request, Order $order)
    {
        if ($order->payment_status !== 'refund_manual_processing') {
            return back()->with('error', 'Refund manual ini belum siap ditandai selesai.');
        }

        if (! $order->refund_destination_submitted_at) {
            return back()->with('error', 'User belum mengirim data tujuan refund.');
        }

        $data = $request->validate([
            'manual_refund_proof' => ['required', 'image', 'max:4096'],
            'manual_refund_admin_note' => ['nullable', 'string', 'max:500'],
        ]);

        $path = $request->file('manual_refund_proof')->store('refund-proofs', 'public');

        DB::transaction(function () use ($order, $data, $path) {
            $order->update([
                'payment_status' => 'refunded',
                'refunded_at' => now(),
                'manual_refunded_at' => now(),
                'manual_refund_proof' => $path,
                'manual_refund_admin_note' => $data['manual_refund_admin_note'] ?? null,
                'xendit_refund_status' => 'MANUAL_COMPLETED',
            ]);

            app(RecommendationFeatureSnapshotService::class)->recordPurchasedOrder($order->refresh());
        });

        return back()->with('success', 'Refund manual berhasil ditandai selesai.');
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

        $xenditProcessed = 0;
        $manualPending = 0;
        $localRefunded = 0;
        $failed = [];

        foreach ($orders as $order) {
            try {
                $state = $this->startRefund($order, $request->reason, CreateRefund::REASON_CANCELLATION);

                if ($state === 'manual_pending') {
                    $manualPending++;
                } elseif ($state === 'refunded') {
                    $localRefunded++;
                } else {
                    $xenditProcessed++;
                }
            } catch (\Throwable $e) {
                Log::error('Xendit refund request failed', [
                    'order_id' => $order->id,
                    'event_id' => $event->id,
                    'message' => $e->getMessage(),
                ]);

                $failed[] = "#{$order->id}: {$e->getMessage()}";
            }
        }

        $processed = $xenditProcessed + $manualPending + $localRefunded;

        if ($processed > 0 && empty($failed)) {
            // Event otomatis ditolak/dinonaktifkan agar tidak tampil lagi di map.
            $event->update([
                'status' => 'rejected',
                'reject_reason' => 'Event dibatalkan & refund diproses: '.$request->reason,
            ]);
        }

        if ($processed === 0) {
            return back()->with('error', 'Refund gagal dimulai. '.implode(' ', array_slice($failed, 0, 3)));
        }

        if (! empty($failed)) {
            return back()->with('error', "Refund berhasil diajukan untuk {$processed} transaksi, tapi ".count($failed).' transaksi gagal: '.implode(' | ', array_slice($failed, 0, 3)));
        }

        $details = [];

        if ($xenditProcessed > 0) {
            $details[] = "{$xenditProcessed} lewat Xendit";
        }

        if ($manualPending > 0) {
            $details[] = "{$manualPending} perlu refund manual";
        }

        if ($localRefunded > 0) {
            $details[] = "{$localRefunded} refund lokal";
        }

        return back()->with('success', "Refund diproses untuk {$processed} transaksi pada event \"{$event->title}\" (".implode(', ', $details).'). Tiket terkait sudah dibatalkan.');
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

        if ($state === 'manual_pending') {
            return back()->with('success', 'Channel pembayaran order ini tidak mendukung refund otomatis Xendit. Order ditandai perlu refund manual dan tiket terkait sudah dibatalkan.');
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

        try {
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
        } catch (\Throwable $e) {
            if ($this->isRefundNotSupported($e)) {
                $this->markManualRefundRequired($order, $adminReason, $referenceId);

                return 'manual_pending';
            }

            throw $e;
        }

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

    private function isRefundNotSupported(\Throwable $e): bool
    {
        if ($e instanceof XenditSdkException) {
            $fullError = (array) ($e->getFullError() ?? []);
            $errorCode = $e->getErrorCode()
                ?: ($fullError['error_code'] ?? null)
                ?: ($fullError['errorCode'] ?? null);

            if ($errorCode === 'REFUND_NOT_SUPPORTED') {
                return true;
            }
        }

        return str_contains(strtolower($e->getMessage()), 'refunds are not supported')
            || str_contains(strtolower($e->getMessage()), 'refund feature is not available');
    }

    private function refundReferenceId(Order $order): string
    {
        return 'REFUND-ORDER-'.$order->id.'-'.now()->format('YmdHis').'-'.Str::upper(Str::random(8));
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

    private function markManualRefundRequired(Order $order, string $adminReason, string $referenceId): void
    {
        DB::transaction(function () use ($order, $adminReason, $referenceId) {
            $refundDestination = $this->refundDestinationFromUserProfile($order);
            $hasRefundDestination = ! empty($refundDestination);

            $order->update([
                'payment_status' => $hasRefundDestination ? 'refund_manual_processing' : 'refund_manual_pending',
                'refund_requested_at' => now(),
                'refund_reason' => $adminReason,
                'xendit_refund_id' => null,
                'xendit_refund_reference_id' => $referenceId,
                'xendit_refund_status' => 'NOT_SUPPORTED',
                'xendit_refund_failure_code' => 'REFUND_NOT_SUPPORTED',
                'refund_destination_submitted_at' => $hasRefundDestination ? now() : null,
                ...$refundDestination,
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

    private function refundDestinationFromUserProfile(Order $order): array
    {
        $order->loadMissing('user');

        if (! $order->user?->hasRefundDestination()) {
            return [];
        }

        return [
            'refund_destination_type' => $order->user->refund_destination_type,
            'refund_destination_provider' => $order->user->refund_destination_provider,
            'refund_destination_channel_code' => $order->user->refund_destination_channel_code,
            'refund_destination_account_number' => $order->user->refund_destination_account_number,
            'refund_destination_account_name' => $order->user->refund_destination_account_name,
        ];
    }
}
