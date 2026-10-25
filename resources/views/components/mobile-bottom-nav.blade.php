@php
    /** @var \App\Models\User $user */
    $user = Auth::user();
    $isAlumno = $user->hasRole('alumno');
    $isStaff = $user->hasRole(['trainer', 'administrador']);

    // Los 4 items principales del bottom nav (siempre visibles junto a Menú)
    $primary = [
        [
            'route' => 'dashboard',
            'match' => 'dashboard',
            'label' => 'Inicio',
            'material_icon' => 'dashboard',
            'ph_icon' => 'ph ph-house',
            'ph_fill' => 'ph-fill ph-house',
        ],
        [
            'route' => 'rutinas',
            'match' => 'rutinas*',
            'label' => 'Rutinas',
            'material_icon' => 'assignment',
            'ph_icon' => 'ph ph-clipboard-text',
            'ph_fill' => 'ph-fill ph-clipboard-text',
        ],
        [
            'route' => 'historial',
            'match' => 'historial*',
            'label' => 'Historial',
            'material_icon' => 'history',
            'ph_icon' => 'ph ph-clock-counter-clockwise',
            'ph_fill' => 'ph-fill ph-clock-counter-clockwise',
        ],
        [
            'route' => 'progreso',
            'match' => 'progreso*',
            'label' => 'Progreso',
            'material_icon' => 'monitoring',
            'ph_icon' => 'ph ph-chart-line-up',
            'ph_fill' => 'ph-fill ph-chart-line-up',
        ],
    ];
@endphp

<div
    x-data="{ menuOpen: false }"
    @keydown.escape.window="menuOpen = false"
    class="md:hidden fixed bottom-0 left-0 right-0 z-40 font-sans"
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
        class="fixed inset-0 bg-black/70 backdrop-blur-sm"
        style="display: none;"
    ></div>

    {{-- Sheet deslizable (GymApp Telemetry Pro) --}}
    <div
        x-show="menuOpen"
        x-transition:enter="transition ease-out duration-300 transform"
        x-transition:enter-start="translate-y-full"
        x-transition:enter-end="translate-y-0"
        x-transition:leave="transition ease-in duration-200 transform"
        x-transition:leave-start="translate-y-0"
        x-transition:leave-end="translate-y-full"
        class="fixed bottom-0 left-0 right-0 bg-[#131924] rounded-t-3xl shadow-2xl border-t border-[#232D42] max-h-[85vh] overflow-y-auto text-slate-200"
        style="display: none;"
    >
        {{-- Handle visual --}}
        <div class="flex justify-center pt-3 pb-1">
            <div class="w-10 h-1.5 bg-[#232D42] rounded-full"></div>
        </div>

        <div class="px-4 pt-2 pb-24">
            {{-- Header del usuario (Telemetry Pro gradient) --}}
            <div class="flex items-center gap-3 p-3.5 mb-4 bg-gradient-to-r from-indigo-600/25 via-violet-600/20 to-[#18202F] border border-indigo-500/30 rounded-2xl relative overflow-hidden">
                <div class="relative w-11 h-11 bg-gradient-to-tr from-indigo-600 to-violet-500 rounded-xl flex items-center justify-center shadow-lg shadow-indigo-600/30">
                    <span class="text-white font-bold text-lg">{{ substr($user->name, 0, 1) }}</span>
                </div>
                <div class="relative flex-1 min-w-0">
                    <p class="text-sm font-bold text-white truncate">{{ $user->name }}</p>
                    <p class="text-xs text-indigo-300 font-mono uppercase tracking-wider">{{ $user->normalizedRole() }}</p>
                </div>
                <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/30">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span class="text-[10px] font-mono text-emerald-300 uppercase">Online</span>
                </div>
            </div>

            {{-- Items de navegación primaria --}}
            <p class="px-2 mb-2 text-[10px] font-bold uppercase tracking-widest text-slate-400">Navegación</p>
            <nav class="space-y-1 mb-4">
                @foreach ($primary as $item)
                    <a
                        href="{{ route($item['route']) }}"
                        @click="menuOpen = false"
                        class="flex items-center gap-3 px-3.5 py-3 rounded-xl text-sm font-semibold transition-colors {{ request()->routeIs($item['match']) ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30' : 'text-slate-200 hover:bg-[#18202F]' }}"
                    >
                        <span class="material-symbols-outlined text-[20px] {{ request()->routeIs($item['match']) ? 'text-indigo-400' : 'text-slate-400' }}">{{ $item['material_icon'] }}</span>
                        <span>{{ $item['label'] }}</span>
                        @if (request()->routeIs($item['match']))
                            <span class="ml-auto w-2 h-2 bg-indigo-400 rounded-full"></span>
                        @endif
                    </a>
                @endforeach

                <a
                    href="{{ route('ejercicios') }}"
                    @click="menuOpen = false"
                    class="flex items-center gap-3 px-3.5 py-3 rounded-xl text-sm font-semibold transition-colors {{ request()->routeIs('ejercicios*') ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30' : 'text-slate-200 hover:bg-[#18202F]' }}"
                >
                    <span class="material-symbols-outlined text-[20px] {{ request()->routeIs('ejercicios*') ? 'text-indigo-400' : 'text-slate-400' }}">fitness_center</span>
                    <span>Biblioteca de Ejercicios</span>
                    @if (request()->routeIs('ejercicios*'))
                        <span class="ml-auto w-2 h-2 bg-indigo-400 rounded-full"></span>
                    @endif
                </a>
            </nav>

            {{-- Trainer --}}
            @if ($isStaff)
                <p class="px-2 mb-2 text-[10px] font-bold uppercase tracking-widest text-indigo-400">Panel Trainer</p>
                <nav class="space-y-1 mb-4">
                    <a href="{{ route('trainer.dashboard') }}" @click="menuOpen = false" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-colors {{ request()->routeIs('trainer.dashboard') ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30' : 'text-slate-200 hover:bg-[#18202F]' }}">
                        <span class="material-symbols-outlined text-[18px] text-indigo-400">space_dashboard</span>
                        <span>Dashboard Trainer</span>
                    </a>
                    <a href="{{ route('trainer.alumnos') }}" @click="menuOpen = false" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-colors {{ request()->routeIs('trainer.alumnos') ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30' : 'text-slate-200 hover:bg-[#18202F]' }}">
                        <span class="material-symbols-outlined text-[18px] text-indigo-400">groups</span>
                        <span>Alumnos</span>
                    </a>
                    <a href="{{ route('trainer.ejercicios') }}" @click="menuOpen = false" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-colors {{ request()->routeIs('trainer.ejercicios') ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30' : 'text-slate-200 hover:bg-[#18202F]' }}">
                        <span class="material-symbols-outlined text-[18px] text-indigo-400">exercise</span>
                        <span>Ejercicios Privados</span>
                    </a>
                    <a href="{{ route('trainer.duplicar') }}" @click="menuOpen = false" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-colors {{ request()->routeIs('trainer.duplicar') ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30' : 'text-slate-200 hover:bg-[#18202F]' }}">
                        <span class="material-symbols-outlined text-[18px] text-indigo-400">content_copy</span>
                        <span>Duplicar Rutinas</span>
                    </a>
                </nav>
            @endif

            {{-- Admin --}}
            @if ($user->hasRole('administrador'))
                <p class="px-2 mb-2 text-[10px] font-bold uppercase tracking-widest text-rose-400">Panel Admin</p>
                <nav class="space-y-1 mb-4">
                    <a href="{{ route('admin.estadisticas') }}" @click="menuOpen = false" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-colors {{ request()->routeIs('admin.estadisticas') ? 'bg-rose-500/20 text-rose-300 border border-rose-500/30' : 'text-slate-200 hover:bg-[#18202F]' }}">
                        <span class="material-symbols-outlined text-[18px] text-rose-400">analytics</span>
                        <span>Estadísticas</span>
                    </a>
                    <a href="{{ route('admin.membresias') }}" @click="menuOpen = false" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-colors {{ request()->routeIs('admin.membresias*') ? 'bg-rose-500/20 text-rose-300 border border-rose-500/30' : 'text-slate-200 hover:bg-[#18202F]' }}">
                        <span class="material-symbols-outlined text-[18px] text-rose-400">card_membership</span>
                        <span>Membresías</span>
                    </a>
                    <a href="{{ route('admin.audit-logs') }}" @click="menuOpen = false" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-colors {{ request()->routeIs('admin.audit-logs') ? 'bg-rose-500/20 text-rose-300 border border-rose-500/30' : 'text-slate-200 hover:bg-[#18202F]' }}">
                        <span class="material-symbols-outlined text-[18px] text-rose-400">receipt_long</span>
                        <span>Audit Logs</span>
                    </a>
                    <a href="{{ route('admin.import-export') }}" @click="menuOpen = false" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-colors {{ request()->routeIs('admin.import-export') ? 'bg-rose-500/20 text-rose-300 border border-rose-500/30' : 'text-slate-200 hover:bg-[#18202F]' }}">
                        <span class="material-symbols-outlined text-[18px] text-rose-400">sync_alt</span>
                        <span>Importar / Exportar</span>
                    </a>
                </nav>
            @endif

            {{-- Cuenta --}}
            <p class="px-2 mb-2 text-[10px] font-bold uppercase tracking-widest text-slate-400">Cuenta</p>
            <nav class="space-y-1">
                <a href="{{ $user->hasRole('administrador') ? route('configuracion') : route('profile.edit') }}" @click="menuOpen = false" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-colors text-slate-200 hover:bg-[#18202F]">
                    <span class="material-symbols-outlined text-[18px] text-slate-400">settings</span>
                    <span>Configuración</span>
                </a>
                <a href="{{ route('profile.edit') }}" @click="menuOpen = false" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-colors text-slate-200 hover:bg-[#18202F]">
                    <span class="material-symbols-outlined text-[18px] text-slate-400">person</span>
                    <span>Perfil</span>
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-colors text-rose-400 hover:bg-rose-500/10 cursor-pointer">
                        <span class="material-symbols-outlined text-[18px]">logout</span>
                        <span>Cerrar Sesión</span>
                    </button>
                </form>
            </nav>
        </div>
    </div>

    {{-- Barra inferior persistente (GymApp Telemetry Pro) --}}
    <nav class="bg-[#0B0F17]/95 backdrop-blur-xl border-t border-[#232D42] pb-safe shadow-[0_-8px_30px_rgba(0,0,0,0.6)]" data-purpose="bottom-persistent-navigation">
        <div class="max-w-md mx-auto px-3 py-1.5">
            <div class="grid grid-cols-5 gap-1 items-center justify-items-center">
                @foreach ($primary as $item)
                    @php $isActive = request()->routeIs($item['match']); @endphp
                    @if ($isActive)
                        <a
                            href="{{ route($item['route']) }}"
                            class="flex flex-col items-center justify-center py-1 px-2.5 rounded-xl text-white relative group w-full"
                        >
                            <div class="absolute -top-1.5 w-8 h-1 rounded-full bg-indigo-500 shadow-[0_0_12px_rgba(99,102,241,0.8)]"></div>
                            <div class="w-9 h-7 rounded-lg bg-indigo-600/25 border border-indigo-500/40 flex items-center justify-center text-indigo-300 mb-0.5">
                                <span class="material-symbols-outlined text-[19px]">{{ $item['material_icon'] }}</span>
                            </div>
                            <span class="text-[10px] font-bold tracking-tight text-white">{{ $item['label'] }}</span>
                        </a>
                    @else
                        <a
                            href="{{ route($item['route']) }}"
                            class="flex flex-col items-center justify-center py-1 px-2 rounded-xl text-slate-400 hover:text-slate-200 transition-colors group w-full"
                        >
                            <div class="w-9 h-7 flex items-center justify-center">
                                <span class="material-symbols-outlined text-[20px] group-hover:scale-110 transition-transform">{{ $item['material_icon'] }}</span>
                            </div>
                            <span class="text-[10px] font-medium tracking-tight">{{ $item['label'] }}</span>
                        </a>
                    @endif
                @endforeach

                {{-- Botón "Menú" (abre el sheet) --}}
                <button
                    type="button"
                    @click="menuOpen = !menuOpen"
                    class="flex flex-col items-center justify-center py-1 px-2 rounded-xl text-slate-400 hover:text-slate-200 transition-colors group relative w-full cursor-pointer"
                    :class="menuOpen ? 'text-indigo-400' : ''"
                >
                    @if ($user->hasRole('administrador'))
                        <span class="absolute top-1 right-3 w-2 h-2 bg-rose-500 rounded-full ring-2 ring-[#0B0F17]"></span>
                    @elseif ($isStaff)
                        <span class="absolute top-1 right-3 w-2 h-2 bg-violet-500 rounded-full ring-2 ring-[#0B0F17]"></span>
                    @endif
                    <div class="w-9 h-7 flex items-center justify-center">
                        <span class="material-symbols-outlined text-[20px] group-hover:scale-110 transition-transform">menu</span>
                    </div>
                    <span class="text-[10px] font-medium tracking-tight">Menú</span>
                </button>
            </div>
        </div>
    </nav>
</div>
