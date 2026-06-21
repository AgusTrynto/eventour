<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Payout;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminPayoutController extends Controller
{
    // =========================================================
    // List event yang sudah selesai & berisi dana escrow,
    // tapi belum dicairkan ke EO
    // =========================================================
    public function index()
    {
        // Event yang sudah lewat tanggalnya, approved, dan punya order paid,
        // tapi belum punya payout
        $readyForPayout = Event::where('status', 'approved')
            ->where('end_date', '<', now())
            ->whereDoesntHave('payout')
            ->with('organizer.user')
            ->get()
            ->filter(fn ($event) => $event->escrow_amount > 0);

        $processingPayouts = Payout::where('status', 'pending')
            ->orWhere('status', 'processing')
            ->with('event', 'organizer.user')
            ->latest()
            ->get();

        $completedPayouts = Payout::where('status', 'completed')
            ->with('event', 'organizer.user')
            ->latest()
            ->get();

        return view('admin.payouts', compact('readyForPayout', 'processingPayouts', 'completedPayouts'));
    }

    // =========================================================
    // Admin buat payout request untuk sebuah event
    // (menghitung total dana, opsional potong fee platform)
    // =========================================================
    public function create(Event $event)
    {
        if ($event->payout) {
            return back()->with('error', 'Event ini sudah memiliki payout.');
        }

        $organizer = $event->organizer;

        if (!$organizer->bank_account_number) {
            return back()->with('error', 'EO belum melengkapi data rekening bank.');
        }

        $gross = $event->escrow_amount;

        if ($gross <= 0) {
            return back()->with('error', 'Tidak ada dana untuk dicairkan pada event ini.');
        }

        $feePercent = 5; // contoh: platform ambil 5%, sesuaikan kebijakan
        $fee   = round($gross * ($feePercent / 100), 2);
        $net   = $gross - $fee;

        Payout::create([
            'event_id'            => $event->id,
            'event_organizer_id'  => $organizer->id,
            'gross_amount'        => $gross,
            'platform_fee'        => $fee,
            'net_amount'          => $net,
            'status'              => 'pending',
        ]);

        return redirect()->route('admin.payouts.index')
            ->with('success', "Payout untuk \"{$event->title}\" berhasil dibuat. Silakan proses transfer.");
    }

    // =========================================================
    // Admin tandai sudah transfer manual (upload bukti)
    // =========================================================
    public function complete(Request $request, Payout $payout)
    {
        $request->validate([
            'transfer_proof' => ['required', 'image', 'max:4096'],
            'admin_note'     => ['nullable', 'string', 'max:500'],
        ]);

        $path = $request->file('transfer_proof')->store('payout-proofs', 'public');

        DB::transaction(function () use ($payout, $path, $request) {
            $payout->update([
                'status'         => 'completed',
                'transfer_proof' => $path,
                'admin_note'     => $request->admin_note,
                'processed_at'   => now(),
            ]);

            // Tandai semua order terkait event ini sebagai 'disbursed'
            Order::where('event_id', $payout->event_id)
                ->where('payment_status', 'paid')
                ->update(['payment_status' => 'disbursed']);
        });

        return back()->with('success', 'Payout berhasil ditandai selesai.');
    }
}