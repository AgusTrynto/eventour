<?php
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\EORegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LocationController;
use Illuminate\Support\Facades\Route;
Route::get('/', function () {
    return view('welcome');
});
// === AUTH ROUTES ==========================================================================
Route::get('/login', [LoginController::class, 'showForm'])
    ->name('login')
    ->middleware('guest');

Route::post('/login', [LoginController::class, 'submit'])
    ->name('login.submit')
    ->middleware('guest');

Route::post('/logout', [LoginController::class, 'logout'])
    ->name('logout')
    ->middleware('auth');

//===== REGISTER ROUTES =====================================================================
Route::get('/register', [RegisterController::class, 'showForm'])
    ->name('register')
    ->middleware('guest');

Route::post('/register', [RegisterController::class, 'submitForm'])
    ->name('register.submit')
    ->middleware('guest');

Route::get('/register/verify-otp', [RegisterController::class, 'showOtpForm'])
    ->name('register.otp')
    ->middleware('guest');

Route::post('/register/verify-otp', [RegisterController::class, 'verifyOtp'])
    ->name('register.otp.verify')
    ->middleware('guest');

Route::post('/register/resend-otp', [RegisterController::class, 'resendOtp'])
    ->name('register.otp.resend')
    ->middleware('guest');

// === EO REGISTER ROUTES =========================================================
Route::get('/eo-register', [EORegisterController::class, 'showForm'])
    ->name('eo.register')
    ->middleware('guest');
 
Route::post('/eo-register', [EORegisterController::class, 'submitForm'])
    ->name('eo.register.submit')
    ->middleware('guest');
 
Route::get('/eo-register/verify-otp', [EORegisterController::class, 'showOtpForm'])
    ->name('eo.register.otp')
    ->middleware('guest');
 
Route::post('/eo-register/verify-otp', [EORegisterController::class, 'verifyOtp'])
    ->name('eo.register.otp.verify')
    ->middleware('guest');
 
Route::post('/eo-register/resend-otp', [EORegisterController::class, 'resendOtp'])
    ->name('eo.register.otp.resend')
    ->middleware('guest');

// === DASHBOARD =======================================================================
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->name('dashboard')
    ->middleware('auth');

Route::post('/location/save', [LocationController::class, 'save'])
    ->name('location.save')
    ->middleware('auth');