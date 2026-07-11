<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Ticket;
use App\Services\RecommendationFeatureSnapshotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Xendit\Invoice\InvoiceCallback;

class XenditWebhookController extends Controller
{
    // =========================================================
    // Endpoint dipanggil oleh server Xendit saat status invoice
    // berubah (PAID, EXPIRED, dll).
    //
    // Daftarkan URL ini di Xendit Dashboard →
    // Settings → Webhooks → Invoice Callback,
    // lalu copy "Verification Token" ke .env
    // =========================================================
    public function handle(Request $request)
    {
        $token = $request->header('x-callback-token');

        if ($token !== config('services.xendit.webhook_token')) {
            Log::warning('Xendit webhook: invalid token');

            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $payload = $request->all();
        Log::info('Xendit webhook received', $payload);

        $event = $payload['event'] ?? null;

        if (is_string($event) && str_starts_with($event, 'refund.')) {
            return $this->handleRefund($payload);
        }

        $callback = new InvoiceCallback($payload);

        $externalId = $callback->getExternalId();
        $status = $callback->getStatus();

        if (! $externalId) {
            Log::warning('Xendit webhook: missing external_id');

            return response()->json(['message' => 'OK']);
        }

        $order = Order::where('external_id', $externalId)->first();

        if (! $order) {
            Log::warning("Xendit webhook: order not found for external_id {$externalId}");

            return response()->json(['message' => 'OK']);
        }

        switch ($status) {
            case 'PAID':
            case 'SETTLED':
                if ($order->payment_status === 'pending') {
                    $order->update([
                        'payment_status' => 'paid',
                        'paid_at' => now(),
                    ]);

                    $this->generateTickets($order); // ← INI YANG HILANG

                    Log::info("Order {$order->id} marked as paid via webhook");
                }
                break;

            case 'EXPIRED':
                if ($order->payment_status === 'pending') {
                    $order->update(['payment_status' => 'expired']);
                    app(RecommendationFeatureSnapshotService::class)->recordPurchasedOrder($order);
                }
                break;

            default:
                Log::info("Xendit webhook: unhandled status {$status}");
        }

        return response()->json(['message' => 'OK']);
    }

    private function handleRefund(array $payload)
    {
        $data = $payload['data'] ?? [];

        // Some Xendit examples show data wrapped twice. Accept both shapes.
        if (isset($data['data']) && is_array($data['data'])) {
            $data = $data['data'];
        }

        $event = $payload['event'] ?? null;
        $refundId = $data['id'] ?? null;
        $referenceId = $data['reference_id'] ?? null;
        $invoiceId = $data['invoice_id'] ?? null;
        $status = strtoupper((string) ($data['status'] ?? ''));
        $failureCode = $data['failure_code'] ?? null;

        if ($event === 'refund.succeeded') {
            $status = 'SUCCEEDED';
        } elseif ($event === 'refund.failed') {
            $status = 'FAILED';
        }

        $order = $this->findRefundOrder($refundId, $referenceId, $invoiceId);

        if (! $order) {
            Log::warning('Xendit refund webhook: order not found', [
                'refund_id' => $refundId,
                'reference_id' => $referenceId,
                'invoice_id' => $invoiceId,
            ]);

            return response()->json(['message' => 'OK']);
        }

        if ($this->isStaleRefundWebhook($order, $refundId, $referenceId)) {
            Log::info('Xendit refund webhook ignored because it belongs to an older refund attempt', [
                'order_id' => $order->id,
                'refund_id' => $refundId,
                'reference_id' => $referenceId,
            ]);

            return response()->json(['message' => 'OK']);
        }

        if ($status === 'SUCCEEDED') {
            DB::transaction(function () use ($order, $refundId, $referenceId) {
                $order->update([
                    'payment_status' => 'refunded',
                    'refunded_at' => $order->refunded_at ?? now(),
                    'xendit_refund_id' => $refundId ?? $order->xendit_refund_id,
                    'xendit_refund_reference_id' => $referenceId ?? $order->xendit_refund_reference_id,
                    'xendit_refund_status' => 'SUCCEEDED',
                    'xendit_refund_failure_code' => null,
                ]);

                $order->tickets()->update([
                    'status' => 'cancelled',
                    'checked_in_at' => null,
                    'checked_in_by' => null,
                ]);

                app(RecommendationFeatureSnapshotService::class)->recordPurchasedOrder($order->refresh());
            });

            Log::info("Order {$order->id} marked as refunded via webhook");

            return response()->json(['message' => 'OK']);
        }

        if ($status === 'FAILED') {
            $order->update([
                'payment_status' => 'paid',
                'xendit_refund_id' => $refundId ?? $order->xendit_refund_id,
                'xendit_refund_reference_id' => $referenceId ?? $order->xendit_refund_reference_id,
                'xendit_refund_status' => 'FAILED',
                'xendit_refund_failure_code' => $failureCode,
            ]);

            app(RecommendationFeatureSnapshotService::class)->recordPurchasedOrder($order->refresh());

            Log::warning("Xendit refund webhook: refund failed for order {$order->id}", [
                'failure_code' => $failureCode,
            ]);

            return response()->json(['message' => 'OK']);
        }

        Log::info("Xendit refund webhook: unhandled status {$status}");

        return response()->json(['message' => 'OK']);
    }

    private function findRefundOrder(?string $refundId, ?string $referenceId, ?string $invoiceId): ?Order
    {
        if ($refundId) {
            $order = Order::where('xendit_refund_id', $refundId)->first();

            if ($order) {
                return $order;
            }
        }

        if ($referenceId) {
            $order = Order::where('xendit_refund_reference_id', $referenceId)->first();

            if ($order) {
                return $order;
            }
        }

        if ($invoiceId) {
            return Order::where('xendit_invoice_id', $invoiceId)->first();
        }

        return null;
    }

    private function isStaleRefundWebhook(Order $order, ?string $refundId, ?string $referenceId): bool
    {
        if ($refundId && $order->xendit_refund_id && $refundId !== $order->xendit_refund_id) {
            return true;
        }

        if ($referenceId && $order->xendit_refund_reference_id && $referenceId !== $order->xendit_refund_reference_id) {
            return true;
        }

        return false;
    }

    // =========================================================
    // Buat 1 tiket per quantity di order, masing-masing dengan
    // kode unik sendiri (1 tiket = 1 kursi/akses masuk)
    // =========================================================
    private function generateTickets(Order $order): void
    {
        if (! Ticket::where('order_id', $order->id)->exists()) {
            for ($i = 0; $i < $order->quantity; $i++) {
                Ticket::create([
                    'order_id' => $order->id,
                    'event_id' => $order->event_id,
                    'user_id' => $order->user_id,
                    'ticket_code' => Ticket::generateCode(),
                    'status' => 'valid',
                ]);
            }
        }

        $order->refresh();

        app(RecommendationFeatureSnapshotService::class)->recordPurchasedOrder($order);
    }
}
