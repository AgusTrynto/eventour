<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\OtpCode;
use App\Models\User;
use App\Models\EventOrganizer;
use App\Mail\OtpMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;

class EORegisterController extends Controller
{
    // =========================================================
    // STEP 1 — Tampilkan form register EO
    // =========================================================
    public function showForm()
    {
        return view('auth.EoRegister');
    }

    // =========================================================
    // STEP 2 — Validasi input, simpan ke session, kirim OTP
    // =========================================================
    public function submitForm(Request $request)
    {
        $request->validate([
            'org_name' => ['required', 'string', 'max:255'],
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'unique:users,email'],
            'phone'    => ['required', 'string', 'max:20'],
            'address'  => ['nullable', 'string', 'max:500'],
            'password' => ['required', 'string', 'min:8'],
        ], [
            'org_name.required' => 'Nama organisasi/EO wajib diisi.',
            'name.required'     => 'Nama penanggung jawab wajib diisi.',
            'email.required'    => 'Email wajib diisi.',
            'email.email'       => 'Format email tidak valid.',
            'email.unique'      => 'Email ini sudah terdaftar.',
            'phone.required'    => 'Nomor telepon wajib diisi.',
            'password.required' => 'Password wajib diisi.',
            'password.min'      => 'Password minimal 8 karakter.',
        ]);

        $otp = $this->generateOtp($request->email);

        // Simpan semua data EO ke session, password sudah di-hash
        Session::put('eo_register_pending', [
            'org_name' => $request->org_name,
            'name'     => $request->name,
            'email'    => $request->email,
            'phone'    => $request->phone,
            'address'  => $request->address,
            'password' => Hash::make($request->password),
        ]);

        Mail::to($request->email)->send(new OtpMail($otp, $request->name));

        return redirect()->route('eo.register.otp')
            ->with('success', 'Kode OTP telah dikirim ke ' . $request->email);
    }

    // =========================================================
    // STEP 3 — Tampilkan form verifikasi OTP
    // =========================================================
    public function showOtpForm()
    {
        if (!Session::has('eo_register_pending')) {
            return redirect()->route('eo.register')
                ->with('error', 'Sesi pendaftaran tidak ditemukan. Silakan daftar ulang.');
        }

        $email = Session::get('eo_register_pending.email');

        return view('auth.eo-verify-otp', compact('email'));
    }

    // =========================================================
    // STEP 4 — Verifikasi OTP, buat akun user + EO
    // =========================================================
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => ['required', 'digits:6'],
        ], [
            'otp.required' => 'Kode OTP wajib diisi.',
            'otp.digits'   => 'Kode OTP harus 6 digit angka.',
        ]);

        $pending = Session::get('eo_register_pending');

        if (!$pending) {
            return redirect()->route('eo.register')
                ->with('error', 'Sesi habis. Silakan daftar ulang.');
        }

        $otpRecord = OtpCode::where('email', $pending['email'])->latest()->first();

        if (!$otpRecord) {
            return redirect()->route('eo.register')
                ->with('error', 'Kode OTP tidak ditemukan. Silakan daftar ulang.');
        }

        if ($otpRecord->attempts >= 5) {
            $otpRecord->delete();
            Session::forget('eo_register_pending');

            return redirect()->route('eo.register')
                ->with('error', 'Terlalu banyak percobaan. Silakan daftar ulang.');
        }

        if ($otpRecord->isExpired()) {
            $otpRecord->delete();
            Session::forget('eo_register_pending');

            return redirect()->route('eo.register')
                ->with('error', 'Kode OTP sudah kedaluwarsa. Silakan daftar ulang.');
        }

        $inputOtp  = trim($request->otp);
        $recordOtp = trim($otpRecord->code);

        if ($inputOtp !== $recordOtp) {
            $otpRecord->increment('attempts');
            $sisaCobaan = 5 - $otpRecord->fresh()->attempts;

            return back()->with('otp_error', "Kode OTP salah. Sisa percobaan: {$sisaCobaan}x.");
        }

        // ✅ OTP valid — buat user dengan role 'eo'
        $user = User::create([
            'name'     => $pending['name'],
            'email'    => $pending['email'],
            'password' => $pending['password'],
            'role'     => 'eo',
        ]);

        // Buat data EO terkait (status pending menunggu approval admin)
        EventOrganizer::create([
            'user_id'  => $user->id,
            'org_name' => $pending['org_name'],
            'phone'    => $pending['phone'],
            'address'  => $pending['address'],
            'status'   => 'pending',
        ]);

        $otpRecord->delete();
        Session::forget('eo_register_pending');

        return redirect()->route('login')
            ->with('success', 'Akun EO berhasil dibuat! Tunggu persetujuan admin sebelum bisa membuat event.');
    }

    // =========================================================
    // BONUS — Kirim ulang OTP
    // =========================================================
    public function resendOtp(Request $request)
    {
        $pending = Session::get('eo_register_pending');

        if (!$pending) {
            return redirect()->route('eo.register')
                ->with('error', 'Sesi tidak ditemukan. Silakan daftar ulang.');
        }

        $last = OtpCode::where('email', $pending['email'])->latest()->first();

        if ($last && $last->created_at->diffInSeconds(now()) < 60) {
            $tunggu = 60 - $last->created_at->diffInSeconds(now());

            return back()->with('otp_error', "Tunggu {$tunggu} detik sebelum mengirim ulang.");
        }

        $otp = $this->generateOtp($pending['email']);

        Mail::to($pending['email'])->send(new OtpMail($otp, $pending['name']));

        return back()->with('success', 'Kode OTP baru telah dikirim.');
    }

    // =========================================================
    // PRIVATE — Generate OTP & simpan ke DB
    // =========================================================
    private function generateOtp(string $email): string
    {
        OtpCode::where('email', $email)->delete();

        $otp = (string) random_int(100000, 999999);

        OtpCode::create([
            'email'      => $email,
            'code'       => $otp,
            'expires_at' => now()->addMinutes(10),
        ]);

        return $otp;
    }
}