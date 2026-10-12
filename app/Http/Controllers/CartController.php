<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Event;
use App\Models\Order;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Xendit\Configuration;
use Xendit\Invoice\CreateInvoiceRequest;
use Xendit\Invoice\InvoiceApi;
use Xendit\XenditSdkException;

class CartController extends Controller
{
    public function __construct()
    {
        Configuration::setXenditKey(config('services.xendit.secret_key'));
    }

    // =========================================================
    // Tampilkan halaman keranjang
    // =========================================================
    public function index()
    {
        $user = Auth::user();

        $cartItems = CartItem::with(['event', 'user'])
            ->where('user_id', $user->id)
            ->get();

        $subtotalAll = $cartItems->sum(fn($item) => $item->subtotal);

        $hasPaidItem = $cartItems->contains(fn($item) => ($item->event->price ?? 0) > 0);
        $adminFee    = $hasPaidItem ? Order::ADMIN_FEE : 0;
        $total       = $subtotalAll + $adminFee;

        return view('cart.index', compact('cartItems', 'subtotalAll', 'adminFee', 'total', 'user'));
    }

    // =========================================================
    // Tambah event ke keranjang (AJAX-friendly)
    // =========================================================
    public function store(Request $request, Event $event)
    {
        if ($event->status !== 'approved') {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Event tidak tersedia.'], 422);
            }
            abort(404);
        }

        if ($event->hasEnded()) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Event sudah berakhir.'], 422);
            }
            return back()->with('error', 'Event sudah berakhir.');
        }

        $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:20'],
        ]);

        $user     = Auth::user();
        $quantity = (int) $request->quantity;

        // Validasi kuota tiket event
        if ($event->quota !== null) {
            $remainingQuota = $event->quota - $event->tickets_sold;
            if ($quantity > $remainingQuota) {
                $msg = $remainingQuota <= 0
                    ? 'Tiket untuk event ini sudah habis.'
                    : "Sisa kuota event ini hanya {$remainingQuota} tiket.";
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'message' => $msg], 422);
                }
                return back()->with('error', $msg);
            }
        }

        // Cek batasan kuota person jika policy strict
        $userRemainingSlots = $event->getUserRemainingSlots($user);
        if ($event->ticket_purchase_policy === 'strict' && $event->max_tickets_per_person !== null) {
            if ($userRemainingSlots === 0) {
                $msg = "Anda sudah mencapai batas maksimal {$event->max_tickets_per_person} tiket untuk event ini.";
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'message' => $msg], 422);
                }
                return back()->with('error', $msg);
            }

            if ($quantity > $userRemainingSlots) {
                $msg = "Maksimal {$event->max_tickets_per_person} tiket per orang. Anda hanya bisa membeli {$userRemainingSlots} tiket lagi.";
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'message' => $msg], 422);
                }
                return back()->with('error', $msg);
            }
        }

        // Upsert: jika sudah ada, update quantity; jika belum, buat baru
        CartItem::updateOrCreate(
            ['user_id' => $user->id, 'event_id' => $event->id],
            ['quantity' => $quantity]
        );

        $cartCount = CartItem::where('user_id', $user->id)->count();

        if ($request->expectsJson()) {
            return response()->json([
                'success'    => true,
                'message'    => "'{$event->title}' ditambahkan ke keranjang.",
                'cart_count' => $cartCount,
            ]);
        }

        return back()->with('success', "'{$event->title}' ditambahkan ke keranjang.");
    }

    // =========================================================
    // Update quantity item di keranjang
    // =========================================================
    public function update(Request $request, CartItem $cartItem)
    {
        if ($cartItem->user_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:20'],
        ]);

        $quantity = (int) $request->quantity;
        $event = $cartItem->event;

        if ($event) {
            $user = Auth::user();
            $userRemainingSlots = $event->getUserRemainingSlots($user);

            if ($event->ticket_purchase_policy === 'strict' && $event->max_tickets_per_person !== null) {
                if ($userRemainingSlots === 0 || $quantity > $userRemainingSlots) {
                    if ($request->expectsJson()) {
                        return response()->json([
                            'success' => false,
                            'message' => "Maksimal {$userRemainingSlots} tiket untuk event ini (Strict).",
                        ], 422);
                    }
                    return back()->with('error', "Maksimal {$userRemainingSlots} tiket untuk event ini.");
                }
            }
        }

        $cartItem->update(['quantity' => $quantity]);

        if ($request->expectsJson()) {
            return response()->json([
                'success'  => true,
                'quantity' => $quantity,
                'subtotal' => $cartItem->subtotal,
            ]);
        }

        return back();
    }

    // =========================================================
    // Hapus item dari keranjang
    // =========================================================
    public function destroy(CartItem $cartItem)
    {
        if ($cartItem->user_id !== Auth::id()) {
            abort(403);
        }

        $cartItem->delete();

        return back()->with('success', 'Item dihapus dari keranjang.');
    }

    // =========================================================
    // Checkout semua item di keranjang
    // =========================================================
    public function checkout(Request $request)
    {
        $user = Auth::user();

        // 1. Cek rekening refund
        if (! $user->hasRefundDestination()) {
            session(['profile.redirect_after_update' => route('cart.index', [], false)]);
            return redirect()->route('profile.edit')
                ->with('error', 'Lengkapi data tujuan refund dulu sebelum checkout tiket.');
        }

        $cartItems = CartItem::with('event')
            ->where('user_id', $user->id)
            ->get();

        if ($cartItems->isEmpty()) {
            return redirect()->route('cart.index')
                ->with('error', 'Keranjang kamu kosong.');
        }

        // 2. Validasi ulang setiap item & kumpulkan data attendee
        $itemAttendeeDetails = [];

        foreach ($cartItems as $item) {
            $event = $item->event;

            if (!$event || $event->status !== 'approved') {
                return redirect()->route('cart.index')
                    ->with('error', "Event '{$event->title}' tidak lagi tersedia.");
            }

            if ($event->hasEnded()) {
                return redirect()->route('cart.index')
                    ->with('error', "Event '{$event->title}' sudah berakhir.");
            }

            if ($event->quota !== null) {
                $remaining = $event->quota - $event->tickets_sold;
                if ($item->quantity > $remaining) {
                    return redirect()->route('cart.index')
                        ->with('error', "Kuota tidak cukup untuk event '{$event->title}'.");
                }
            }

            $userRemainingSlots = $event->getUserRemainingSlots($user);

            // Cek kebijakan STRICT
            if ($event->ticket_purchase_policy === 'strict' && $event->max_tickets_per_person !== null) {
                if ($userRemainingSlots === 0) {
                    return redirect()->route('cart.index')
                        ->with('error', "Anda sudah mencapai batas maksimal {$event->max_tickets_per_person} tiket untuk event '{$event->title}'.");
                }
                if ($item->quantity > $userRemainingSlots) {
                    return redirect()->route('cart.index')
                        ->with('error', "Untuk event '{$event->title}', Anda hanya bisa membeli maksimal {$userRemainingSlots} tiket lagi.");
                }
            }

            // Jika user memesan tiket untuk diri sendiri (ada slot miliknya),
            // pastikan user memiliki NIK pada profil jika event memiliki max_tickets_per_person
            $usesOwnSlot = ($userRemainingSlots === null || $userRemainingSlots > 0);
            if ($event->max_tickets_per_person !== null && $usesOwnSlot && empty($user->nik)) {
                session(['profile.redirect_after_update' => route('cart.index', [], false)]);
                return redirect()->route('profile.edit')
                    ->with('error', 'Silakan lengkapi NIK pada profil Anda terlebih dahulu untuk verifikasi tiket.');
            }

            // Cek kebutuhan data teman (attendees)
            $requiredAttendeeCount = $event->getRequiredAttendeeCount($user, $item->quantity);
            $attendeeDetails = null;

            if ($requiredAttendeeCount > 0) {
                $rawNames = $request->input("attendees.{$item->id}.name", []);
                $rawNiks  = $request->input("attendees.{$item->id}.nik", []);

                if (!is_array($rawNames) || !is_array($rawNiks) || count($rawNames) < $requiredAttendeeCount || count($rawNiks) < $requiredAttendeeCount) {
                    return redirect()->route('cart.index')
                        ->with('error', "Harap lengkapi data {$requiredAttendeeCount} pemegang tiket tambahan untuk event '{$event->title}'.");
                }

                $validatedAttendees = [];
                $submittedNiks = [];

                for ($i = 0; $i < $requiredAttendeeCount; $i++) {
                    $name = trim((string) ($rawNames[$i] ?? ''));
                    $nik  = trim((string) ($rawNiks[$i] ?? ''));

                    if ($name === '' || $nik === '') {
                        return redirect()->route('cart.index')
                            ->with('error', "Nama dan NIK pemegang tiket #".($i + 1)." untuk event '{$event->title}' wajib diisi.");
                    }

                    if ($user->nik && $nik === $user->nik) {
                        return redirect()->route('cart.index')
                            ->with('error', "Untuk event '{$event->title}', NIK Anda sendiri sudah melebihi limit. Harap gunakan NIK orang lain.");
                    }

                    if (in_array($nik, $submittedNiks, true)) {
                        return redirect()->route('cart.index')
                            ->with('error', "NIK {$nik} diinput lebih dari sekali untuk event '{$event->title}'. Gunakan NIK yang berbeda untuk setiap orang.");
                    }
                    $submittedNiks[] = $nik;

                    // Cek batas tiket NIK ini di event ini
                    $nikCount = Ticket::where('event_id', $event->id)
                        ->where('attendee_nik', $nik)
                        ->whereIn('status', ['valid', 'used'])
                        ->count();

                    if ($event->max_tickets_per_person !== null && $nikCount >= $event->max_tickets_per_person) {
                        return redirect()->route('cart.index')
                            ->with('error', "NIK {$nik} sudah mencapai batas maksimal {$event->max_tickets_per_person} tiket di event '{$event->title}'.");
                    }

                    $validatedAttendees[] = [
                        'name' => $name,
                        'nik'  => $nik,
                    ];
                }

                $attendeeDetails = $validatedAttendees;
            }

            $itemAttendeeDetails[$item->id] = $attendeeDetails;
        }

        // 3. Hitung total & admin fee
        $paidItems   = $cartItems->filter(fn($i) => ($i->event->price ?? 0) > 0);
        $subtotalAll = $cartItems->sum(fn($i) => $i->subtotal);
        $adminFee    = $paidItems->isNotEmpty() ? Order::ADMIN_FEE : 0;
        $totalAmount = $subtotalAll + $adminFee;

        $cartExternalId = 'CART-' . Str::upper(Str::random(10)) . '-' . time();
        $createdOrders  = collect();

        // 4. Buat Order untuk setiap cart item
        $firstPaidOrder = true;
        foreach ($cartItems as $item) {
            $event    = $item->event;
            $subtotal = $event->price * $item->quantity;
            $isPaid   = $subtotal > 0;

            // Admin fee hanya 1x per transaksi (disimpan pada paid order pertama)
            $orderAdminFee = 0;
            if ($isPaid && $firstPaidOrder) {
                $orderAdminFee  = Order::ADMIN_FEE;
                $firstPaidOrder = false;
            }

            $order = Order::create([
                'user_id'          => $user->id,
                'event_id'         => $event->id,
                'quantity'         => $item->quantity,
                'attendee_details' => $itemAttendeeDetails[$item->id] ?? null,
                'unit_price'       => $event->price,
                'subtotal_amount'  => $subtotal,
                'admin_fee'        => $orderAdminFee,
                'total_amount'     => $subtotal + $orderAdminFee,
                'payment_status'   => $isPaid ? 'pending' : 'paid',
                'payment_method'   => 'xendit',
                'external_id'      => $cartExternalId,
            ]);

            $createdOrders->push($order);
        }

        // 5. Generate tiket untuk free items langsung
        foreach ($createdOrders->filter(fn($o) => $o->payment_status === 'paid') as $order) {
            $order->update(['paid_at' => now()]);
            Ticket::generateForOrder($order);
        }

        // 6. Jika ada item berbayar, buat 1 invoice Xendit gabungan
        $paidOrders = $createdOrders->filter(fn($o) => $o->payment_status === 'pending');

        if ($paidOrders->isNotEmpty()) {
            try {
                $apiInstance = new InvoiceApi;

                $description = $paidOrders->map(fn($o) => "{$o->event->title} x{$o->quantity}")->join(', ');

                $createInvoiceRequest = new CreateInvoiceRequest([
                    'external_id'          => $cartExternalId,
                    'amount'               => (int) $totalAmount,
                    'payer_email'          => $user->email,
                    'description'          => "Tiket: {$description}",
                    'invoice_duration'     => 3600,
                    'currency'             => 'IDR',
                    'success_redirect_url' => route('cart.success', $cartExternalId),
                    'failure_redirect_url' => route('cart.index'),
                ]);

                $invoice = $apiInstance->createInvoice($createInvoiceRequest);

                // Simpan xendit invoice ID & URL ke semua order yang dibayar
                $paidOrders->each(fn($order) => $order->update([
                    'xendit_invoice_id'  => $invoice->getId(),
                    'xendit_invoice_url' => $invoice->getInvoiceUrl(),
                ]));

                // Bersihkan keranjang belanja
                CartItem::where('user_id', $user->id)->delete();

                return redirect($invoice->getInvoiceUrl());
            } catch (XenditSdkException $e) {
                Log::error('Xendit cart invoice creation failed: ' . $e->getMessage(), (array) ($e->getFullError() ?? []));

                // Rollback orders jika invoice Xendit gagal dibuat
                $createdOrders->each->delete();

                return redirect()->route('cart.index')
                    ->with('error', 'Gagal membuat invoice pembayaran Xendit. Coba lagi.');
            }
        }

        // Semua tiket gratis
        CartItem::where('user_id', $user->id)->delete();

        return redirect()->route('cart.success', $cartExternalId)
            ->with('success', 'Semua tiket gratis berhasil diklaim!');
    }

    // =========================================================
    // Halaman sukses cart checkout
    // =========================================================
    public function cartSuccess(string $externalId)
    {
        $user = Auth::user();

        // Jika masih ada order yang pending, sync status dengan Xendit
        $pendingOrders = Order::where('external_id', $externalId)
            ->where('user_id', $user->id)
            ->where('payment_status', 'pending')
            ->get();

        if ($pendingOrders->isNotEmpty()) {
            $sampleOrder = $pendingOrders->first();
            if ($sampleOrder->xendit_invoice_id) {
                try {
                    $invoice = (new InvoiceApi)->getInvoiceById($sampleOrder->xendit_invoice_id);
                    $status  = $invoice->getStatus();

                    if (in_array($status, ['PAID', 'SETTLED'], true)) {
                        foreach ($pendingOrders as $order) {
                            $order->update([
                                'payment_status' => 'paid',
                                'paid_at'        => now(),
                            ]);
                            Ticket::generateForOrder($order);
                        }
                    }
                } catch (XenditSdkException $e) {
                    Log::warning('Xendit invoice status check failed (cart success): ' . $e->getMessage());
                }
            }
        }

        $orders = Order::with('event')
            ->where('external_id', $externalId)
            ->where('user_id', $user->id)
            ->get();

        if ($orders->isEmpty()) {
            abort(404);
        }

        $totalAmount = $orders->sum('total_amount');

        return view('cart.success', compact('orders', 'totalAmount', 'externalId'));
    }
}
