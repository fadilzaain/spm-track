<?php

use App\Http\Middleware\CekRole;
use App\Http\Middleware\PastikanAktif;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Alias untuk dipakai di routes/web.php: "role" (cek role) dan "aktif" (cek status akun)
        $middleware->alias([
            'role' => CekRole::class,
            'aktif' => PastikanAktif::class,
        ]);

        // Belum login -> ke halaman login; sudah login tapi buka /login -> ke dashboard
        $middleware->redirectGuestsTo('/login');
        $middleware->redirectUsersTo('/dashboard');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();