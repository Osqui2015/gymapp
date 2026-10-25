<nav x-data="{ open: false }" class="bg-[#0B0F17]/90 backdrop-blur-xl shadow-[0_1px_8px_rgba(0,0,0,0.2)] border-b border-[#232D42]/80 sticky top-0 z-50 font-body-md">
    <div class="h-14 md:h-20 max-w-[1280px] mx-auto px-margin md:px-margin-tablet lg:px-margin-desktop flex items-center justify-between gap-space-md md:gap-space-lg">
        <div class="flex items-center gap-3 md:gap-space-xl">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 group">
                <div class="w-9 h-9 md:w-10 md:h-10 rounded-xl bg-gradient-to-tr from-indigo-600 via-indigo-500 to-violet-500 flex items-center justify-center shadow-[0_0_20px_rgba(99,102,241,0.35)] transition-transform group-hover:scale-105">
                    <span class="material-symbols-outlined text-white text-[20px] md:text-[24px]">fitness_center</span>
                </div>
                <div class="flex flex-col">
                    <div class="flex items-center gap-1.5">
                        <span class="font-headline-md text-sm md:text-headline-md tracking-tight text-on-surface font-bold">Gym<span class="text-secondary">App</span></span>
                        <span class="text-[10px] font-mono uppercase tracking-widest px-1.5 py-0.5 rounded bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 font-semibold">PRO</span>
                    </div>
                    <span class="hidden sm:block font-label-sm text-label-sm uppercase tracking-wider text-outline">Telemetry Pro</span>
                </div>
            </a>

            <!-- Acceso rápido en Mobile a Historial -->
            <div class="flex md:hidden items-center gap-1.5">
                <a
                    href="{{ route('historial') }}"
                    class="px-2.5 py-1 rounded-lg text-xs font-semibold transition-all flex items-center gap-1 {{ request()->routeIs('historial*') ? 'bg-secondary-container text-on-secondary-container shadow-sm' : 'bg-[#131924] text-slate-300 border border-[#232D42] hover:text-white' }}"
                >
                    <span class="material-symbols-outlined text-[15px]">history</span>
                    <span>Historial</span>
                </a>
            </div>

            <div class="hidden xl:flex xl:items-center">
                <global-search />
            </div>

            <div class="hidden md:flex items-center gap-space-xs p-space-xs bg-surface-container-low rounded-xl">
                <a
                    href="{{ route('dashboard') }}"
                    aria-current="{{ request()->routeIs('dashboard') ? 'page' : 'false' }}"
                    class="px-space-md py-space-sm transition-colors rounded-lg font-label-lg text-label-lg {{ request()->routeIs('dashboard') ? 'bg-secondary-container text-on-secondary-container font-semibold' : 'text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface' }}"
                >
                    {{ __('Dashboard') }}
                </a>

                @if (!Auth::user()->hasRole('alumno'))
                    <a
                        href="{{ route('rutinas') }}"
                        aria-current="{{ request()->routeIs('rutinas*') ? 'page' : 'false' }}"
                        class="px-space-md py-space-sm transition-colors rounded-lg font-label-lg text-label-lg {{ request()->routeIs('rutinas*') ? 'bg-secondary-container text-on-secondary-container font-semibold' : 'text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface' }}"
                    >
                        {{ __('Rutinas') }}
                    </a>
                @endif

                <a
                    href="{{ route('ejercicios') }}"
                    aria-current="{{ request()->routeIs('ejercicios*') ? 'page' : 'false' }}"
                    class="px-space-md py-space-sm transition-colors rounded-lg font-label-lg text-label-lg {{ request()->routeIs('ejercicios*') ? 'bg-secondary-container text-on-secondary-container font-semibold' : 'text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface' }}"
                >
                    {{ __('Ejercicios') }}
                </a>

                <a
                    href="{{ route('historial') }}"
                    aria-current="{{ request()->routeIs('historial*') ? 'page' : 'false' }}"
                    class="px-space-md py-space-sm transition-colors rounded-lg font-label-lg text-label-lg {{ request()->routeIs('historial*') ? 'bg-secondary-container text-on-secondary-container font-semibold' : 'text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface' }}"
                >
                    {{ __('Historial') }}
                </a>

                <a
                    href="{{ route('progreso') }}"
                    aria-current="{{ request()->routeIs('progreso*') ? 'page' : 'false' }}"
                    class="px-space-md py-space-sm transition-colors rounded-lg font-label-lg text-label-lg {{ request()->routeIs('progreso*') ? 'bg-secondary-container text-on-secondary-container font-semibold' : 'text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface' }}"
                >
                    {{ __('Progreso') }}
                </a>

                @if (Auth::user()->hasRole(['trainer', 'administrador']))
                    <div x-data="{ trainerOpen: false }" class="relative">
                        <button
                            @click="trainerOpen = !trainerOpen"
                            @click.away="trainerOpen = false"
                            type="button"
                            class="flex items-center gap-1.5 px-space-md py-space-sm rounded-lg font-label-lg text-label-lg transition-colors {{ request()->routeIs('trainer.*') ? 'bg-secondary-container text-on-secondary-container font-semibold' : 'text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface' }}"
                        >
                            <span>Trainer</span>
                            <svg class="w-4 h-4 transition-transform" :class="{'rotate-180': trainerOpen}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div x-show="trainerOpen" x-transition class="absolute left-0 mt-2 w-56 bg-surface-container rounded-xl shadow-xl border border-surface-variant overflow-hidden z-50" style="display: none;">
                            <div class="py-2">
                                <a href="{{ route('trainer.dashboard') }}" class="flex items-center gap-3 px-4 py-3 text-sm {{ request()->routeIs('trainer.dashboard') ? 'bg-secondary-container/40 text-secondary' : 'text-on-surface hover:bg-surface-container-high' }} transition-colors">
                                    <span class="text-lg">📊</span>
                                    <span class="font-medium">Dashboard</span>
                                </a>
                                <a href="{{ route('trainer.alumnos') }}" class="flex items-center gap-3 px-4 py-3 text-sm {{ request()->routeIs('trainer.alumnos') ? 'bg-secondary-container/40 text-secondary' : 'text-on-surface hover:bg-surface-container-high' }} transition-colors">
                                    <span class="text-lg">👥</span>
                                    <span class="font-medium">Alumnos</span>
                                </a>
                                <a href="{{ route('trainer.ejercicios') }}" class="flex items-center gap-3 px-4 py-3 text-sm {{ request()->routeIs('trainer.ejercicios') ? 'bg-secondary-container/40 text-secondary' : 'text-on-surface hover:bg-surface-container-high' }} transition-colors">
                                    <span class="text-lg">🏋️</span>
                                    <span class="font-medium">Ejercicios Privados</span>
                                </a>
                                <a href="{{ route('trainer.duplicar') }}" class="flex items-center gap-3 px-4 py-3 text-sm {{ request()->routeIs('trainer.duplicar') ? 'bg-secondary-container/40 text-secondary' : 'text-on-surface hover:bg-surface-container-high' }} transition-colors">
                                    <span class="text-lg">📋</span>
                                    <span class="font-medium">Duplicar Rutinas</span>
                                </a>
                            </div>
                        </div>
                    </div>
                @endif

                @if (Auth::user()->hasRole('administrador'))
                    <div x-data="{ adminOpen: false }" class="relative">
                        <button
                            @click="adminOpen = !adminOpen"
                            @click.away="adminOpen = false"
                            type="button"
                            class="flex items-center gap-1.5 px-space-md py-space-sm rounded-lg font-label-lg text-label-lg transition-colors {{ request()->routeIs('admin.*') ? 'bg-error-container text-error font-semibold' : 'text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface' }}"
                        >
                            <span>Admin</span>
                            <svg class="w-4 h-4 transition-transform" :class="{'rotate-180': adminOpen}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div x-show="adminOpen" x-transition class="absolute left-0 mt-2 w-60 bg-surface-container rounded-xl shadow-xl border border-surface-variant overflow-hidden z-50" style="display: none;">
                            <div class="py-2">
                                <a href="{{ route('admin.estadisticas') }}" class="flex items-center gap-3 px-4 py-3 text-sm {{ request()->routeIs('admin.estadisticas') ? 'bg-error-container/40 text-error' : 'text-on-surface hover:bg-surface-container-high' }} transition-colors">
                                    <span class="text-lg">📊</span>
                                    <span class="font-medium">Estadísticas</span>
                                </a>
                                <a href="{{ route('admin.membresias') }}" class="flex items-center gap-3 px-4 py-3 text-sm {{ request()->routeIs('admin.membresias') ? 'bg-error-container/40 text-error' : 'text-on-surface hover:bg-surface-container-high' }} transition-colors">
                                    <span class="text-lg">💳</span>
                                    <span class="font-medium">Membresías</span>
                                </a>
                                <a href="{{ route('admin.audit-logs') }}" class="flex items-center gap-3 px-4 py-3 text-sm {{ request()->routeIs('admin.audit-logs') ? 'bg-error-container/40 text-error' : 'text-on-surface hover:bg-surface-container-high' }} transition-colors">
                                    <span class="text-lg">📋</span>
                                    <span class="font-medium">Audit Logs</span>
                                </a>
                                <a href="{{ route('admin.import-export') }}" class="flex items-center gap-3 px-4 py-3 text-sm {{ request()->routeIs('admin.import-export') ? 'bg-error-container/40 text-error' : 'text-on-surface hover:bg-surface-container-high' }} transition-colors">
                                    <span class="text-lg">📂</span>
                                    <span class="font-medium">Importar/Exportar</span>
                                </a>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Menú usuario desktop -->
        <div class="flex items-center gap-space-md">
            <button
                x-data="{ dark: window.darkMode ? window.darkMode.isDark() : true }"
                @click="window.darkMode.toggle(); dark = !dark"
                @theme:changed.window="dark = $event.detail.dark"
                aria-label="Cambiar tema"
                type="button"
                class="w-10 h-10 rounded-xl bg-surface-container flex items-center justify-center text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface transition-colors"
            >
                <span class="material-symbols-outlined text-[20px]" x-text="dark ? 'dark_mode' : 'light_mode'">dark_mode</span>
            </button>

            <a
                aria-label="Ajustes rápidos"
                href="{{ Auth::user()->hasRole('administrador') ? route('configuracion') : route('profile.edit') }}"
                class="w-10 h-10 rounded-xl bg-surface-container flex items-center justify-center text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface transition-colors"
            >
                <span class="material-symbols-outlined text-[20px]">settings</span>
            </a>

            <div class="h-8 w-px bg-surface-variant hidden sm:block"></div>

            <div x-data="{ userOpen: false }" @click.away="userOpen = false" class="relative">
                <button
                    @click="userOpen = !userOpen"
                    type="button"
                    class="flex items-center gap-space-sm pl-space-xs py-1 rounded-xl hover:bg-surface-container/60 transition-colors"
                >
                    <div class="hidden sm:flex flex-col text-right">
                        <span class="font-label-md text-label-md text-on-surface leading-tight">{{ Auth::user()->name }}</span>
                        <span class="font-label-sm text-label-sm text-tertiary leading-tight">Atleta Élite</span>
                    </div>
                    <div class="w-8 h-8 rounded-full bg-primary flex items-center justify-center">
                        <span class="material-symbols-outlined text-on-primary text-[18px]">person</span>
                    </div>
                </button>

                <!-- Menú desplegable -->
                <div
                    x-show="userOpen"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="absolute right-0 mt-2 w-56 bg-surface-container rounded-xl shadow-2xl border border-surface-variant overflow-hidden z-50"
                    style="display: none;"
                >
                    <div class="px-4 py-3 bg-gradient-to-br from-secondary-container to-primary-container relative overflow-hidden">
                        <div class="relative flex items-center gap-3">
                            <div class="w-10 h-10 bg-surface/20 rounded-full flex items-center justify-center backdrop-blur-sm">
                                <span class="text-white font-bold">{{ substr(Auth::user()->name, 0, 1) }}</span>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-white">{{ Auth::user()->name }}</p>
                                <p class="text-xs text-white/75">{{ Auth::user()->email }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="py-2">
                        <a href="{{ Auth::user()->hasRole('administrador') ? route('configuracion') : route('profile.edit') }}" class="flex items-center gap-3 px-4 py-3 text-on-surface hover:bg-surface-container-high transition-colors">
                            <span class="material-symbols-outlined text-outline text-[20px]">settings</span>
                            <span class="font-medium text-sm">Configuración</span>
                        </a>

                        <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 px-4 py-3 text-on-surface hover:bg-surface-container-high transition-colors">
                            <span class="material-symbols-outlined text-outline text-[20px]">person</span>
                            <span class="font-medium text-sm">Perfil</span>
                        </a>

                        <hr class="my-2 border-surface-variant">

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full flex items-center gap-3 px-4 py-3 text-error hover:bg-error-container/20 transition-colors">
                                <span class="material-symbols-outlined text-[20px]">logout</span>
                                <span class="font-medium text-sm">Cerrar Sesión</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>
