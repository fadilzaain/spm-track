<?php

use App\Http\Controllers\Auth\DaftarController;
use App\Http\Controllers\Auth\GantiPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InputNilaiController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\PencapaianController;
use App\Http\Controllers\PenggunaController;
use Illuminate\Support\Facades\Route;

// Halaman utama langsung diarahkan ke dashboard
Route::redirect('/', '/dashboard');

// Hanya untuk yang BELUM login
Route::middleware('guest')->group(function () {
    // /login dan /daftar menampilkan halaman yang sama (dua tab), beda di tab yang terbuka dulu
    Route::get('/login', [LoginController::class, 'tampilkanForm'])->name('login');
    Route::get('/daftar', [LoginController::class, 'tampilkanForm'])->name('daftar');

    // Dibatasi 10 percobaan per menit untuk mencegah tebak password
    Route::post('/login', [LoginController::class, 'proses'])
        ->middleware('throttle:10,1')
        ->name('login.proses');

    // Pendaftaran dibatasi 5 kali per 10 menit per alamat IP supaya tidak dibanjiri akun palsu
    Route::post('/daftar', [DaftarController::class, 'proses'])
        ->middleware('throttle:5,10')
        ->name('daftar.proses');
});

// Hanya untuk yang SUDAH login dan akunnya aktif (akun yang dinonaktifkan admin langsung dikeluarkan).
// "wajib.ganti": akun dengan password sementara dari admin hanya bisa membuka Ganti Password dan Keluar.
Route::middleware(['auth', 'aktif', 'wajib.ganti'])->group(function () {
    Route::post('/logout', [LoginController::class, 'keluar'])->name('logout');

    // Ganti password sendiri: semua role. Dibatasi 6 percobaan per menit supaya password lama tidak bisa ditebak.
    Route::get('/akun/password', [GantiPasswordController::class, 'form'])->name('akun.password');
    Route::put('/akun/password', [GantiPasswordController::class, 'simpan'])
        ->middleware('throttle:6,1')
        ->name('akun.password.simpan');

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

    // Laporan rekap: semua role boleh melihat dan mengunduh. Ekspor Excel mengikuti filter (?tahun=&layanan=)
    Route::get('/laporan', [LaporanController::class, 'index'])->name('laporan');
    Route::get('/laporan/ekspor', [LaporanController::class, 'ekspor'])->name('laporan.ekspor');

    // Kelola pengguna: khusus admin (setujui pendaftar baru, ubah role, nonaktifkan akun)
    Route::middleware('role:admin')->group(function () {
        Route::get('/pengguna', [PenggunaController::class, 'index'])->name('pengguna');
        Route::patch('/pengguna/{pengguna}', [PenggunaController::class, 'perbarui'])->name('pengguna.perbarui');
    });
});