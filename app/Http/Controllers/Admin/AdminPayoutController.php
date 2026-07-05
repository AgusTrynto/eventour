<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Order;
use App\Models\Payout;
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
        $pendingPayouts = Payout::where('status', 'pending')
            ->with(['event', 'organizer.user'])
            ->latest('requested_at')
            ->get();

        $processingPayouts = Payout::where('status', 'processing')
            ->with(['event', 'organizer.user'])
            ->latest()
            ->get();

        $completedPayouts = Payout::where('status', 'completed')
            ->with(['event', 'organizer.user'])
            ->latest()
            ->get();

        $rejectedPayouts = Payout::where('status', 'rejected')
            ->with(['event', 'organizer.user'])
            ->latest('reviewed_at')
            ->get();

        return view('admin.payouts', compact('pendingPayouts', 'processingPayouts', 'completedPayouts', 'rejectedPayouts'));
    }

    // =========================================================
    // Admin buat payout request untuk sebuah event
    // (menghitung total dana, opsional potong fee platform)
    // =========================================================
    public function create(Event $event)
    {
        if ($event->payouts()->whereIn('status', ['pending', 'processing', 'completed'])->exists()) {
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

    public function approve(Payout $payout)
    {
        if ($payout->status !== 'pending') {
            return back()->with('error', 'Pengajuan ini sudah direview.');
        }

        $payout->update([
            'status' => 'processing',
            'reviewed_at' => now(),
            'admin_note' => null,
        ]);

        return back()->with('success', 'Pengajuan pencairan disetujui. Silakan proses transfer.');
    }

    public function reject(Request $request, Payout $payout)
    {
        if ($payout->status !== 'pending') {
            return back()->with('error', 'Pengajuan ini sudah direview.');
        }

        $request->validate([
            'admin_note' => ['required', 'string', 'min:5', 'max:500'],
        ], [
            'admin_note.required' => 'Alasan penolakan wajib diisi.',
            'admin_note.min' => 'Alasan penolakan minimal 5 karakter.',
        ]);

        $payout->update([
            'status' => 'rejected',
            'reviewed_at' => now(),
            'admin_note' => $request->admin_note,
        ]);

        return back()->with('success', 'Pengajuan pencairan ditolak.');
    }

    // =========================================================
    // Admin tandai sudah transfer manual (upload bukti)
    // =========================================================
    public function complete(Request $request, Payout $payout)
    {
        if ($payout->status !== 'processing') {
            return back()->with('error', 'Pengajuan harus disetujui sebelum ditandai selesai transfer.');
        }

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
