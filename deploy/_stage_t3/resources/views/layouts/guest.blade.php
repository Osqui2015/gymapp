<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'GymApp') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-gray-50 dark:bg-[var(--color-obsidian-base)] text-gray-900 dark:text-gray-100">
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
                    <span class="text-2xl font-black text-gray-900 dark:text-white tracking-tight">GymApp</span>
                </a>
            </div>

            <div class="relative w-full sm:max-w-md mt-6 px-6 py-8 bg-white dark:bg-[var(--color-obsidian-surface)] shadow-[0_24px_60px_rgba(0,0,0,0.3)] dark:shadow-[0_24px_60px_rgba(0,0,0,0.6)] rounded-2xl border border-gray-200 dark:border-[var(--color-obsidian-border-strong)]">
                {{ $slot }}
            </div>

            <div class="relative mt-6 text-center text-sm text-gray-500 dark:text-gray-400">
                &copy; {{ date('Y') }} GymApp. Todos los derechos reservados.
            </div>
        </div>
    </body>
</html>