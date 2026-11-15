<?php

use App\Http\Middleware\EnsureAccountActive;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\SecurityHeaders;
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
        // Security headers dipasang global: berlaku di semua respons dari Laravel
        $middleware->append(SecurityHeaders::class);

        // Di belakang reverse proxy (Vercel): percayai proxy agar HTTPS dan IP klien terbaca benar.
        // Diaktifkan lewat env TRUSTED_PROXIES=* (di Vercel saja; api/index.php mengisinya otomatis). Nilai '*' hanya mempercayai
        // proxy yang langsung terhubung (REMOTE_ADDR), jadi header X-Forwarded-* palsu dari klien diabaikan.
        // Jika env kosong (lokal/XAMPP), tidak ada proxy yang dipercaya.
        $middleware->trustProxies(at: env('TRUSTED_PROXIES') ?: null);

        // Alias untuk dipakai di routes/web.php: ->middleware('role:admin'), 'active'
        $middleware->alias([
            'role'   => EnsureRole::class,
            'active' => EnsureAccountActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
