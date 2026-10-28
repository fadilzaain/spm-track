<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GantiPasswordController extends Controller
{
    private const PESAN = [
        'required' => ':attribute wajib diisi.',
        'min' => ':attribute minimal :min karakter.',
        'confirmed' => 'Konfirmasi password baru tidak sama.',
        'current_password' => 'Password lama salah.',
        'different' => 'Password baru harus berbeda dari password lama.',
    ];

    private const ATRIBUT = [
        'password_lama' => 'Password lama',
        'password' => 'Password baru',
    ];

    // Halaman form ganti password (untuk semua role yang sudah login)
    public function form(): View
    {
        return view('auth.ganti-password');
    }

    // Simpan password baru. 
    public function simpan(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'password_lama' => ['required', 'string', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed', 'different:password_lama'],
        ], self::PESAN, self::ATRIBUT);

        // Password otomatis di-hash oleh model User (lihat casts di User.php)
        $request->user()->update([
            'password' => $data['password'],
            'wajib_ganti_password' => false,
        ]);

        // Ganti ID sesi setelah perubahan sensitif
        $request->session()->regenerate();

        return redirect()->route('akun.password')->with('status', 'Password berhasil diganti.');
    }
}