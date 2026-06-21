<nav x-data="{ open: false }" class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                        <div class="w-8 h-8 bg-gradient-to-br from-indigo-600 to-purple-600 rounded-lg flex items-center justify-center">
                            <span class="text-white font-bold text-sm">G</span>
                        </div>
                        <span class="text-xl font-bold text-gray-800 dark:text-white">GymApp</span>
                    </a>
                </div>

                <div class="hidden lg:flex lg:items-center lg:ms-4">
                    <global-search />
                </div>

                <div class="hidden space-x-6 sm:ms-8 sm:flex sm:items-center">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" class="flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        {{ __('Dashboard') }}
                    </x-nav-link>
                    @if (!Auth::user()->hasRole('alumno'))
                        <x-nav-link :href="route('rutinas')" :active="request()->routeIs('rutinas')" class="flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-3-8h3" />
                            </svg>
                            {{ __('Rutinas') }}
                        </x-nav-link>
                    @endif
                    <x-nav-link :href="route('ejercicios')" :active="request()->routeIs('ejercicios')" class="flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                            {{ __('Ejercicios') }}
                        </x-nav-link>
                    <x-nav-link :href="route('historial')" :active="request()->routeIs('historial')" class="flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        {{ __('Historial') }}
                    </x-nav-link>
                    <x-nav-link :href="route('progreso')" :active="request()->routeIs('progreso')" class="flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        {{ __('Progreso') }}
                    </x-nav-link>
                    {{-- Oculto por ahora:
                    <x-nav-link :href="route('nutricion')" :active="request()->routeIs('nutricion')" class="flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        {{ __('Nutrición') }}
                    </x-nav-link>
                    --}}
                    @if (Auth::user()->hasRole(['trainer', 'administrador']))
                        <div x-data="{ trainerOpen: false }" class="relative">
                            <button @click="trainerOpen = !trainerOpen" @click.away="trainerOpen = false" class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('trainer.*') ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                Trainer
                                <svg class="w-4 h-4 transition-transform" :class="{'rotate-180': trainerOpen}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div x-show="trainerOpen" x-transition class="absolute left-0 mt-2 w-56 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden z-50" style="display: none;">
                                <div class="py-2">
                                    <a href="{{ route('trainer.dashboard') }}" class="flex items-center gap-3 px-4 py-3 text-sm {{ request()->routeIs('trainer.dashboard') ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/20 dark:text-indigo-400' : 'text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700' }} transition-colors">
                                        <span class="text-lg">📊</span>
                                        <span class="font-medium">Dashboard</span>
                                    </a>
                                    <a href="{{ route('trainer.alumnos') }}" class="flex items-center gap-3 px-4 py-3 text-sm {{ request()->routeIs('trainer.alumnos') ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/20 dark:text-indigo-400' : 'text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700' }} transition-colors">
                                        <span class="text-lg">👥</span>
                                        <span class="font-medium">Alumnos</span>
                                    </a>
                                    <a href="{{ route('trainer.ejercicios') }}" class="flex items-center gap-3 px-4 py-3 text-sm {{ request()->routeIs('trainer.ejercicios') ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/20 dark:text-indigo-400' : 'text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700' }} transition-colors">
                                        <span class="text-lg">🏋️</span>
                                        <span class="font-medium">Ejercicios Privados</span>
                                    </a>
                                    <a href="{{ route('trainer.duplicar') }}" class="flex items-center gap-3 px-4 py-3 text-sm {{ request()->routeIs('trainer.duplicar') ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/20 dark:text-indigo-400' : 'text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700' }} transition-colors">
                                        <span class="text-lg">📋</span>
                                        <span class="font-medium">Duplicar Rutinas</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if (Auth::user()->hasRole('administrador'))
                        <div x-data="{ adminOpen: false }" class="relative">
                            <button @click="adminOpen = !adminOpen" @click.away="adminOpen = false" class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('admin.*') ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                Admin
                                <svg class="w-4 h-4 transition-transform" :class="{'rotate-180': adminOpen}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div x-show="adminOpen" x-transition class="absolute left-0 mt-2 w-60 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden z-50" style="display: none;">
                                <div class="py-2">
                                    <a href="{{ route('admin.estadisticas') }}" class="flex items-center gap-3 px-4 py-3 text-sm {{ request()->routeIs('admin.estadisticas') ? 'bg-red-50 text-red-700 dark:bg-red-900/20 dark:text-red-400' : 'text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700' }} transition-colors">
                                        <span class="text-lg">📊</span>
                                        <span class="font-medium">Estadísticas</span>
                                    </a>
                                    <a href="{{ route('admin.membresias') }}" class="flex items-center gap-3 px-4 py-3 text-sm {{ request()->routeIs('admin.membresias') ? 'bg-red-50 text-red-700 dark:bg-red-900/20 dark:text-red-400' : 'text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700' }} transition-colors">
                                        <span class="text-lg">💳</span>
                                        <span class="font-medium">Membresías</span>
                                    </a>
                                    <a href="{{ route('admin.audit-logs') }}" class="flex items-center gap-3 px-4 py-3 text-sm {{ request()->routeIs('admin.audit-logs') ? 'bg-red-50 text-red-700 dark:bg-red-900/20 dark:text-red-400' : 'text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700' }} transition-colors">
                                        <span class="text-lg">📋</span>
                                        <span class="font-medium">Audit Logs</span>
                                    </a>
                                    <a href="{{ route('admin.import-export') }}" class="flex items-center gap-3 px-4 py-3 text-sm {{ request()->routeIs('admin.import-export') ? 'bg-red-50 text-red-700 dark:bg-red-900/20 dark:text-red-400' : 'text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700' }} transition-colors">
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
            <div class="hidden sm:flex sm:items-center sm:gap-2 relative">
                {{-- Toggle dark mode (Alpine.js) --}}
                <x-dark-mode-toggle />

                <button @click="open = !open" class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                    <div class="w-8 h-8 bg-gradient-to-br from-indigo-500 to-purple-500 rounded-full flex items-center justify-center">
                        <span class="text-white text-sm font-semibold">{{ substr(Auth::user()->name, 0, 1) }}</span>
                    </div>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ Auth::user()->name }}</span>
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" />
                    </svg>
                </button>

                <!-- Menú desplegable -->
                <div x-show="open" 
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     @click.away="open = false"
                     class="absolute right-0 mt-2 w-56 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden z-50"
                     style="display: none;">
                    
                    <!-- Header con usuario -->
                    <div class="px-4 py-3 bg-gradient-to-r from-indigo-500 to-purple-500">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
                                <span class="text-white font-bold">{{ substr(Auth::user()->name, 0, 1) }}</span>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-white">{{ Auth::user()->name }}</p>
                                <p class="text-xs text-white/70">{{ Auth::user()->email }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Opciones del menú -->
                    <div class="py-2">
                        <a href="{{ Auth::user()->hasRole('administrador') ? route('configuracion') : route('profile.edit') }}" class="flex items-center gap-3 px-4 py-3 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span class="font-medium">Configuración</span>
                        </a>
                        
                        <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 px-4 py-3 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            <span class="font-medium">Perfil</span>
                        </a>

                        <hr class="my-2 border-gray-200 dark:border-gray-700">

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full flex items-center gap-3 px-4 py-3 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                                <span class="font-medium">Cerrar Sesión</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Menú hamburguesa móvil eliminado: lo reemplaza el bottom nav (resources/views/components/mobile-bottom-nav.blade.php) -->
        </div>
    </div>

</nav>
