<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Order;
use App\Models\Ticket;
use App\Services\RecommendationFeatureSnapshotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Xendit\Configuration;
use Xendit\Invoice\CreateInvoiceRequest;
use Xendit\Invoice\InvoiceApi;
use Xendit\XenditSdkException;

class CheckoutController extends Controller
{
    public function __construct()
    {
        Configuration::setXenditKey(config('services.xendit.secret_key'));
    }

    // =========================================================
    // Halaman checkout — pilih jumlah tiket
    // =========================================================
    public function show(Event $event)
    {
        if ($event->status !== 'approved') {
            abort(404);
        }

        if ($event->hasEnded()) {
            return redirect()->route('events.show', $event)
                ->with('error', 'Event sudah berakhir. Tiket tidak tersedia lagi.');
        }

        if ($redirect = $this->redirectIfRefundDestinationMissing($event)) {
            return $redirect;
        }

        return view('checkout.show', compact('event'));
    }

    // =========================================================
    // Buat Order + Invoice Xendit, lalu redirect ke halaman bayar
    // =========================================================
    public function store(Request $request, Event $event)
    {
        if ($event->status !== 'approved') {
            abort(404);
        }

        if ($event->hasEnded()) {
            return redirect()->route('events.show', $event)
                ->with('error', 'Event sudah berakhir. Tiket tidak tersedia lagi.');
        }

        if ($redirect = $this->redirectIfRefundDestinationMissing($event)) {
            return $redirect;
        }

        $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:10'],
        ]);

        $user = Auth::user();
        $quantity = (int) $request->quantity;
        $total = $event->price * $quantity;

        if ($event->quota !== null) {
            $sold = $event->tickets_sold;
            if (($sold + $quantity) > $event->quota) {
                return back()->with('error', 'Kuota tiket tidak mencukupi.');
            }
        }

        $externalId = 'ORDER-'.Str::upper(Str::random(10)).'-'.time();

        $order = Order::create([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'quantity' => $quantity,
            'unit_price' => $event->price,
            'total_amount' => $total,
            'payment_status' => 'pending',
            'payment_method' => 'xendit',
            'external_id' => $externalId,
        ]);

        app(RecommendationFeatureSnapshotService::class)->recordPurchasedOrder($order);

        // ── Free event: tidak perlu bayar, langsung tandai paid ──
        if ($total <= 0) {
            $order->update([
                'payment_status' => 'paid',
                'paid_at' => now(),
            ]);

            $this->generateTickets($order);

            return redirect()->route('checkout.success', $order)
                ->with('success', 'Tiket gratis berhasil diklaim!');
        }

        // ── Buat invoice Xendit pakai SDK resmi ──────────────
        try {
            $apiInstance = new InvoiceApi;

            $createInvoiceRequest = new CreateInvoiceRequest([
                'external_id' => $externalId,
                'amount' => (int) $total,
                'payer_email' => $user->email,
                'description' => "Tiket {$event->title} x{$quantity}",
                'invoice_duration' => 3600, // 1 jam (detik)
                'currency' => 'IDR',
                'success_redirect_url' => route('checkout.success', $order),
                'failure_redirect_url' => route('checkout.failed', $order),
            ]);

            $invoice = $apiInstance->createInvoice($createInvoiceRequest);

            $order->update([
                'xendit_invoice_id' => $invoice->getId(),
                'xendit_invoice_url' => $invoice->getInvoiceUrl(),
            ]);

            return redirect($invoice->getInvoiceUrl());
        } catch (XenditSdkException $e) {
            Log::error(
                'Xendit invoice creation failed: '.$e->getMessage(),
                (array) ($e->getFullError() ?? [])
            );
            $order->delete();

            return back()->with('error', 'Gagal membuat invoice pembayaran. Coba lagi.');
        }
    }

    public function success(Order $order)
    {
        if ($order->payment_status === 'pending' && $order->xendit_invoice_id) {
            try {
                $invoice = (new InvoiceApi)->getInvoiceById($order->xendit_invoice_id);
                $status = $invoice->getStatus();

                if (in_array($status, ['PAID', 'SETTLED'], true)) {
                    $order->update([
                        'payment_status' => 'paid',
                        'paid_at' => now(),
                    ]);

                    $this->generateTickets($order);
                    $order->refresh();
                }
            } catch (XenditSdkException $e) {
                Log::warning('Xendit invoice status check failed: '.$e->getMessage());
            }
        }

        return view('checkout.success', compact('order'));
    }

    public function failed(Order $order)
    {
        return view('checkout.failed', compact('order'));
    }

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

    private function redirectIfRefundDestinationMissing(Event $event)
    {
        $user = Auth::user();

        if ($user?->hasRefundDestination()) {
            return null;
        }

        session([
            'profile.redirect_after_update' => route('checkout.show', $event, false),
        ]);

        return redirect()->route('profile.edit')
            ->with('error', 'Lengkapi data tujuan refund dulu sebelum beli tiket.');
    }
}
