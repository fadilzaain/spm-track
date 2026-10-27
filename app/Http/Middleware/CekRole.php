<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CekRole
{
    /**
     * Cara pakai di route: ->middleware('role:admin,manajemen')
     * Hanya user dengan salah satu role yang disebutkan yang boleh lewat.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        // Belum login: arahkan ke halaman login
        if (! $user) {
            return redirect()->route('login');
        }

        // Sudah login tapi role tidak diizinkan: tolak dengan 403
        if (! in_array($user->role, $roles, true)) {
            abort(403, 'Anda tidak punya akses ke halaman ini.');
        }

        return $next($request);
    }
}