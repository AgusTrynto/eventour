<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Xendit\Invoice\InvoiceCallback;
use App\Models\Ticket;

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

        $callback = new InvoiceCallback($payload);

        $externalId = $callback->getExternalId();
        $status     = $callback->getStatus();

        if (!$externalId) {
            return response()->json(['message' => 'Missing external_id'], 400);
        }

        $order = Order::where('external_id', $externalId)->first();

        if (!$order) {
            Log::warning("Xendit webhook: order not found for external_id {$externalId}");
            return response()->json(['message' => 'Order not found'], 404);
        }

        switch ($status) {
            case 'PAID':
            case 'SETTLED':
                if ($order->payment_status === 'pending') {
                    $order->update([
                        'payment_status' => 'paid',
                        'paid_at'        => now(),
                    ]);

                    $this->generateTickets($order); // ← INI YANG HILANG

                    Log::info("Order {$order->id} marked as paid via webhook");
                }
                break;

            case 'EXPIRED':
                if ($order->payment_status === 'pending') {
                    $order->update(['payment_status' => 'expired']);
                }
                break;

            default:
                Log::info("Xendit webhook: unhandled status {$status}");
        }

        return response()->json(['message' => 'OK']);
    }

    // =========================================================
    // Buat 1 tiket per quantity di order, masing-masing dengan
    // kode unik sendiri (1 tiket = 1 kursi/akses masuk)
    // =========================================================
    private function generateTickets(Order $order): void
    {
        if (Ticket::where('order_id', $order->id)->exists()) {
            return;
        }

        for ($i = 0; $i < $order->quantity; $i++) {
            Ticket::create([
                'order_id'    => $order->id,
                'event_id'    => $order->event_id,
                'user_id'     => $order->user_id,
                'ticket_code' => Ticket::generateCode(),
                'status'      => 'valid',
            ]);
        }
    }
}