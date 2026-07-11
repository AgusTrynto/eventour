<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Order;
use App\Services\RecommendationFeatureSnapshotService;
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

        if ($event->payouts()->whereIn('status', ['pending', 'processing', 'completed'])->exists()) {
            return back()->with('error', 'Event ini masih memiliki payout aktif. Tolak atau selesaikan payout dulu sebelum refund.');
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

                $order->tickets()->update([
                    'status'        => 'cancelled',
                    'checked_in_at' => null,
                    'checked_in_by' => null,
                ]);

                app(RecommendationFeatureSnapshotService::class)->recordPurchasedOrder($order->refresh());
            }

            // Event otomatis ditolak/dinonaktifkan agar tidak tampil lagi di map
            $event->update([
                'status'        => 'rejected',
                'reject_reason' => 'Event dibatalkan & seluruh dana direfund: ' . $request->reason,
            ]);
        });

        return back()->with('success', "Simulasi refund berhasil untuk {$orders->count()} transaksi pada event \"{$event->title}\". Tiket terkait sudah dibatalkan.");
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

        DB::transaction(function () use ($order, $request) {
            $order->update([
                'payment_status' => 'refunded',
                'refunded_at'    => now(),
                'refund_reason'  => $request->reason,
            ]);

            $order->tickets()->update([
                'status'        => 'cancelled',
                'checked_in_at' => null,
                'checked_in_by' => null,
            ]);

            app(RecommendationFeatureSnapshotService::class)->recordPurchasedOrder($order->refresh());
        });

        return back()->with('success', 'Simulasi refund order berhasil. Tiket terkait sudah dibatalkan.');
    }
}
