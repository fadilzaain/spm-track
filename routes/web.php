<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

// Halaman utama langsung diarahkan ke dashboard
Route::redirect('/', '/dashboard');

// Hanya untuk yang BELUM login
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'tampilkanForm'])->name('login');

    // Dibatasi 10 percobaan per menit untuk mencegah tebak password
    Route::post('/login', [LoginController::class, 'proses'])
        ->middleware('throttle:10,1')
        ->name('login.proses');
});

// Hanya untuk yang SUDAH login
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'keluar'])->name('logout');

    // Dashboard SPM (rekap kelengkapan pengisian). Filter lewat ?tahun=&bulan=&layanan=
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});