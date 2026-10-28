<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class PastikanAktif
{
    /**
     * Akun yang dinonaktifkan admin langsung dikeluarkan di permintaan berikutnya,
     * jadi tidak perlu menunggu sesinya habis.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->isAktif()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['username' => User::PESAN_STATUS[$user->status] ?? 'Akun Anda tidak aktif.']);
        }

        return $next($request);
    }
}