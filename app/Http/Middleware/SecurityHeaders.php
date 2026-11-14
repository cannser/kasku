<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $h = $response->headers;

        // CSP: hanya script/style dari domain sendiri + CDN Bootstrap & Chart.js, tanpa inline script.
        // Saat APP_DEBUG=true dipakai mode Report-Only supaya halaman error debug Laravel
        // (yang memakai inline script) tetap tampil. Di production CSP benar-benar memblokir.
        $csp = "default-src 'self'; "
            . "script-src 'self' https://cdn.jsdelivr.net; "
            . "style-src 'self' https://cdn.jsdelivr.net; "
            . "img-src 'self' data:; font-src 'self' https://cdn.jsdelivr.net; "
            . "connect-src 'self'; object-src 'none'; base-uri 'self'; "
            . "form-action 'self'; frame-ancestors 'none'";
        $h->set(config('app.debug') ? 'Content-Security-Policy-Report-Only' : 'Content-Security-Policy', $csp);

        $h->set('X-Frame-Options', 'DENY');
        $h->set('X-Content-Type-Options', 'nosniff');
        $h->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $h->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        if ($request->isSecure()) {
            $h->set('Strict-Transport-Security', 'max-age=31536000');
        }

        // Halaman berisi data pribadi: jangan disimpan di cache / tombol back setelah logout
        $h->set('Cache-Control', 'no-store, no-cache, must-revalidate, private');
        $h->remove('X-Powered-By');

        return $response;
    }
}
