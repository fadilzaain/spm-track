<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\JenisPelayanan;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class LoginController extends Controller
{
    /**
     * Satu halaman untuk Masuk dan Daftar (dua tab). Rute /login dan /daftar memakai method ini,
     * bedanya hanya tab yang terbuka lebih dulu.
     */
    public function tampilkanForm(Request $request): View
    {
        $galat = $request->session()->get('errors');

        // Kalau login baru saja gagal, tetap di tab Masuk. Kalau pendaftaran gagal, buka tab Daftar.
        $galatMasuk = $galat && $galat->getBag('default')->any();
        $tab = ! $galatMasuk && ($request->routeIs('daftar') || ($galat && $galat->hasBag('daftar')))
            ? 'daftar'
            : 'masuk';

        return view('auth.akses', [
            'tab' => $tab,
            'daftarLayanan' => JenisPelayanan::orderBy('id')->pluck('nama_pelayanan', 'id')->all(),
        ]);
    }

    // Proses login pakai username dan password
    public function proses(Request $request): RedirectResponse
    {
        $kredensial = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'username.required' => 'Username wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);

        // Hanya akun berstatus "aktif" yang bisa masuk
        if (Auth::attempt($kredensial + ['status' => User::STATUS_AKTIF], $request->boolean('ingat_saya'))) {
            // Ganti ID sesi setelah login supaya aman dari session fixation
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'));
        }

        // Gagal: kalau password-nya benar tapi akun belum aktif, beri tahu alasannya.
        // Pesan khusus ini hanya muncul untuk yang tahu password, jadi tidak membocorkan data.
        $user = User::where('username', $kredensial['username'])->first();
        $pesan = ($user && Hash::check($kredensial['password'], $user->password))
            ? (User::PESAN_STATUS[$user->status] ?? null)
            : null;

        return redirect()->route('login')
            ->withErrors(['username' => $pesan ?? 'Username atau password salah.'])
            ->onlyInput('username');
    }

    // Logout
    public function keluar(Request $request): RedirectResponse
    {
        Auth::logout();

        // Matikan sesi lama dan buat token CSRF baru
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}