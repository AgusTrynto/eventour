<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminRefundController extends Controller
{
    // =========================================================
    // Refund SEMUA order paid pada sebuah event sekaligus
    // (dipakai saat event terbukti palsu / dibatalkan)
    // =========================================================
    public function refundEvent(Request $request, Event $event)
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        if ($event->payout) {
            return back()->with('error', 'Dana event ini sudah dicairkan ke EO, tidak bisa direfund otomatis. Hubungi tim finance.');
        }

        $orders = Order::where('event_id', $event->id)
            ->where('payment_status', 'paid')
            ->get();

        if ($orders->isEmpty()) {
            return back()->with('error', 'Tidak ada transaksi yang bisa direfund pada event ini.');
        }

        DB::transaction(function () use ($orders, $request, $event) {
            foreach ($orders as $order) {
                $order->update([
                    'payment_status' => 'refunded',
                    'refunded_at'    => now(),
                    'refund_reason'  => $request->reason,
                ]);
                // TODO: integrasi payment gateway untuk transfer balik otomatis,
                // atau proses manual oleh tim finance berdasarkan data ini.
            }

            // Event otomatis ditolak/dinonaktifkan agar tidak tampil lagi di map
            $event->update([
                'status'        => 'rejected',
                'reject_reason' => 'Event dibatalkan & seluruh dana direfund: ' . $request->reason,
            ]);
        });

        return back()->with('success', "Berhasil refund {$orders->count()} transaksi untuk event \"{$event->title}\".");
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

        $order->update([
            'payment_status' => 'refunded',
            'refunded_at'    => now(),
            'refund_reason'  => $request->reason,
        ]);

        return back()->with('success', 'Order berhasil direfund.');
    }
}