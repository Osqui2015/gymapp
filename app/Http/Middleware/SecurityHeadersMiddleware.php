<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cabeceras de seguridad HTTP estándar.
 *
 * - X-Frame-Options: SAMEORIGIN         -> clickjacking.
 * - X-Content-Type-Options: nosniff     -> evita que el navegador adivine el MIME de un asset.
 * - Referrer-Policy: strict-origin...   -> no leakear paths a orígenes externos.
 * - Permissions-Policy: cámara/micro/geolocalización apagadas por defecto (la app no las usa).
 * - Strict-Transport-Security: 1 año, sólo si la request es HTTPS.
 * - Content-Security-Policy: estricto pero compatible con Vite (dev con HMR sobre ws:)
 *   y la PWA. Se permite inline style porque Vue/Tailwind los usa; si se rompe
 *   algún recurso, agregar el origen específico en lugar de relajar el default-src.
 */
class SecurityHeadersMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        $csp = $this->buildCsp();
        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }

    private function buildCsp(): string
    {
        // CSP deliberadamente permisivo con 'self' e inline (necesario para Vue
        // runtime + Tailwind + service worker de la PWA). 'unsafe-eval' solo en
        // dev (Vite lo necesita). En prod el manifest se construye sin eval.
        $isDev = config('app.debug');

        $scriptSrc = ["'self'"];
        $scriptSrc[] = "'unsafe-inline'";   // Vue runtime template compilation
        if ($isDev) {
            $scriptSrc[] = "'unsafe-eval'"; // Vite HMR
        }
        $scriptSrc[] = 'https://cdn.jsdelivr.net'; // canvas-confetti fallback si se sirve por CDN

        $styleSrc = ["'self'", "'unsafe-inline'"]; // Tailwind inline styles
        $connectSrc = ["'self'"];
        if ($isDev) {
            $connectSrc[] = 'ws:';   // Vite HMR WebSocket
            $connectSrc[] = 'wss:';
        }

        $directives = [
            "default-src 'self'",
            'script-src '.implode(' ', $scriptSrc),
            'style-src '.implode(' ', $styleSrc),
            "img-src 'self' data: blob: ".implode(' ', $connectSrc),
            "font-src 'self' data:",
            'connect-src '.implode(' ', $connectSrc),
            "frame-ancestors 'self'",
            "base-uri 'self'",
            "form-action 'self'",
        ];

        return implode('; ', $directives);
    }
}
