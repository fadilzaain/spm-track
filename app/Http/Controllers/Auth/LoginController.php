<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    // Tampilkan halaman login
    public function tampilkanForm(): View
    {
        return view('auth.login');
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

        // Auth::attempt mencocokkan kolom username dan password (hash) di tabel users
        if (Auth::attempt($kredensial, $request->boolean('ingat_saya'))) {
            // Ganti ID sesi setelah login supaya aman dari session fixation
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'));
        }

        // Jika gagal login: balik ke form, username tetap terisi, password dikosongkan
        return back()
            ->withErrors(['username' => 'Username atau password salah.'])
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