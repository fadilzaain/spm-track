<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InputNilaiController;
use App\Http\Controllers\PencapaianController;
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

    // Input nilai bulanan: semua role boleh MELIHAT, tapi hanya admin dan petugas unit yang boleh MENYIMPAN
    // (petugas unit dibatasi ke pelayanannya sendiri di dalam controller)
    Route::get('/input-nilai', [InputNilaiController::class, 'index'])->name('input.nilai');
    Route::post('/input-nilai', [InputNilaiController::class, 'simpan'])
        ->middleware('role:admin,petugas_unit')
        ->name('input.nilai.simpan');

    // Pencapaian tahunan: aturan aksesnya sama dengan input nilai
    Route::get('/pencapaian', [PencapaianController::class, 'index'])->name('pencapaian');
    Route::post('/pencapaian', [PencapaianController::class, 'simpan'])
        ->middleware('role:admin,petugas_unit')
        ->name('pencapaian.simpan');
});