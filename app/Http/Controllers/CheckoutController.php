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

        $user = Auth::user();
        $userOwnedTicketsCount = 0;
        if ($event->max_tickets_per_person) {
            $userOwnedTicketsCount = Ticket::where('event_id', $event->id)
                ->where('attendee_nik', $user->nik)
                ->whereIn('status', ['valid', 'used'])
                ->count();
        }
        $userRemainingSlots = $event->max_tickets_per_person ? max(0, $event->max_tickets_per_person - $userOwnedTicketsCount) : null;

        return view('checkout.show', compact('event', 'userRemainingSlots'));
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

        $maxPerPerson = $event->max_tickets_per_person ?? 10;
        $effectiveMax = $maxPerPerson;

        if ($event->ticket_purchase_policy === 'flexible' && $event->max_tickets_per_person !== null) {
            $effectiveMax = max(20, $event->max_tickets_per_person * 3);
        } elseif ($event->ticket_purchase_policy === 'flexible') {
            $effectiveMax = 20;
        }

        $request->validate([
            'quantity' => [
                'required',
                'integer',
                'min:1',
                'max:' . $effectiveMax,
            ],
        ]);

        $user = Auth::user();
        $quantity = (int) $request->quantity;

        $userOwnedTicketsCount = 0;
        if ($event->max_tickets_per_person !== null) {
            $userOwnedTicketsCount = Ticket::where('event_id', $event->id)
                ->where('attendee_nik', $user->nik)
                ->whereIn('status', ['valid', 'used'])
                ->count();
        }
        $userRemainingSlots = $event->max_tickets_per_person !== null ? max(0, $event->max_tickets_per_person - $userOwnedTicketsCount) : null;

        $subtotal = $event->price * $quantity;
        $adminFee = Order::adminFeeForSubtotal((float) $subtotal);
        $total = $subtotal + $adminFee;

        if ($event->quota !== null) {
            $sold = $event->tickets_sold;
            if (($sold + $quantity) > $event->quota) {
                return back()->with('error', 'Kuota tiket tidak mencukupi.');
            }
        }

        if ($event->ticket_purchase_policy === 'strict' && $event->max_tickets_per_person !== null) {
            if ($quantity > $userRemainingSlots) {
                if ($userRemainingSlots === 0) {
                    return back()->with('error', "Kamu sudah mencapai batas maksimal {$event->max_tickets_per_person} tiket untuk event ini.");
                }
                return back()->with('error', "Maksimal {$event->max_tickets_per_person} tiket per orang. Kamu hanya bisa membeli {$userRemainingSlots} tiket lagi.");
            }
        }

        $attendeeDetails = [];
        if ($event->ticket_purchase_policy === 'flexible' && $event->max_tickets_per_person !== null && ($userRemainingSlots === 0 || $quantity > $userRemainingSlots)) {
            if ($userRemainingSlots === 0) {
                // Semua tiket harus diisi data teman
                $requiredAttendeeCount = (int) ceil($quantity / $event->max_tickets_per_person);
            } else {
                // Hanya tiket yang melebihi sisa slot yang butuh data teman
                $excessTickets = $quantity - $userRemainingSlots;
                $requiredAttendeeCount = (int) ceil($excessTickets / $event->max_tickets_per_person);
            }
            $request->validate([
                'attendee_name'     => ['required', 'array', 'min:' . $requiredAttendeeCount, 'max:' . $requiredAttendeeCount],
                'attendee_name.*'   => ['required', 'string', 'max:255'],
                'attendee_nik'      => ['required', 'array', 'min:' . $requiredAttendeeCount, 'max:' . $requiredAttendeeCount],
                'attendee_nik.*'    => ['required', 'string', 'max:30'],
            ], [
                'attendee_name.required'    => 'Data nama pemegang tiket tambahan wajib diisi.',
                'attendee_name.*.required'  => 'Nama pemegang tiket tambahan wajib diisi.',
                'attendee_nik.required'     => 'Data NIK pemegang tiket tambahan wajib diisi.',
                'attendee_nik.*.required'   => 'NIK pemegang tiket tambahan wajib diisi.',
            ]);

            $names = $request->attendee_name;
            $niks  = $request->attendee_nik;
            
            // Validasi NIK agar tidak ada duplikasi dan tidak pernah didaftarkan di event ini sebelumnya (atau sudah melewati batas)
            foreach ($niks as $i => $nik) {
                if ($nik === $user->nik) {
                    return back()->with('error', 'NIK Anda sendiri sudah melebihi limit, gunakan NIK lain.');
                }
                // Pastikan NIK ini belum mencapai limit di event ini
                $nikTicketCount = Ticket::where('event_id', $event->id)
                    ->where('attendee_nik', $nik)
                    ->whereIn('status', ['valid', 'used'])
                    ->count();
                if ($nikTicketCount >= $event->max_tickets_per_person) {
                    return back()->with('error', "NIK {$nik} sudah mencapai batas maksimal {$event->max_tickets_per_person} tiket di event ini.");
                }

                $attendeeDetails[] = [
                    'name' => $names[$i],
                    'nik'  => $nik,
                ];
            }
        }

        $externalId = 'ORDER-'.Str::upper(Str::random(10)).'-'.time();

        $order = Order::create([
            'user_id'          => $user->id,
            'event_id'         => $event->id,
            'quantity'         => $quantity,
            'attendee_details' => !empty($attendeeDetails) ? $attendeeDetails : null,
            'unit_price'       => $event->price,
            'subtotal_amount'  => $subtotal,
            'admin_fee'        => $adminFee,
            'total_amount'     => $total,
            'payment_status'   => 'pending',
            'payment_method'   => 'xendit',
            'external_id'      => $externalId,
        ]);

        app(RecommendationFeatureSnapshotService::class)->recordPurchasedOrder($order);

        // ── Free event: tidak perlu bayar, langsung tandai paid ──
        if ($subtotal <= 0) {
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
        Ticket::generateForOrder($order);
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
