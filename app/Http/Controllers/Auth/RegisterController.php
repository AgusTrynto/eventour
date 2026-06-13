<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\OtpCode;
use App\Models\User;
use App\Mail\OtpMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;

class RegisterController extends Controller
{
    // =========================================================
    // STEP 1 — Tampilkan form register
    // =========================================================
    public function showForm()
    {
        return view('auth.UserRegister');
    }

    // =========================================================
    // STEP 2 — Validasi input, simpan ke session, kirim OTP
    // =========================================================
    public function submitForm(Request $request)
    {
        $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ], [
            'name.required'     => 'Nama lengkap wajib diisi.',
            'email.required'    => 'Email wajib diisi.',
            'email.email'       => 'Format email tidak valid.',
            'email.unique'      => 'Email ini sudah terdaftar.',
            'password.required' => 'Password wajib diisi.',
            'password.min'      => 'Password minimal 8 karakter.',
        ]);

        // Generate dan simpan OTP ke DB
        $otp = $this->generateOtp($request->email);

        // Simpan data form ke session — password sudah di-hash
        Session::put('register_pending', [
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        Mail::to($request->email)->send(new OtpMail($otp, $request->name));

        return redirect()->route('register.otp')
            ->with('success', 'Kode OTP telah dikirim ke ' . $request->email);
    }

    // =========================================================
    // STEP 3 — Tampilkan form verifikasi OTP
    // =========================================================
    public function showOtpForm()
    {
        if (!Session::has('register_pending')) {
            return redirect()->route('register')
                ->withErrors(['error' => 'Sesi pendaftaran tidak ditemukan. Silakan daftar ulang.']);
        }

        $email = Session::get('register_pending.email');

        return view('auth.verify-otp', compact('email'));
    }

    // =========================================================
    // STEP 4 — Verifikasi OTP, buat akun user
    // =========================================================
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => ['required', 'digits:6'],
        ], [
            'otp.required' => 'Kode OTP wajib diisi.',
            'otp.digits'   => 'Kode OTP harus 6 digit angka.',
        ]);

        $pending = Session::get('register_pending');

        if (!$pending) {
            return redirect()->route('register')
                ->withErrors(['error' => 'Sesi habis. Silakan daftar ulang.']);
        }

        $otpRecord = OtpCode::where('email', $pending['email'])->latest()->first();

        // Tidak ada record OTP
        if (!$otpRecord) {
            return redirect()->route('register')
                ->withErrors(['error' => 'Kode OTP tidak ditemukan. Silakan daftar ulang.']);
        }

        // Cek batas percobaan (maks 5x)
        if ($otpRecord->attempts >= 5) {
            $otpRecord->delete();
            Session::forget('register_pending');

            return redirect()->route('register')
                ->withErrors(['error' => 'Terlalu banyak percobaan. Silakan daftar ulang.']);
        }

        // Cek kedaluwarsa
        if ($otpRecord->isExpired()) {
            $otpRecord->delete();
            Session::forget('register_pending');

            return redirect()->route('register')
                ->withErrors(['error' => 'Kode OTP sudah kedaluwarsa. Silakan daftar ulang.']);
        }

        // Cek kecocokan OTP
        if ($request->otp !== $otpRecord->code) {
            $otpRecord->increment('attempts');
            $sisaCobaan = 5 - $otpRecord->attempts;

            return back()->withErrors([
                'otp' => "Kode OTP salah. Sisa percobaan: {$sisaCobaan}x.",
            ]);
        }

        // ✅ OTP valid — buat user
        $user = User::create([
            'name'     => $pending['name'],
            'email'    => $pending['email'],
            'password' => $pending['password'],
        ]);

        // Bersihkan OTP & session
        $otpRecord->delete();
        Session::forget('register_pending');
        return redirect('/login')
            ->with('success', 'Akun berhasil dibuat! Silakan login untuk masuk.');
    }

    // =========================================================
    // BONUS — Kirim ulang OTP
    // =========================================================
    public function resendOtp(Request $request)
    {
        $pending = Session::get('register_pending');

        if (!$pending) {
            return redirect()->route('register')
                ->withErrors(['error' => 'Sesi tidak ditemukan. Silakan daftar ulang.']);
        }

        // Rate limit: cek apakah OTP terakhir dikirim < 60 detik lalu
        $last = OtpCode::where('email', $pending['email'])->latest()->first();

        if ($last && $last->created_at->diffInSeconds(now()) < 60) {
            $tunggu = 60 - $last->created_at->diffInSeconds(now());

            return back()->withErrors([
                'resend' => "Tunggu {$tunggu} detik sebelum mengirim ulang.",
            ]);
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
        // Hapus OTP lama milik email ini
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
