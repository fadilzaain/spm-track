<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WajibGantiPassword
{
    /**
     * Akun yang baru direset admin (password sementara) hanya boleh membuka halaman
     * Ganti Password dan Keluar. Semua halaman lain dialihkan ke Ganti Password.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->wajib_ganti_password
            && ! $request->routeIs('akun.password', 'akun.password.simpan', 'logout')) {
            return redirect()->route('akun.password');
        }

        return $next($request);
    }
}