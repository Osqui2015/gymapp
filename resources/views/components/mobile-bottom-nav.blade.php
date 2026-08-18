@php
    /** @var \App\Models\User $user */
    $user = Auth::user();
    $isAlumno = $user->hasRole('alumno');
    $isStaff = $user->hasRole(['trainer', 'administrador']);

    // Los 4 items principales del bottom nav (siempre visibles)
    $primary = [
        ['route' => 'dashboard', 'match' => 'dashboard', 'label' => 'Inicio',  'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />'],
    ];

    if (!$isAlumno) {
        $primary[] = ['route' => 'rutinas', 'match' => 'rutinas', 'label' => 'Rutinas', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-3-8h3" />'];
    } else {
        $primary[] = ['route' => 'ejercicios', 'match' => 'ejercicios', 'label' => 'Ejercicios', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />'];
    }

    $primary[] = ['route' => 'historial', 'match' => 'historial', 'label' => 'Historial', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />'];
    $primary[] = ['route' => 'progreso',  'match' => 'progreso',  'label' => 'Progreso',  'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />'];

    // Determinar el "match" para resaltar el tab activo
    $currentRoute = request()->route()->getName();
@endphp

<div
    x-data="{ menuOpen: false }"
    @keydown.escape.window="menuOpen = false"
    class="md:hidden fixed bottom-0 left-0 right-0 z-40"
>
    {{-- Backdrop del sheet "Menú" --}}
    <div
        x-show="menuOpen"
        x-transition:enter="transition-opacity ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="menuOpen = false"
        class="fixed inset-0 bg-black/50 backdrop-blur-sm"
        style="display: none;"
    ></div>

    {{-- Sheet deslizable --}}
    <div
        x-show="menuOpen"
        x-transition:enter="transition ease-out duration-300 transform"
        x-transition:enter-start="translate-y-full"
        x-transition:enter-end="translate-y-0"
        x-transition:leave="transition ease-in duration-200 transform"
        x-transition:leave-start="translate-y-0"
        x-transition:leave-end="translate-y-full"
        class="fixed bottom-0 left-0 right-0 bg-white dark:bg-gray-800 rounded-t-3xl shadow-2xl border-t border-gray-200 dark:border-gray-700 max-h-[85vh] overflow-y-auto"
        style="display: none;"
    >
        {{-- Handle visual --}}
        <div class="flex justify-center pt-3 pb-1">
            <div class="w-10 h-1.5 bg-gray-300 dark:bg-gray-600 rounded-full"></div>
        </div>

        <div class="px-4 pt-2 pb-20">
            {{-- Header del usuario --}}
            <div class="flex items-center gap-3 p-3 mb-4 bg-gradient-to-r from-indigo-500 to-purple-500 rounded-2xl">
                <div class="w-11 h-11 bg-white/20 rounded-full flex items-center justify-center">
                    <span class="text-white font-bold text-lg">{{ substr($user->name, 0, 1) }}</span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-white truncate">{{ $user->name }}</p>
                    <p class="text-xs text-white/80 capitalize">{{ $user->normalizedRole() }}</p>
                </div>
            </div>

            {{-- Items de navegación primaria --}}
            <p class="px-2 mb-2 text-[10px] font-bold uppercase tracking-wider text-gray-400">Principal</p>
            <nav class="space-y-1 mb-4">
                @foreach ($primary as $item)
                    <a
                        href="{{ route($item['route']) }}"
                        @click="menuOpen = false"
                        class="flex items-center gap-3 px-3 py-3 rounded-xl text-sm font-semibold transition-colors {{ request()->routeIs($item['match']) ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300' : 'text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700' }}"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">{!! $item['icon'] !!}</svg>
                        {{ $item['label'] }}
                        @if (request()->routeIs($item['match']))
                            <span class="ml-auto w-1.5 h-1.5 bg-indigo-500 rounded-full"></span>
                        @endif
                    </a>
                @endforeach

                <a
                    href="{{ route('ejercicios') }}"
                    @click="menuOpen = false"
                    class="flex items-center gap-3 px-3 py-3 rounded-xl text-sm font-semibold transition-colors {{ request()->routeIs('ejercicios') ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300' : 'text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700' }}"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    Ejercicios
                </a>
            </nav>

            {{-- Trainer --}}
            @if ($isStaff)
                <p class="px-2 mb-2 text-[10px] font-bold uppercase tracking-wider text-gray-400">Panel Trainer</p>
                <nav class="space-y-1 mb-4">
                    <a href="{{ route('trainer.dashboard') }}" @click="menuOpen = false" class="flex items-center gap-3 px-3 py-3 rounded-xl text-sm font-semibold transition-colors {{ request()->routeIs('trainer.dashboard') ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300' : 'text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                        <span>📊</span> Dashboard Trainer
                    </a>
                    <a href="{{ route('trainer.alumnos') }}" @click="menuOpen = false" class="flex items-center gap-3 px-3 py-3 rounded-xl text-sm font-semibold transition-colors {{ request()->routeIs('trainer.alumnos') ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300' : 'text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                        <span>👥</span> Alumnos
                    </a>
                    <a href="{{ route('trainer.ejercicios') }}" @click="menuOpen = false" class="flex items-center gap-3 px-3 py-3 rounded-xl text-sm font-semibold transition-colors {{ request()->routeIs('trainer.ejercicios') ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300' : 'text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                        <span>🏋️</span> Ejercicios Privados
                    </a>
                    <a href="{{ route('trainer.duplicar') }}" @click="menuOpen = false" class="flex items-center gap-3 px-3 py-3 rounded-xl text-sm font-semibold transition-colors {{ request()->routeIs('trainer.duplicar') ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300' : 'text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                        <span>📋</span> Duplicar Rutinas
                    </a>
                </nav>
            @endif

            {{-- Admin --}}
            @if ($user->hasRole('administrador'))
                <p class="px-2 mb-2 text-[10px] font-bold uppercase tracking-wider text-red-500">Panel Admin</p>
                <nav class="space-y-1 mb-4">
                    <a href="{{ route('admin.estadisticas') }}" @click="menuOpen = false" class="flex items-center gap-3 px-3 py-3 rounded-xl text-sm font-semibold transition-colors {{ request()->routeIs('admin.estadisticas') ? 'bg-red-50 text-red-700 dark:bg-red-950/40 dark:text-red-300' : 'text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                        <span>📊</span> Estadísticas
                    </a>
                    <a href="{{ route('admin.membresias') }}" @click="menuOpen = false" class="flex items-center gap-3 px-3 py-3 rounded-xl text-sm font-semibold transition-colors {{ request()->routeIs('admin.membresicas') || request()->routeIs('admin.membresias') ? 'bg-red-50 text-red-700 dark:bg-red-950/40 dark:text-red-300' : 'text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                        <span>💳</span> Membresías
                    </a>
                    <a href="{{ route('admin.audit-logs') }}" @click="menuOpen = false" class="flex items-center gap-3 px-3 py-3 rounded-xl text-sm font-semibold transition-colors {{ request()->routeIs('admin.audit-logs') ? 'bg-red-50 text-red-700 dark:bg-red-950/40 dark:text-red-300' : 'text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                        <span>📋</span> Audit Logs
                    </a>
                    <a href="{{ route('admin.import-export') }}" @click="menuOpen = false" class="flex items-center gap-3 px-3 py-3 rounded-xl text-sm font-semibold transition-colors {{ request()->routeIs('admin.import-export') ? 'bg-red-50 text-red-700 dark:bg-red-950/40 dark:text-red-300' : 'text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                        <span>📂</span> Importar / Exportar
                    </a>
                </nav>
            @endif

            {{-- Cuenta --}}
            <p class="px-2 mb-2 text-[10px] font-bold uppercase tracking-wider text-gray-400">Cuenta</p>
            <nav class="space-y-1">
                <a href="{{ $user->hasRole('administrador') ? route('configuracion') : route('profile.edit') }}" @click="menuOpen = false" class="flex items-center gap-3 px-3 py-3 rounded-xl text-sm font-semibold transition-colors text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Configuración
                </a>
                <a href="{{ route('profile.edit') }}" @click="menuOpen = false" class="flex items-center gap-3 px-3 py-3 rounded-xl text-sm font-semibold transition-colors text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    Perfil
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full flex items-center gap-3 px-3 py-3 rounded-xl text-sm font-semibold transition-colors text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/30">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        Cerrar Sesión
                    </button>
                </form>
            </nav>
        </div>
    </div>

    {{-- Toggle dark mode flotante sobre el bottom nav (solo mobile) --}}
    <div class="md:hidden fixed bottom-20 right-3 z-40">
        <x-dark-mode-toggle />
    </div>

    {{-- Barra inferior (4 tabs + Menú) --}}
    <nav class="bg-white/95 dark:bg-gray-800/95 backdrop-blur-md border-t border-gray-200 dark:border-gray-700 shadow-[0_-2px_8px_rgba(0,0,0,0.04)] pb-[env(safe-area-inset-bottom)]">
        <ul class="grid grid-cols-5 h-16">
            @foreach ($primary as $item)
                @php $isActive = request()->routeIs($item['match']); @endphp
                <li>
                    <a
                        href="{{ route($item['route']) }}"
                        class="group flex flex-col items-center justify-center h-full gap-0.5 transition-all {{ $isActive ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-500 dark:text-gray-400' }}"
                        :class="{ 'text-indigo-600 dark:text-indigo-400': true }"
                    >
                        <span class="relative">
                            <svg class="w-6 h-6 transition-transform group-active:scale-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">{!! $item['icon'] !!}</svg>
                            @if ($isActive)
                                <span class="absolute -top-1.5 left-1/2 -translate-x-1/2 w-1 h-1 bg-indigo-600 dark:bg-indigo-400 rounded-full"></span>
                            @endif
                        </span>
                        <span class="text-[10px] font-semibold leading-none">{{ $item['label'] }}</span>
                    </a>
                </li>
            @endforeach

            {{-- Botón "Menú" (abre el sheet) --}}
            <li>
                <button
                    @click="menuOpen = !menuOpen"
                    class="w-full h-full flex flex-col items-center justify-center gap-0.5 transition-all"
                    :class="menuOpen ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-500 dark:text-gray-400'"
                >
                    <span class="relative">
                        @if ($user->hasRole('administrador'))
                            <span class="absolute -top-0.5 -right-0.5 w-2 h-2 bg-red-500 rounded-full ring-2 ring-white dark:ring-gray-800"></span>
                        @elseif ($isStaff)
                            <span class="absolute -top-0.5 -right-0.5 w-2 h-2 bg-indigo-500 rounded-full ring-2 ring-white dark:ring-gray-800"></span>
                        @endif
                        <svg x-show="!menuOpen" class="w-6 h-6 transition-transform" :class="menuOpen ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        <svg x-show="menuOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:none;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </span>
                    <span class="text-[10px] font-semibold leading-none">Menú</span>
                </button>
            </li>
        </ul>
    </nav>
</div>
