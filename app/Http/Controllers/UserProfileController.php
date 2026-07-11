<?php

namespace App\Http\Controllers;

use App\Support\XenditPayoutChannels;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UserProfileController extends Controller
{
    public function edit()
    {
        return view('user.profile', [
            'user' => Auth::user(),
            'refundChannels' => XenditPayoutChannels::refundChannels(),
        ]);
    }

    public function updateRefundDestination(Request $request)
    {
        $channels = XenditPayoutChannels::flatRefundChannels();

        $data = $request->validate([
            'refund_destination_channel_code' => ['required', Rule::in(array_keys($channels))],
            'refund_destination_account_number' => ['required', 'string', 'max:50'],
            'refund_destination_account_name' => ['required', 'string', 'max:255'],
        ], [
            'refund_destination_channel_code.required' => 'Bank atau e-wallet tujuan refund wajib dipilih.',
            'refund_destination_channel_code.in' => 'Bank atau e-wallet tujuan refund tidak valid.',
            'refund_destination_account_number.required' => 'Nomor rekening/e-wallet wajib diisi.',
            'refund_destination_account_name.required' => 'Nama pemilik rekening/e-wallet wajib diisi.',
        ]);

        $selectedChannel = $channels[$data['refund_destination_channel_code']];

        Auth::user()->update([
            'refund_destination_type' => $selectedChannel['type'],
            'refund_destination_provider' => $selectedChannel['label'],
            'refund_destination_channel_code' => $data['refund_destination_channel_code'],
            'refund_destination_account_number' => $data['refund_destination_account_number'],
            'refund_destination_account_name' => $data['refund_destination_account_name'],
            'refund_destination_updated_at' => now(),
        ]);

        $redirectAfterUpdate = session()->pull('profile.redirect_after_update');

        if (is_string($redirectAfterUpdate) && str_starts_with($redirectAfterUpdate, '/')) {
            return redirect($redirectAfterUpdate)
                ->with('success', 'Data tujuan refund berhasil disimpan. Kamu bisa lanjut beli tiket.');
        }

        return redirect()->route('profile.edit')
            ->with('success', 'Data tujuan refund berhasil disimpan.');
    }
}
