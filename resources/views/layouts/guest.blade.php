<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'GymApp') }}</title>

        {{-- Script anti-FOUC: aplica el tema antes de que Vue hidrate (mínimo bloqueante) --}}
        <script nonce="{{ $cspNonce ?? '' }}">
            (function () {
                try {
                    var m = localStorage.getItem('theme_mode') || localStorage.getItem('theme') || 'auto';
                    var p = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
                    var d = m === 'dark' || (m === 'auto' && p);
                    var doc = document.documentElement;
                    if (d) doc.classList.add('dark'); else doc.classList.remove('dark');
                    doc.dataset.theme = d ? 'dark' : 'light';
                    doc.style.colorScheme = d ? 'dark' : 'light';
                } catch (e) {}
            })();
        </script>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-bg text-text">
        <div class="min-h-screen flex flex-col sm:justify-center items-center py-12 sm:pt-0 relative overflow-hidden">
            {{-- Decoración de fondo Kinetic Obsidian --}}
            <div class="pointer-events-none absolute inset-0 opacity-60 dark:opacity-100">
                <div class="absolute -top-32 -left-32 w-96 h-96 rounded-full bg-violet-500/20 blur-3xl"></div>
                <div class="absolute -bottom-32 -right-32 w-96 h-96 rounded-full bg-emerald-500/15 blur-3xl"></div>
            </div>

            <div class="relative">
                <a href="/" class="flex items-center gap-3 mb-6">
                    <div class="w-12 h-12 bg-gradient-to-br from-[var(--color-violet-deep)] via-[var(--color-violet-primary)] to-[var(--color-violet-light)] rounded-xl flex items-center justify-center shadow-[0_8px_24px_var(--color-violet-glow)]">
                        <span class="text-white font-black text-xl">G</span>
                    </div>
                    <span class="text-2xl font-black text-text tracking-tight">GymApp</span>
                </a>
            </div>

            <div class="relative w-full sm:max-w-md mt-6 px-6 py-8 bg-surface shadow-[0_24px_60px_rgba(0,0,0,0.12)] dark:shadow-[0_24px_60px_rgba(0,0,0,0.6)] rounded-2xl border border-border">
                {{ $slot }}
            </div>

            <div class="relative mt-6 text-center text-sm text-text-3">
                &copy; {{ date('Y') }} GymApp. Todos los derechos reservados.
            </div>
        </div>
    </body>
</html>