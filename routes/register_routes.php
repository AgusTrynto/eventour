<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisterController;

// === REGISTER ROUTES ===

// Step 1: Tampil form register
Route::get('/register', [RegisterController::class, 'showForm'])
    ->name('register')
    ->middleware('guest');

// Step 2: Submit form register → kirim OTP ke email
Route::post('/register', [RegisterController::class, 'submitForm'])
    ->name('register.submit')
    ->middleware('guest');

// Step 3: Tampil form verifikasi OTP
Route::get('/register/verify-otp', [RegisterController::class, 'showOtpForm'])
    ->name('register.otp')
    ->middleware('guest');

// Step 4: Submit OTP → buat akun
Route::post('/register/verify-otp', [RegisterController::class, 'verifyOtp'])
    ->name('register.otp.verify')
    ->middleware('guest');

// Kirim ulang OTP
Route::post('/register/resend-otp', [RegisterController::class, 'resendOtp'])
    ->name('register.otp.resend')
    ->middleware('guest');