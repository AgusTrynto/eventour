<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Services\XenditRefundPayoutService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TicketController extends Controller
{
    // =========================================================
    // List semua tiket milik user yang login
    // =========================================================
    public function index()
    {
        $tickets = Ticket::where('user_id', Auth::id())
            ->with('event', 'order')
            ->latest()
            ->get()
            ->groupBy('order_id'); // kelompokkan per transaksi

        $this->syncVisibleRefundPayouts($tickets);

        return view('tickets.index', compact('tickets'));
    }

    // =========================================================
    // Detail 1 tiket — tampilkan QR besar untuk di-scan
    // =========================================================
    public function show(Ticket $ticket)
    {
        if ($ticket->user_id !== Auth::id()) {
            abort(403);
        }

        $ticket->load('event', 'order');

        $this->syncTicketRefundPayout($ticket);

        return view('tickets.show', compact('ticket'));
    }

    private function syncVisibleRefundPayouts($tickets): void
    {
        $payoutService = app(XenditRefundPayoutService::class);

        $tickets->flatten()
            ->pluck('order')
            ->filter(fn ($order) => $order
                && $order->payment_status === 'refund_payout_pending'
                && ($order->xendit_payout_id || $order->xendit_payout_reference_id))
            ->unique('id')
            ->each(function ($order) use ($payoutService) {
                try {
                    $payoutService->syncStatusFromXendit($order);
                    $order->refresh();
                } catch (\Throwable $e) {
                    Log::warning('Unable to sync visible refund payout status', [
                        'order_id' => $order->id,
                        'message' => $e->getMessage(),
                    ]);
                }
            });
    }

    private function syncTicketRefundPayout(Ticket $ticket): void
    {
        $order = $ticket->order;

        if (! $order
            || $order->payment_status !== 'refund_payout_pending'
            || (! $order->xendit_payout_id && ! $order->xendit_payout_reference_id)
        ) {
            return;
        }

        try {
            app(XenditRefundPayoutService::class)->syncStatusFromXendit($order);
            $ticket->load('event', 'order');
        } catch (\Throwable $e) {
            Log::warning('Unable to sync ticket refund payout status', [
                'order_id' => $order->id,
                'ticket_id' => $ticket->id,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
