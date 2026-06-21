<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\EORegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\EO\EODashboardController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventMapController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminEOController;
use App\Http\Controllers\Admin\AdminEventController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminPayoutController;
use App\Http\Controllers\Admin\AdminRefundController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\XenditWebhookController;


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

Route::get('/events/nearby', [EventMapController::class, 'nearby'])
    ->name('events.nearby');

Route::get('/events/{event}', [EventController::class, 'show'])
    ->name('events.show')
    ->middleware('auth');

// ── Checkout (perlu login) ──────────────────────────
Route::middleware('auth')->group(function () {
    Route::get('/checkout/{event}', [CheckoutController::class, 'show'])
        ->name('checkout.show');

    Route::post('/checkout/{event}', [CheckoutController::class, 'store'])
        ->name('checkout.store');

    Route::get('/checkout/order/{order}/success', [CheckoutController::class, 'success'])
        ->name('checkout.success');

    Route::get('/checkout/order/{order}/failed', [CheckoutController::class, 'failed'])
        ->name('checkout.failed');
});

// ── Webhook Xendit (TANPA middleware auth/csrf — dipanggil server Xendit) ──
Route::post('/webhooks/xendit', [XenditWebhookController::class, 'handle'])
    ->name('webhooks.xendit');

// === EO DASHBOARD ROUTES ===
// middleware: auth (sudah login) + eo (role harus eo)

Route::middleware(['auth', 'eo'])->prefix('eo')->group(function () {

    Route::get('/dashboard', [EODashboardController::class, 'index'])
        ->name('eo.dashboard');

    Route::get('/events/create', [EODashboardController::class, 'createEvent'])
        ->name('eo.events.create');

    Route::post('/events', [EODashboardController::class, 'storeEvent'])
        ->name('eo.events.store');
});


Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {

    Route::get('/dashboard', [AdminDashboardController::class, 'index'])
        ->name('admin.dashboard');

    // ── EO ─────────────────────────────────────────
    Route::get('/eo', [AdminEOController::class, 'index'])
        ->name('admin.eo.index');

    Route::post('/eo/{organizer}/approve', [AdminEOController::class, 'approve'])
        ->name('admin.eo.approve');

    Route::post('/eo/{organizer}/reject', [AdminEOController::class, 'reject'])
        ->name('admin.eo.reject');

        
    // (checkout & webhook moved to public routes)
        
    // ── Events ──────────────────────────────────────
    Route::get('/events', [AdminEventController::class, 'index'])
        ->name('admin.events.index');

    Route::post('/events/{event}/approve', [AdminEventController::class, 'approve'])
        ->name('admin.events.approve');

    Route::post('/events/{event}/reject', [AdminEventController::class, 'reject'])
        ->name('admin.events.reject');

    // ── Payout (pencairan dana ke EO) ────────────────
    Route::get('/payouts', [AdminPayoutController::class, 'index'])
        ->name('admin.payouts.index');

    Route::post('/payouts/{event}/create', [AdminPayoutController::class, 'create'])
        ->name('admin.payouts.create');

    Route::post('/payouts/{payout}/complete', [AdminPayoutController::class, 'complete'])
        ->name('admin.payouts.complete');

    // ── Refund (kembalikan dana ke user) ─────────────
    Route::post('/events/{event}/refund', [AdminRefundController::class, 'refundEvent'])
        ->name('admin.events.refund');

    Route::post('/orders/{order}/refund', [AdminRefundController::class, 'refundOrder'])
        ->name('admin.orders.refund');

});
