<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

use Xendit\Configuration;
use Xendit\Invoice\InvoiceApi;
use Xendit\Invoice\CreateInvoiceRequest;
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

        $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:10'],
        ]);

        $user     = Auth::user();
        $quantity = (int) $request->quantity;
        $total    = $event->price * $quantity;

        if ($event->quota !== null) {
            $sold = $event->tickets_sold;
            if (($sold + $quantity) > $event->quota) {
                return back()->with('error', 'Kuota tiket tidak mencukupi.');
            }
        }

        $externalId = 'ORDER-' . Str::upper(Str::random(10)) . '-' . time();

        $order = Order::create([
            'user_id'        => $user->id,
            'event_id'       => $event->id,
            'quantity'       => $quantity,
            'unit_price'     => $event->price,
            'total_amount'   => $total,
            'payment_status' => 'pending',
            'payment_method' => 'xendit',
            'external_id'    => $externalId,
        ]);

        // ── Free event: tidak perlu bayar, langsung tandai paid ──
        if ($total <= 0) {
            $order->update([
                'payment_status' => 'paid',
                'paid_at'        => now(),
            ]);

            return redirect()->route('checkout.success', $order)
                ->with('success', 'Tiket gratis berhasil diklaim!');
        }

        // ── Buat invoice Xendit pakai SDK resmi ──────────────
        try {
            $apiInstance = new InvoiceApi();

            $createInvoiceRequest = new CreateInvoiceRequest([
                'external_id'           => $externalId,
                'amount'                => (int) $total,
                'payer_email'           => $user->email,
                'description'           => "Tiket {$event->title} x{$quantity}",
                'invoice_duration'      => 3600, // 1 jam (detik)
                'currency'              => 'IDR',
                'success_redirect_url'  => route('checkout.success', $order),
                'failure_redirect_url'  => route('checkout.failed', $order),
            ]);

            $invoice = $apiInstance->createInvoice($createInvoiceRequest);

            $order->update([
                'xendit_invoice_id'  => $invoice->getId(),
                'xendit_invoice_url' => $invoice->getInvoiceUrl(),
            ]);

            return redirect($invoice->getInvoiceUrl());
        } catch (XenditSdkException $e) {
            Log::error(
                'Xendit invoice creation failed: ' . $e->getMessage(),
                (array) ($e->getFullError() ?? [])
            );
            $order->delete();
            return back()->with('error', 'Gagal membuat invoice pembayaran. Coba lagi.');
        }
    }

    public function success(Order $order)
    {
        return view('checkout.success', compact('order'));
    }

    public function failed(Order $order)
    {
        return view('checkout.failed', compact('order'));
    }
}
