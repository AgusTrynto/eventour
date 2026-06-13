<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;

// === AUTH ROUTES ===
Route::get('/login', [LoginController::class, 'showForm'])
    ->name('login')
    ->middleware('guest');

Route::post('/login', [LoginController::class, 'submit'])
    ->name('login.submit')
    ->middleware('guest');

Route::post('/logout', [LoginController::class, 'logout'])
    ->name('logout')
    ->middleware('auth');

// === DASHBOARD ===
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->name('dashboard')
    ->middleware('auth');