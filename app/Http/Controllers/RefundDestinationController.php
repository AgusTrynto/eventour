<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RefundDestinationController extends Controller
{
    public function store(Request $request, Order $order)
    {
        if ($order->user_id !== Auth::id()) {
            abort(403);
        }

        if ($order->payment_status !== 'refund_manual_pending') {
            return back()->with('error', 'Order ini tidak sedang menunggu data refund manual.');
        }

        $data = $request->validate([
            'refund_destination_type' => ['required', 'in:bank,ewallet'],
            'refund_destination_provider' => ['required', 'string', 'max:50'],
            'refund_destination_account_number' => ['required', 'string', 'max:50'],
            'refund_destination_account_name' => ['required', 'string', 'max:255'],
        ], [
            'refund_destination_type.required' => 'Jenis tujuan refund wajib dipilih.',
            'refund_destination_provider.required' => 'Nama bank/e-wallet wajib diisi.',
            'refund_destination_account_number.required' => 'Nomor rekening/e-wallet wajib diisi.',
            'refund_destination_account_name.required' => 'Nama pemilik rekening/e-wallet wajib diisi.',
        ]);

        $order->update([
            ...$data,
            'payment_status' => 'refund_manual_processing',
            'refund_destination_submitted_at' => now(),
        ]);

        return back()->with('success', 'Data refund berhasil dikirim. Admin akan memproses pengembalian dana.');
    }
}
