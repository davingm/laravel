<?php

use Davingm\Auth\Http\Controllers\AuthenticatedSessionController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function (): void {
    Route::middleware('guest')->group(function (): void {
        Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
        Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
    });

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->middleware('auth')->name('logout');

    Route::view('/dashboard', 'davingm-auth::dashboard')->middleware('auth')->name('dashboard');
});
