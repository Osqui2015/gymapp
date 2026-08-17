<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'GymApp') }}</title>

        {{-- PWA (Progressive Web App) --}}
        <link rel="manifest" href="/manifest.webmanifest">
        <meta name="theme-color" content="#4f46e5">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="apple-mobile-web-app-title" content="GymApp">
        <link rel="icon" href="/favicon.ico">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        {{-- Script anti-FOUC: aplica el tema antes de que Vue hidrate --}}
        <script>
            (function () {
                try {
                    // Soporta ambas keys para retrocompat: 'theme' (vieja) y 'theme_mode' (nueva)
                    var mode = localStorage.getItem('theme_mode') || localStorage.getItem('theme') || 'auto';
                    var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
                    var dark = mode === 'dark' || (mode === 'auto' && prefersDark);
                    if (dark) document.documentElement.classList.add('dark');
                } catch (e) {}
            })();
        </script>

        {{-- Config pública de broadcasting (Mejora 8.1) --}}
        <script>
            window.__broadcasting = {
                driver: @json(config('services.broadcasting.driver', 'null')),
                key: @json(config('services.broadcasting.pusher.key')),
                cluster: @json(config('services.broadcasting.pusher.options.cluster')),
            };
            window.__user = window.__user || { id: @json(auth()->id()) };
        </script>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100">
        {{-- Skip to content (a11y: 6.3) --}}
        <a
            href="#main-content"
            class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-[60] focus:px-4 focus:py-2 focus:bg-indigo-600 focus:text-white focus:rounded-lg focus:shadow-lg focus:font-semibold"
        >
            Saltar al contenido principal
        </a>

        <div class="min-h-screen pb-16 md:pb-0">
            @include('layouts.navigation')

            @isset($header)
                <header class="bg-white dark:bg-gray-800 shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <main id="app" x-ignore>
                <div id="main-content" tabindex="-1">
                    {{ $slot }}
                </div>
            </main>
        </div>

        {{-- Bottom navigation (mobile only) --}}
        @auth
            <x-mobile-bottom-nav />
        @endauth

        {{-- Mount point para el sistema global de toasts (Vue app separada en resources/js/app.js) --}}
        <div id="toast-root"></div>

        {{-- Dark mode manager (debe ir DESPUÉS de Alpine.js para exponer window.darkMode) --}}
        <script>
            (function () {
                const STORAGE_KEY = 'theme_mode'; // nueva key: 'auto' | 'light' | 'dark'
                const LEGACY_KEY = 'theme';      // vieja key: 'light' | 'dark'
                const root = document.documentElement;
                function setStored(v) { try { localStorage.setItem(STORAGE_KEY, v); } catch (e) {} }
                function prefersDark() { return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches; }
                function effectiveDark() {
                    var mode = localStorage.getItem(STORAGE_KEY);
                    if (!mode) {
                        // retrocompat: si la vieja key está, usarla
                        var legacy = localStorage.getItem(LEGACY_KEY);
                        if (legacy === 'dark') return true;
                        if (legacy === 'light') return false;
                        return prefersDark();
                    }
                    return mode === 'dark' || (mode === 'auto' && prefersDark());
                }
                function apply() {
                    if (effectiveDark()) root.classList.add('dark');
                    else root.classList.remove('dark');
                }
                window.darkMode = {
                    isDark: () => root.classList.contains('dark'),
                    toggle() {
                        const next = !root.classList.contains('dark');
                        apply();
                        setStored(next ? 'dark' : 'light');
                        window.dispatchEvent(new CustomEvent('theme:changed', { detail: { dark: next } }));
                    },
                };
                // Reaccionar a cambios del sistema cuando el user eligió 'auto'
                if (window.matchMedia) {
                    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', apply);
                }
            })();
        </script>
    </body>
</html>