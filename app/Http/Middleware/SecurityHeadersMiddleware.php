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
            // Vite dev server corre en localhost:5173. Usamos wildcards
            // (localhost:* y 127.0.0.1:*) que son válidos en CSP spec
            // y cubren cualquier puerto. NO usamos http://[::1]:5173
            // porque Chrome ignora sources IPv6 con brackets literales.
            $scriptSrc[] = 'http://localhost:*';
            $scriptSrc[] = 'http://127.0.0.1:*';
        }
        $scriptSrc[] = 'https://cdn.jsdelivr.net'; // canvas-confetti fallback si se sirve por CDN
        $scriptSrc[] = 'https://unpkg.com'; // @phosphor-icons/web (Kinetic Obsidian UI)

        $styleSrc = ["'self'", "'unsafe-inline'"]; // Tailwind inline styles
        // Bunny Fonts es el default de Laravel/Breeze para Figtree. Es seguro
        // (no permite JS, solo CSS/fonts) y se usa en producción, no solo en dev.
        $styleSrc[] = 'https://fonts.bunny.net';
        $styleSrc[] = 'https://fonts.googleapis.com';
        // Google Fonts sirve los archivos binarios de fuente desde gstatic.com;
        // algunos navegadores también lo consultan como stylesheet fallback.
        $styleSrc[] = 'https://fonts.gstatic.com';
        // @phosphor-icons/web (Kinetic Obsidian UI) puede inyectar <style>
        // o cargar CSS auxiliar desde unpkg.
        $styleSrc[] = 'https://unpkg.com';
        // jsDelivr se usa como CDN alternativo para algunos assets.
        $styleSrc[] = 'https://cdn.jsdelivr.net';
        if ($isDev) {
            $styleSrc[] = 'http://localhost:*';
            $styleSrc[] = 'http://127.0.0.1:*';
        }

        $fontSrc = ["'self'", 'data:'];
        $fontSrc[] = 'https://fonts.bunny.net'; // Bunny Fonts (Figtree)
        $fontSrc[] = 'https://fonts.gstatic.com';
        // @phosphor-icons/web inyecta <link rel=stylesheet> por cada weight
        // (regular/thin/light/bold/fill/duotone). Los style.css resultantes
        // declaran @font-face que apunta a woff2/woff/ttf. Esos binarios
        // se sirven desde unpkg.com Y desde cdn.jsdelivr.net (depende
        // de qué CDN termine resolviendo el navegador).
        $fontSrc[] = 'https://unpkg.com';
        $fontSrc[] = 'https://cdn.jsdelivr.net';

        $connectSrc = ["'self'"];
        if ($isDev) {
            $connectSrc[] = 'ws:';   // Vite HMR WebSocket
            $connectSrc[] = 'wss:';
            $connectSrc[] = 'http://localhost:*';
            $connectSrc[] = 'http://127.0.0.1:*';
        }

        // img-src: además de 'self', data:, blob:, en dev permitimos localhost
        // para que el Vite dev server pueda servir imágenes del repo (svg, etc).
        $imgSrc = ["'self'", 'data:', 'blob:'];
        if ($isDev) {
            $imgSrc[] = 'http://localhost:*';
            $imgSrc[] = 'http://127.0.0.1:*';
        }

        $directives = [
            "default-src 'self'",
            'script-src '.implode(' ', $scriptSrc),
            'style-src '.implode(' ', $styleSrc),
            'img-src '.implode(' ', $imgSrc),
            'font-src '.implode(' ', $fontSrc),
            'connect-src '.implode(' ', $connectSrc),
            // canvas-confetti crea un Web Worker desde blob: para animar partículas.
            // Sin worker-src explícito, CSP usa script-src como fallback, que no
            // permite blob:. Agregamos 'self' + blob: para que funcione tanto en
            // dev (Vite) como en prod (assets bundleados).
            "worker-src 'self' blob:",
            "frame-ancestors 'self'",
            "base-uri 'self'",
            "form-action 'self'",
        ];

        return implode('; ', $directives);
    }
}
