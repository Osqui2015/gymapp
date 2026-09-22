<template>
    <div class="min-h-screen bg-[#080b12] text-slate-200 font-sans antialiased selection:bg-neon-indigo selection:text-white pb-32 md:pb-16">
        <!-- ============================================================ -->
        <!-- HEADER MOBILE (Exact Kinetic Obsidian Prototype)             -->
        <!-- ============================================================ -->
        <header class="md:hidden sticky top-0 z-40 pt-safe glass-panel border-b border-white/[0.06]">
            <div class="max-w-md mx-auto px-4 py-3">
                <div class="flex items-center justify-between gap-3">
                    <!-- Logo Brand & Title -->
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-neon-indigo via-neon-violet to-purple-500 p-[1.5px] shadow-glow-indigo flex items-center justify-center">
                            <div class="w-full h-full bg-obsidian-900 rounded-[10px] flex items-center justify-center">
                                <span class="font-display font-extrabold text-base bg-gradient-to-tr from-white via-indigo-200 to-neon-indigo bg-clip-text text-transparent">G</span>
                            </div>
                        </div>
                        <div>
                            <div class="flex items-center gap-1.5">
                                <span class="font-display font-bold text-sm tracking-tight text-white">GymApp</span>
                                <span class="inline-block w-1 h-1 rounded-full bg-neon-indigo"></span>
                                <span class="text-[10px] tracking-wider uppercase font-semibold text-slate-400">Library</span>
                            </div>
                            <p class="text-[11px] text-slate-400 font-medium">{{ total.toLocaleString() }} Ejercicios</p>
                        </div>
                    </div>
                    <!-- Right Badges: Streak & User -->
                    <div class="flex items-center gap-2">
                        <!-- Streak Badge -->
                        <div class="flex items-center gap-1 px-2.5 py-1 rounded-full bg-amber-500/10 border border-amber-500/25 text-amber-400">
                            <i class="ph-fill ph-fire text-xs animate-pulse"></i>
                            <span class="font-display text-[11px] font-bold tracking-tight">{{ streak }} DÍAS</span>
                        </div>
                        <!-- Notification quick button -->
                        <button
                            type="button"
                            @click="toast.info('Sin notificaciones pendientes')"
                            aria-label="Notificaciones"
                            class="w-9 h-9 rounded-xl bg-obsidian-800/80 border border-white/[0.08] flex items-center justify-center text-slate-300 hover:text-white active:scale-95 transition-all"
                        >
                            <i class="ph ph-bell-simple text-base"></i>
                        </button>
                        <!-- User Profile Avatar -->
                        <a
                            href="/profile"
                            aria-label="Perfil de usuario"
                            class="w-9 h-9 rounded-xl overflow-hidden border border-neon-indigo/30 p-[1px] bg-gradient-to-b from-white/20 to-transparent block"
                        >
                            <div class="w-full h-full bg-obsidian-700 rounded-[10px] flex items-center justify-center text-xs font-semibold text-indigo-300">
                                {{ userInitials }}
                            </div>
                        </a>
                    </div>
                </div>
                <!-- Breadcrumbs & Heading -->
                <div class="mt-3.5 pb-1">
                    <div class="flex items-center gap-1.5 text-[11px] font-medium text-slate-400 mb-1">
                        <a class="hover:text-neon-indigo transition-colors" href="/dashboard">Inicio</a>
                        <i class="ph ph-caret-right text-[10px] text-slate-600"></i>
                        <span class="text-neon-indigo font-semibold">Ejercicios</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <h1 class="font-display text-2xl font-bold text-white tracking-tight flex items-center gap-2">
                            Biblioteca de Ejercicios
                        </h1>
                        <button
                            v-if="userRole === 'trainer' || userRole === 'administrador'"
                            @click="mostrarModal = true"
                            class="text-xs font-semibold text-emerald-400 hover:text-emerald-300 bg-emerald-500/10 border border-emerald-500/30 px-2.5 py-1 rounded-lg flex items-center gap-1 transition-all active:scale-95"
                        >
                            <i class="ph ph-plus-circle text-sm"></i>
                            <span>Nuevo</span>
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <!-- ============================================================ -->
        <!-- HEADER DESKTOP (Widescreen layout with Breadcrumbs & Title)  -->
        <!-- ============================================================ -->
        <header class="hidden md:block border-b border-white/[0.06] bg-obsidian-950/40 backdrop-blur-md">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-5">
                <div class="flex items-center justify-between">
                    <div>
                        <!-- Breadcrumbs -->
                        <div class="flex items-center gap-1.5 text-xs font-medium text-slate-400 mb-1.5">
                            <a class="hover:text-neon-indigo transition-colors" href="/dashboard">Inicio</a>
                            <i class="ph ph-caret-right text-[10px] text-slate-600"></i>
                            <span class="text-neon-indigo font-semibold">Ejercicios</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <h1 class="font-display text-2xl lg:text-3xl font-bold text-white tracking-tight">
                                Biblioteca de Ejercicios
                            </h1>
                            <span class="px-2.5 py-0.5 rounded-full bg-neon-indigo/15 border border-neon-indigo/30 text-neon-indigo text-xs font-semibold">
                                {{ total.toLocaleString() }} ejercicios disponibles
                            </span>
                        </div>
                    </div>

                    <!-- Right Quick Actions -->
                    <div class="flex items-center gap-3">
                        <!-- Streak Badge -->
                        <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-amber-500/10 border border-amber-500/25 text-amber-400">
                            <i class="ph-fill ph-fire text-sm animate-pulse"></i>
                            <span class="font-display text-xs font-bold tracking-tight">{{ streak }} DÍAS DE RACHA</span>
                        </div>

                        <!-- Add Exercise (Trainer/Admin) -->
                        <button
                            v-if="userRole === 'trainer' || userRole === 'administrador'"
                            @click="mostrarModal = true"
                            class="px-4 py-2 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 text-white text-xs font-semibold shadow-glow-emerald flex items-center gap-1.5 transition-all hover:brightness-110 active:scale-95 cursor-pointer"
                        >
                            <i class="ph-bold ph-plus text-sm"></i>
                            <span>Agregar Ejercicio</span>
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <!-- ============================================================ -->
        <!-- MAIN CONTENT (Responsive 2-column layout: Sticky Map + Feed) -->
        <!-- ============================================================ -->
        <main class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4 sm:pt-6">
            <div class="md:grid md:grid-cols-[320px_1fr] lg:grid-cols-[380px_1fr] xl:grid-cols-[400px_1fr] md:gap-6 lg:gap-8 items-start">
                <!-- ======================================================== -->
                <!-- COLUMNA IZQUIERDA (Sticky): MAPA MUSCULAR ANATÓMICO      -->
                <!-- ======================================================== -->
                <aside class="md:sticky md:top-20 lg:top-24 space-y-4 mb-6 md:mb-0">
                    <section class="glass-card rounded-2xl p-4 sm:p-5 relative overflow-hidden shadow-xl" data-purpose="interactive-muscle-map">
                        <!-- Background Ambient Glow -->
                        <div class="absolute -top-16 -right-16 w-48 h-48 bg-neon-indigo/15 rounded-full blur-3xl pointer-events-none"></div>
                        <div class="absolute -bottom-16 -left-16 w-48 h-48 bg-neon-violet/10 rounded-full blur-3xl pointer-events-none"></div>

                        <!-- Card Top: Title & Front/Back Switch Tabs -->
                        <div class="flex items-center justify-between mb-3 relative z-10">
                            <div>
                                <span class="text-[10px] font-bold tracking-wider uppercase text-slate-400">Mapa Muscular</span>
                                <p class="text-xs text-slate-200 font-medium">Toca un grupo para filtrar</p>
                            </div>
                            <!-- Pills Toggle: Frente / Espalda -->
                            <div class="flex items-center bg-obsidian-950/80 p-1 rounded-xl border border-white/[0.08]">
                                <button
                                    type="button"
                                    @click="vistaAnatomia = 'front'"
                                    :class="[
                                        'px-3 py-1 rounded-lg text-xs font-semibold transition-all cursor-pointer',
                                        vistaAnatomia === 'front'
                                            ? 'bg-neon-indigo text-white shadow-glow-indigo'
                                            : 'text-slate-400 hover:text-white font-medium'
                                    ]"
                                >
                                    Frente
                                </button>
                                <button
                                    type="button"
                                    @click="vistaAnatomia = 'back'"
                                    :class="[
                                        'px-3 py-1 rounded-lg text-xs font-semibold transition-all cursor-pointer',
                                        vistaAnatomia === 'back'
                                            ? 'bg-neon-indigo text-white shadow-glow-indigo'
                                            : 'text-slate-400 hover:text-white font-medium'
                                    ]"
                                >
                                    Espalda
                                </button>
                            </div>
                        </div>

                        <!-- Vector Anatomy Illustration (High-Definition Anatomical Paths) -->
                        <div class="relative py-2 flex flex-col items-center justify-center">
                            <div class="relative w-full h-72 sm:h-80 flex items-center justify-center select-none">
                                <svg
                                    :viewBox="currentAnatomyData.vb"
                                    class="w-full h-full max-h-[300px] sm:max-h-[320px] drop-shadow-[0_14px_28px_rgba(0,0,0,0.8)]"
                                    preserveAspectRatio="xMidYMid meet"
                                >
                                    <defs>
                                        <!-- Active Neon Glow Filter -->
                                        <filter id="neonMuscleGlow" x="-30%" y="-30%" width="160%" height="160%">
                                            <feGaussianBlur stdDeviation="7" result="glow" />
                                            <feMerge>
                                                <feMergeNode in="glow" />
                                                <feMergeNode in="SourceGraphic" />
                                            </feMerge>
                                        </filter>
                                        <!-- Soft Radial Ambient behind figure -->
                                        <radialGradient id="bodyAmbientLight" cx="50%" cy="45%" r="50%">
                                            <stop offset="0%" stop-color="#6366f1" stop-opacity="0.18" />
                                            <stop offset="60%" stop-color="#4f46e5" stop-opacity="0.05" />
                                            <stop offset="100%" stop-color="#090d16" stop-opacity="0" />
                                        </radialGradient>
                                    </defs>

                                    <!-- Ambient Background Glow behind the body -->
                                    <ellipse
                                        :cx="vistaAnatomia === 'front' ? 363 : 1081"
                                        cy="600"
                                        rx="260"
                                        ry="460"
                                        fill="url(#bodyAmbientLight)"
                                        class="pointer-events-none"
                                    />

                                    <!-- Anatomical Muscle Groups & Structural Parts -->
                                    <g v-for="(pathList, slug) in currentAnatomyData.paths" :key="`${vistaAnatomia}-${slug}`">
                                        <path
                                            v-for="(d, idx) in pathList"
                                            :key="`${slug}-${idx}`"
                                            :d="d"
                                            :data-slug="slug"
                                            :fill="getMuscleFill(slug)"
                                            :stroke="getMuscleStroke(slug)"
                                            :stroke-width="getMuscleStrokeWidth(slug)"
                                            :filter="isMuscleActive(slug) ? 'url(#neonMuscleGlow)' : undefined"
                                            class="transition-all duration-200"
                                            :class="[
                                                isInteractiveMuscle(slug)
                                                    ? 'cursor-pointer hover:brightness-125'
                                                    : 'pointer-events-none'
                                            ]"
                                            @mouseenter="onMuscleHover(slug)"
                                            @mouseleave="onMuscleLeave"
                                            @click="onMuscleClick(slug)"
                                        >
                                            <title>{{ getMuscleTitle(slug) }}</title>
                                        </path>
                                    </g>
                                </svg>

                                <!-- Floating badge: Selected Muscle overlay -->
                                <div class="absolute bottom-1 left-2 bg-obsidian-950/90 border border-neon-indigo/40 px-2.5 py-1 rounded-xl backdrop-blur-md shadow-lg flex items-center gap-1.5 pointer-events-none z-10">
                                    <span class="w-2 h-2 rounded-full bg-neon-indigo animate-ping"></span>
                                    <span class="text-[10px] font-semibold text-white">{{ activeMuscleLabel }}</span>
                                </div>
                            </div>

                            <!-- Full Expand Button -->
                            <button
                                type="button"
                                @click="bodyMapExpandido = true"
                                class="mt-2 inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-400 hover:text-indigo-300 py-1 px-3 rounded-lg bg-indigo-500/10 border border-indigo-500/20 active:scale-95 transition-all cursor-pointer"
                            >
                                <i class="ph ph-arrows-out-simple"></i>
                                <span>Expandir en grande</span>
                            </button>
                        </div>

                        <!-- Recency Legend -->
                        <div class="mt-3 pt-3 border-t border-white/[0.06]">
                            <span class="block text-[10px] font-bold tracking-wider uppercase text-slate-400 mb-2">Recencia · Cuándo entrenaste</span>
                            <div class="grid grid-cols-3 gap-1.5 text-[11px]">
                                <div class="flex items-center gap-1.5 bg-obsidian-950/60 p-1.5 rounded-lg border border-white/[0.04]">
                                    <span class="w-2.5 h-2.5 rounded-full bg-slate-500 shrink-0"></span>
                                    <span class="text-slate-300 text-[10px] truncate">0-3d Reciente</span>
                                </div>
                                <div class="flex items-center gap-1.5 bg-obsidian-950/60 p-1.5 rounded-lg border border-white/[0.04]">
                                    <span class="w-2.5 h-2.5 rounded-full bg-neon-indigo shrink-0"></span>
                                    <span class="text-indigo-200 text-[10px] truncate">1-2 sem.</span>
                                </div>
                                <div class="flex items-center gap-1.5 bg-obsidian-950/60 p-1.5 rounded-lg border border-white/[0.04]">
                                    <span class="w-2.5 h-2.5 rounded-full bg-neon-cyan shrink-0"></span>
                                    <span class="text-cyan-200 text-[10px] truncate">+30d o nunca</span>
                                </div>
                            </div>
                        </div>
                    </section>
                </aside>

                <!-- ======================================================== -->
                <!-- COLUMNA DERECHA: BUSCADOR, FILTROS, CARDS Y PAGINACIÓN   -->
                <!-- ======================================================== -->
                <div class="space-y-4">
                    <!-- BEGIN: SearchAndFiltersSection -->
                    <section class="space-y-3" data-purpose="search-and-filters">
                        <!-- Search Input Bar & CTA (Mobile: stacked | Desktop: inline row) -->
                        <div class="flex flex-col sm:flex-row gap-2.5">
                            <div class="relative flex-1">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                    <i class="ph ph-magnifying-glass text-lg"></i>
                                </div>
                                <input
                                    v-model="busqueda"
                                    @keyup.enter="buscar"
                                    class="w-full bg-obsidian-850/90 border border-white/[0.09] focus:border-neon-indigo focus:ring-1 focus:ring-neon-indigo text-slate-100 placeholder-slate-500 text-sm rounded-xl pl-10 pr-10 py-3 shadow-inner-subtle transition-all outline-none"
                                    placeholder="Buscar por nombre, equipamiento o músculo..."
                                    type="text"
                                />
                                <button
                                    type="button"
                                    @click="toggleAdvancedFilters"
                                    class="md:hidden absolute inset-y-0 right-0 pr-3 flex items-center text-slate-500 hover:text-slate-300"
                                    title="Filtros avanzados"
                                >
                                    <i class="ph ph-sliders-horizontal text-base" :class="{ 'text-neon-indigo': showAdvancedFilters }"></i>
                                </button>
                                <button
                                    v-if="busqueda"
                                    type="button"
                                    @click="busqueda = ''; buscar();"
                                    class="hidden md:flex absolute inset-y-0 right-0 pr-3 items-center text-slate-400 hover:text-white"
                                    title="Limpiar texto"
                                >
                                    <i class="ph ph-x text-sm"></i>
                                </button>
                            </div>

                            <!-- CTA Button -->
                            <button
                                type="button"
                                @click="buscar"
                                class="w-full sm:w-auto px-6 py-3 bg-gradient-to-r from-neon-indigo via-indigo-600 to-neon-violet hover:brightness-110 active:scale-[0.99] text-white font-display font-semibold text-sm rounded-xl shadow-glow-indigo flex items-center justify-center gap-2 transition-all cursor-pointer shrink-0"
                            >
                                <i class="ph ph-magnifying-glass font-bold"></i>
                                <span>Buscar Ejercicios</span>
                            </button>
                        </div>

                        <!-- Dropdown Selectors Filter Pills (Collapsible on Mobile, always grid on Desktop) -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 pt-1" :class="{ 'hidden sm:grid': !showAdvancedFilters }">
                            <!-- Grupo Muscular Selector -->
                            <div class="relative" ref="grupoDropdownRef">
                                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1 pl-1">Grupo Muscular</label>
                                <div
                                    @click="toggleGrupoDropdown"
                                    class="flex items-center justify-between bg-obsidian-850 border border-white/[0.08] hover:border-white/20 rounded-xl px-3.5 py-2.5 text-xs text-white cursor-pointer transition-colors"
                                >
                                    <div class="flex items-center gap-2 truncate">
                                        <i class="ph-bold ph-barbell text-neon-indigo"></i>
                                        <span class="font-medium truncate">{{ grupoMuscularFiltro || 'Todos los grupos' }}</span>
                                    </div>
                                    <div class="flex items-center gap-1.5 ml-2 shrink-0">
                                        <span
                                            v-if="grupoMuscularFiltro"
                                            @click.stop="seleccionarGrupo('')"
                                            class="text-slate-400 hover:text-rose-400 font-bold px-1 text-xs"
                                            title="Quitar filtro"
                                        >✕</span>
                                        <i class="ph ph-caret-down text-slate-400 text-xs transition-transform" :class="{ 'rotate-180': grupoSelectOpen }"></i>
                                    </div>
                                </div>

                                <!-- Dropdown modal popup -->
                                <div
                                    v-if="grupoSelectOpen"
                                    class="absolute z-50 left-0 right-0 mt-1 bg-obsidian-900 rounded-xl shadow-2xl border border-white/[0.12] overflow-hidden"
                                >
                                    <div class="p-2 border-b border-white/[0.06] bg-obsidian-950">
                                        <input
                                            ref="grupoSearchInputRef"
                                            v-model="grupoSearchText"
                                            type="text"
                                            placeholder="Buscar grupo muscular..."
                                            class="w-full px-3 py-1.5 text-xs bg-obsidian-850 border border-white/[0.1] rounded-lg text-white outline-none focus:border-neon-indigo"
                                        />
                                    </div>
                                    <div class="max-h-52 overflow-y-auto divide-y divide-white/[0.04]">
                                        <button
                                            type="button"
                                            @click="seleccionarGrupo('')"
                                            class="w-full px-3.5 py-2 text-left text-xs transition-colors hover:bg-neon-indigo/10 flex items-center justify-between text-slate-300"
                                            :class="{ 'font-bold text-neon-indigo bg-neon-indigo/15': !grupoMuscularFiltro }"
                                        >
                                            <span>Todos los grupos musculares</span>
                                            <span v-if="!grupoMuscularFiltro" class="text-neon-indigo">✓</span>
                                        </button>
                                        <button
                                            v-for="grupo in gruposFiltrados"
                                            :key="grupo"
                                            type="button"
                                            @click="seleccionarGrupo(grupo)"
                                            class="w-full px-3.5 py-2 text-left text-xs transition-colors hover:bg-neon-indigo/10 flex items-center justify-between text-slate-300"
                                            :class="{ 'font-bold text-neon-indigo bg-neon-indigo/15': grupoMuscularFiltro === grupo }"
                                        >
                                            <span>{{ grupo }}</span>
                                            <span v-if="grupoMuscularFiltro === grupo" class="text-neon-indigo">✓</span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Equipamiento -->
                            <div class="relative" ref="equipoDropdownRef">
                                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1 pl-1">Equipo</label>
                                <div
                                    @click="toggleEquipoDropdown"
                                    class="flex items-center justify-between bg-obsidian-850 border border-white/[0.08] hover:border-white/20 rounded-xl px-3.5 py-2.5 text-xs text-white cursor-pointer transition-colors"
                                >
                                    <span class="font-medium truncate">{{ equipamientoFiltro || 'Todo el equipo' }}</span>
                                    <div class="flex items-center gap-1 ml-1 shrink-0">
                                        <span
                                            v-if="equipamientoFiltro"
                                            @click.stop="seleccionarEquipamiento('')"
                                            class="text-slate-400 hover:text-rose-400 font-bold px-0.5 text-xs"
                                        >✕</span>
                                        <i class="ph ph-caret-down text-slate-400 text-xs transition-transform" :class="{ 'rotate-180': equipoSelectOpen }"></i>
                                    </div>
                                </div>
                                <div
                                    v-if="equipoSelectOpen"
                                    class="absolute z-50 left-0 right-0 mt-1 bg-obsidian-900 rounded-xl shadow-2xl border border-white/[0.12] overflow-hidden"
                                >
                                    <div class="p-2 border-b border-white/[0.06] bg-obsidian-950">
                                        <input
                                            ref="equipoSearchInputRef"
                                            v-model="equipoSearchText"
                                            type="text"
                                            placeholder="Buscar equipo..."
                                            class="w-full px-2.5 py-1 text-xs bg-obsidian-850 border border-white/[0.1] rounded-lg text-white outline-none focus:border-neon-indigo"
                                        />
                                    </div>
                                    <div class="max-h-48 overflow-y-auto divide-y divide-white/[0.04]">
                                        <button
                                            type="button"
                                            @click="seleccionarEquipamiento('')"
                                            class="w-full px-3 py-2 text-left text-xs transition-colors hover:bg-neon-indigo/10 text-slate-300"
                                            :class="{ 'font-bold text-neon-indigo bg-neon-indigo/15': !equipamientoFiltro }"
                                        >
                                            Todo el equipo
                                        </button>
                                        <button
                                            v-for="eq in equipamientosFiltrados"
                                            :key="eq"
                                            type="button"
                                            @click="seleccionarEquipamiento(eq)"
                                            class="w-full px-3 py-2 text-left text-xs transition-colors hover:bg-neon-indigo/10 flex items-center justify-between text-slate-300"
                                            :class="{ 'font-bold text-neon-indigo bg-neon-indigo/15': equipamientoFiltro === eq }"
                                        >
                                            <span class="truncate">{{ eq }}</span>
                                            <span v-if="equipamientoFiltro === eq" class="text-neon-indigo text-xs">✓</span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Dificultad -->
                            <div class="relative" ref="dificultadDropdownRef">
                                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1 pl-1">Dificultad</label>
                                <div
                                    @click="toggleDificultadDropdown"
                                    class="flex items-center justify-between bg-obsidian-850 border border-white/[0.08] hover:border-white/20 rounded-xl px-3.5 py-2.5 text-xs text-white cursor-pointer transition-colors"
                                >
                                    <div class="flex items-center gap-1.5 truncate">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                                        <span class="font-medium truncate capitalize">{{ dificultadFiltro || 'Todas' }}</span>
                                    </div>
                                    <div class="flex items-center gap-1 ml-1 shrink-0">
                                        <span
                                            v-if="dificultadFiltro"
                                            @click.stop="seleccionarDificultad('')"
                                            class="text-slate-400 hover:text-rose-400 font-bold px-0.5 text-xs"
                                        >✕</span>
                                        <i class="ph ph-caret-down text-slate-400 text-xs transition-transform" :class="{ 'rotate-180': dificultadSelectOpen }"></i>
                                    </div>
                                </div>
                                <div
                                    v-if="dificultadSelectOpen"
                                    class="absolute z-50 left-0 right-0 mt-1 bg-obsidian-900 rounded-xl shadow-2xl border border-white/[0.12] overflow-hidden divide-y divide-white/[0.04]"
                                >
                                    <button
                                        type="button"
                                        @click="seleccionarDificultad('')"
                                        class="w-full px-3 py-2 text-left text-xs transition-colors hover:bg-neon-indigo/10 text-slate-300"
                                        :class="{ 'font-bold text-neon-indigo bg-neon-indigo/15': !dificultadFiltro }"
                                    >
                                        Todas
                                    </button>
                                    <button
                                        v-for="dif in ['principiante', 'intermedio', 'avanzado']"
                                        :key="dif"
                                        type="button"
                                        @click="seleccionarDificultad(dif)"
                                        class="w-full px-3 py-2 text-left text-xs capitalize transition-colors hover:bg-neon-indigo/10 flex items-center justify-between text-slate-300"
                                        :class="{ 'font-bold text-neon-indigo bg-neon-indigo/15': dificultadFiltro === dif }"
                                    >
                                        <span>{{ dif }}</span>
                                        <span v-if="dificultadFiltro === dif" class="text-neon-indigo text-xs">✓</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Chips Scrollable Horizontal -->
                        <div class="flex items-center gap-1.5 overflow-x-auto no-scrollbar py-1 text-xs">
                            <button
                                type="button"
                                @click="limpiarFiltrosRapidos"
                                class="shrink-0 px-3 py-1.5 rounded-lg font-medium transition-all cursor-pointer"
                                :class="[
                                    !grupoMuscularFiltro && !musculoFiltroBodyMap
                                        ? 'bg-white/10 text-white border border-white/15 shadow-glow-indigo'
                                        : 'bg-obsidian-850 text-slate-300 hover:text-white border border-white/[0.06]'
                                ]"
                            >
                                Todos ({{ total.toLocaleString() }})
                            </button>
                            <button
                                v-for="chip in chipsPopulares"
                                :key="chip"
                                type="button"
                                @click="seleccionarChip(chip)"
                                class="shrink-0 px-3 py-1.5 rounded-lg font-medium transition-all cursor-pointer"
                                :class="[
                                    grupoMuscularFiltro === chip
                                        ? 'bg-neon-indigo text-white border border-neon-indigo/40 shadow-glow-indigo'
                                        : 'bg-obsidian-850 text-slate-300 hover:text-white border border-white/[0.06]'
                                ]"
                            >
                                {{ chip }}
                            </button>
                        </div>
                    </section>
                    <!-- END: SearchAndFiltersSection -->

                    <!-- Active Filter Bar Banner (if muscle filter selected from body map) -->
                    <div
                        v-if="musculoFiltroBodyMap || grupoMuscularFiltro"
                        class="flex items-center justify-between bg-neon-indigo/10 border border-neon-indigo/30 rounded-xl px-4 py-2.5 text-xs text-indigo-200"
                    >
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-neon-indigo animate-pulse"></span>
                            <span>Filtrando por: <strong class="text-white">{{ grupoMuscularFiltro || muscleLabels[musculoFiltroBodyMap] || musculoFiltroBodyMap }}</strong></span>
                        </div>
                        <button
                            type="button"
                            @click="limpiarFiltrosRapidos"
                            class="text-xs font-semibold text-neon-indigo hover:text-indigo-300 underline cursor-pointer"
                        >
                            Quitar filtro
                        </button>
                    </div>

                    <!-- BEGIN: ExercisesListSection -->
                    <section class="space-y-3" data-purpose="exercise-cards-feed">
                        <!-- Section Header with count badge and sort -->
                        <div class="flex items-center justify-between px-1">
                            <div class="flex items-center gap-2">
                                <h2 class="font-display text-sm font-bold tracking-tight text-slate-200">Listado de Ejercicios</h2>
                                <span class="px-2 py-0.5 rounded-md bg-neon-indigo/15 border border-neon-indigo/30 text-neon-indigo text-[11px] font-semibold">
                                    {{ ejercicios.length }} visibles
                                </span>
                            </div>
                            <button
                                type="button"
                                @click="toggleOrden"
                                class="text-xs font-semibold text-slate-400 hover:text-white flex items-center gap-1 transition-colors cursor-pointer"
                                :title="ordenAsc ? 'Ordenar Z-A' : 'Ordenar A-Z'"
                            >
                                <i class="ph ph-arrows-down-up"></i>
                                <span>{{ ordenAsc ? 'A-Z' : 'Z-A' }}</span>
                            </button>
                        </div>

                        <!-- Exercise Cards Feed (1 column on mobile, 2 columns on tablet/desktop) -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                            <article
                                v-for="ejercicio in ejercicios"
                                :key="ejercicio.id"
                                @click="seleccionarEjercicioBodyMap(ejercicio)"
                                class="glass-card rounded-xl p-3.5 relative overflow-hidden transition-all duration-200 border-l-2 cursor-pointer hover:border-white/20 hover:shadow-lg flex flex-col justify-between"
                                :class="[
                                    getBorderColor(ejercicio),
                                    ejercicioAComparar?.id === ejercicio.id
                                        ? 'ring-1 ring-neon-indigo bg-neon-indigo/10'
                                        : ''
                                ]"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex-1 min-w-0">
                                        <!-- Title & Favorite icon -->
                                        <div class="flex items-center gap-2 mb-1.5">
                                            <button
                                                type="button"
                                                @click="toggleFavorito(ejercicio, $event)"
                                                class="transition-colors text-base flex-shrink-0 cursor-pointer"
                                                :title="ejercicio.is_favorite ? 'Quitar de favoritos' : 'Marcar favorito'"
                                            >
                                                <i v-if="ejercicio.is_favorite" class="ph-fill ph-star text-amber-400"></i>
                                                <i v-else class="ph ph-star text-slate-500 hover:text-amber-400"></i>
                                            </button>
                                            <h3 class="font-display font-bold text-sm text-white truncate tracking-tight" :title="ejercicio.nombre">
                                                {{ ejercicio.nombre }}
                                            </h3>
                                        </div>

                                        <!-- Tags Badges Grid -->
                                        <div class="flex flex-wrap items-center gap-1.5 mb-2">
                                            <span
                                                v-if="ejercicio.equipamiento"
                                                class="px-2 py-0.5 rounded-md bg-blue-500/15 border border-blue-500/30 text-blue-300 text-[10px] font-medium"
                                            >
                                                {{ ejercicio.equipamiento }}
                                            </span>
                                            <span
                                                v-if="ejercicio.grupo_muscular"
                                                class="px-2 py-0.5 rounded-md bg-neon-violet/15 border border-neon-violet/30 text-indigo-200 text-[10px] font-medium"
                                            >
                                                {{ ejercicio.grupo_muscular }}
                                            </span>
                                            <span
                                                v-if="ejercicio.dificultad"
                                                class="px-2 py-0.5 rounded-md text-[10px] font-semibold capitalize"
                                                :class="getDificultadBadge(ejercicio.dificultad)"
                                            >
                                                {{ ejercicio.dificultad }}
                                            </span>
                                        </div>

                                        <!-- Last Trained Info -->
                                        <div class="flex items-center gap-1.5 text-[11px] text-slate-400">
                                            <template v-if="isRecent(ejercicio.last_trained_at)">
                                                <i class="ph ph-calendar-check text-neon-emerald"></i>
                                                <span>Entrenado: <span class="text-emerald-400 font-semibold">{{ relativeTime(ejercicio.last_trained_at).text }}</span></span>
                                            </template>
                                            <template v-else>
                                                <i class="ph ph-clock text-slate-500"></i>
                                                <span>Última vez: <span class="text-slate-300 font-medium">{{ relativeTime(ejercicio.last_trained_at).text }}</span></span>
                                            </template>
                                        </div>
                                    </div>

                                    <!-- Actions Column: Check / Add -->
                                    <div class="flex flex-col items-center gap-2 shrink-0">
                                        <button
                                            type="button"
                                            @click="quickLog(ejercicio, $event)"
                                            title="Marcar como hecho hoy"
                                            aria-label="Marcar como hecho hoy"
                                            class="w-8 h-8 rounded-lg flex items-center justify-center active:scale-90 transition-all cursor-pointer"
                                            :class="[
                                                isTrainedToday(ejercicio.last_trained_at)
                                                    ? 'bg-emerald-500/20 border border-emerald-500/50 text-emerald-300 shadow-glow-emerald'
                                                    : 'bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 hover:bg-emerald-500/20'
                                            ]"
                                        >
                                            <i class="ph-bold ph-check text-sm"></i>
                                        </button>
                                        <button
                                            type="button"
                                            @click.stop="verDetalle(ejercicio)"
                                            title="Ver detalles y video"
                                            aria-label="Ver detalles"
                                            class="w-8 h-8 rounded-lg bg-obsidian-800 border border-white/[0.08] text-slate-400 hover:text-white flex items-center justify-center active:scale-90 transition-all cursor-pointer"
                                        >
                                            <i class="ph ph-plus text-sm"></i>
                                        </button>
                                    </div>
                                </div>
                            </article>
                        </div>

                        <!-- Empty State -->
                        <div v-if="ejercicios.length === 0" class="glass-card rounded-2xl p-8 text-center space-y-3">
                            <div class="w-12 h-12 rounded-2xl bg-neon-indigo/10 border border-neon-indigo/30 flex items-center justify-center mx-auto text-neon-indigo">
                                <i class="ph ph-magnifying-glass text-2xl"></i>
                            </div>
                            <h3 class="font-display font-bold text-base text-white">No se encontraron ejercicios</h3>
                            <p class="text-xs text-slate-400 max-w-sm mx-auto">
                                No hay resultados que coincidan con los filtros seleccionados. Probá limpiando la búsqueda o seleccionando otro grupo muscular.
                            </p>
                            <button
                                type="button"
                                @click="limpiarBusqueda"
                                class="px-4 py-2 rounded-xl bg-neon-indigo text-white text-xs font-semibold shadow-glow-indigo active:scale-95 transition-all inline-flex items-center gap-1.5 cursor-pointer"
                            >
                                <i class="ph ph-arrow-counter-clockwise"></i>
                                <span>Limpiar filtros</span>
                            </button>
                        </div>
                    </section>
                    <!-- END: ExercisesListSection -->

                    <!-- BEGIN: PaginationSection -->
                    <section v-if="totalPages > 1" class="py-6 text-center space-y-3" data-purpose="pagination-controls">
                        <p class="text-xs text-slate-400 font-medium">
                            Mostrando <span class="text-white font-semibold">{{ ejercicios.length }}</span> ejercicios de <span class="text-white font-semibold">{{ total.toLocaleString() }}</span> en total
                        </p>

                        <!-- Pagination Buttons Row -->
                        <div class="flex items-center justify-center gap-1.5">
                            <!-- Previous Page -->
                            <button
                                type="button"
                                @click="cambiarPagina(paginaActual - 1)"
                                :disabled="paginaActual === 1"
                                :class="[
                                    'px-3 py-2 rounded-xl text-xs font-semibold flex items-center gap-1 transition-all',
                                    paginaActual === 1
                                        ? 'bg-obsidian-850 border border-white/[0.06] text-slate-500 cursor-not-allowed'
                                        : 'bg-obsidian-800 border border-white/[0.1] text-white hover:bg-obsidian-700 active:scale-95 cursor-pointer'
                                ]"
                            >
                                <i class="ph ph-caret-left text-xs"></i>
                                <span>Anterior</span>
                            </button>

                            <!-- Numeric Buttons -->
                            <button
                                v-for="page in visiblePages"
                                :key="page"
                                type="button"
                                @click="cambiarPagina(page)"
                                :class="[
                                    'w-9 h-9 rounded-xl font-display font-bold text-xs flex items-center justify-center transition-all cursor-pointer',
                                    page === paginaActual
                                        ? 'bg-neon-indigo text-white shadow-glow-indigo'
                                        : 'bg-obsidian-850 border border-white/[0.06] text-slate-300 hover:text-white hover:border-white/20'
                                ]"
                            >
                                {{ page }}
                            </button>

                            <!-- Next Page -->
                            <button
                                type="button"
                                @click="cambiarPagina(paginaActual + 1)"
                                :disabled="paginaActual === totalPages"
                                :class="[
                                    'px-3 py-2 rounded-xl text-xs font-semibold flex items-center gap-1 transition-all',
                                    paginaActual === totalPages
                                        ? 'bg-obsidian-850 border border-white/[0.06] text-slate-500 cursor-not-allowed'
                                        : 'bg-obsidian-800 border border-white/[0.1] text-white hover:bg-obsidian-700 active:scale-95 cursor-pointer'
                                ]"
                            >
                                <span>Sig.</span>
                                <i class="ph ph-caret-right text-xs"></i>
                            </button>
                        </div>
                    </section>
                    <!-- END: PaginationSection -->
                </div>
            </div>
        </main>

        <!-- Modal de detalle del ejercicio (con video player e información) -->
        <EjercicioDetailModal v-model:open="mostrarDetail" :ejercicio="ejercicioSeleccionado" />

        <!-- Modal expand del body map (vista full) -->
        <Teleport to="body">
            <div
                v-if="bodyMapExpandido"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md"
                @click.self="bodyMapExpandido = false"
                role="dialog"
                aria-modal="true"
                aria-label="Mapa corporal expandido"
            >
                <div class="glass-card rounded-2xl shadow-2xl border border-white/[0.1] w-full max-w-2xl max-h-[90vh] overflow-y-auto p-5 space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-white/[0.08]">
                        <div>
                            <h3 class="font-display font-bold text-lg text-white">Mapa Corporal Expandido</h3>
                            <p class="text-xs text-slate-400">Tocá cualquier músculo para filtrar los ejercicios</p>
                        </div>
                        <button
                            @click="bodyMapExpandido = false"
                            class="w-8 h-8 rounded-xl bg-obsidian-800 border border-white/[0.08] flex items-center justify-center text-slate-400 hover:text-white cursor-pointer"
                            aria-label="Cerrar"
                        >
                            <i class="ph ph-x text-base"></i>
                        </button>
                    </div>

                    <div class="py-2">
                        <BodyMap
                            :levels="bodyMapLevels"
                            :muscle-labels="muscleLabels"
                            mode="balance"
                            :show-gender-toggle="true"
                            @muscle-click="(slug) => { onMuscleClickBodyMap(slug); bodyMapExpandido = false; }"
                        />
                    </div>

                    <div v-if="musculoFiltroBodyMap" class="flex items-center justify-between bg-neon-indigo/15 border border-neon-indigo/30 rounded-xl p-3 text-xs text-indigo-200">
                        <span>Filtrando por: <strong class="text-white">{{ muscleLabels[musculoFiltroBodyMap] || musculoFiltroBodyMap }}</strong></span>
                        <button @click="limpiarFiltroMusculo" class="font-bold underline text-white hover:text-indigo-300 cursor-pointer">Limpiar</button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Modal Crear Ejercicio (Trainer/Admin) -->
        <Teleport to="body">
            <div
                v-if="mostrarModal"
                class="fixed inset-0 bg-black/80 backdrop-blur-md flex items-center justify-center z-50 p-4"
                @click.self="mostrarModal = false"
                role="dialog"
                aria-modal="true"
            >
                <div class="glass-card rounded-2xl p-6 w-full max-w-lg shadow-2xl border border-white/[0.1] space-y-4">
                    <div class="flex items-center justify-between border-b border-white/[0.08] pb-3">
                        <h3 class="font-display text-lg font-bold text-white">Agregar Ejercicio</h3>
                        <button
                            @click="mostrarModal = false"
                            class="w-8 h-8 rounded-xl bg-obsidian-800 border border-white/[0.08] flex items-center justify-center text-slate-400 hover:text-white cursor-pointer"
                        >
                            <i class="ph ph-x text-base"></i>
                        </button>
                    </div>

                    <div class="space-y-3 text-xs">
                        <div>
                            <label class="block font-semibold uppercase tracking-wider text-slate-400 mb-1">Nombre *</label>
                            <input
                                v-model="nuevo.nombre"
                                type="text"
                                placeholder="Ej: Curl banco Scott"
                                class="w-full bg-obsidian-850 border border-white/[0.1] rounded-xl px-3.5 py-2.5 text-white outline-none focus:border-neon-indigo"
                            />
                        </div>

                        <div>
                            <label class="block font-semibold uppercase tracking-wider text-slate-400 mb-1">Equipamiento *</label>
                            <input
                                v-model="nuevo.equipamiento"
                                type="text"
                                placeholder="Ej: Mancuernas, Barra Z"
                                class="w-full bg-obsidian-850 border border-white/[0.1] rounded-xl px-3.5 py-2.5 text-white outline-none focus:border-neon-indigo"
                            />
                        </div>

                        <div>
                            <label class="block font-semibold uppercase tracking-wider text-slate-400 mb-1">Grupo Muscular</label>
                            <input
                                v-model="nuevo.grupo_muscular"
                                type="text"
                                placeholder="Ej: Bíceps, Pecho, Espalda"
                                class="w-full bg-obsidian-850 border border-white/[0.1] rounded-xl px-3.5 py-2.5 text-white outline-none focus:border-neon-indigo"
                            />
                        </div>

                        <div>
                            <label class="block font-semibold uppercase tracking-wider text-slate-400 mb-1">Dificultad</label>
                            <select
                                v-model="nuevo.dificultad"
                                class="w-full bg-obsidian-850 border border-white/[0.1] rounded-xl px-3 py-2.5 text-white outline-none focus:border-neon-indigo"
                            >
                                <option value="principiante">Principiante</option>
                                <option value="intermedio">Intermedio</option>
                                <option value="avanzado">Avanzado</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-semibold uppercase tracking-wider text-slate-400 mb-1">Descripción</label>
                            <textarea
                                v-model="nuevo.descripcion"
                                rows="3"
                                placeholder="Instrucciones o notas de técnica..."
                                class="w-full bg-obsidian-850 border border-white/[0.1] rounded-xl px-3.5 py-2 text-white outline-none focus:border-neon-indigo"
                            ></textarea>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button
                            @click="mostrarModal = false"
                            class="px-4 py-2 text-slate-400 hover:text-white rounded-xl text-xs font-semibold transition-all cursor-pointer"
                        >
                            Cancelar
                        </button>
                        <button
                            @click="agregar"
                            class="bg-neon-indigo hover:bg-indigo-600 text-white px-5 py-2 rounded-xl text-xs font-semibold shadow-glow-indigo transition-all active:scale-95 cursor-pointer"
                        >
                            Agregar Ejercicio
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, nextTick } from 'vue';
import axios from 'axios';
import { useToast } from '../composables/useToast';
import { cachedAxiosGet } from '../composables/useApiCache';
import EjercicioDetailModal from './EjercicioDetailModal.vue';
import BodyMap from './BodyMap.vue';
import maleFrontPaths from '../lib/bodyPaths/male-front.js';
import maleBackPaths from '../lib/bodyPaths/male-back.js';
import { storeToRefs } from 'pinia';
import { useAuthStore } from '../stores/auth';

const auth = useAuthStore();
// El store expone `name` y `role` como refs separados (no `user`).
const { name: userName, role: userRole } = storeToRefs(auth);
const toast = useToast();

const userInitials = computed(() => {
    if (!userName.value) return 'GA';
    const parts = userName.value.trim().split(/\s+/);
    if (parts.length >= 2) return (parts[0][0] + parts[1][0]).toUpperCase();
    return parts[0].slice(0, 2).toUpperCase();
});

const streak = ref(2);

const ejercicios = ref([]);
const busqueda = ref('');
const grupoMuscularFiltro = ref('');
const gruposMusculares = ref([]);
const equipamientoFiltro = ref('');
const equipamientos = ref([]);
const dificultadFiltro = ref('');
const ordenAsc = ref(true);

const showAdvancedFilters = ref(false);
const toggleAdvancedFilters = () => {
    showAdvancedFilters.value = !showAdvancedFilters.value;
};

// === Vista Anatomía (Frente / Espalda) ===
const vistaAnatomia = ref('front');

// === Filtros desplegables ===
const grupoSelectOpen = ref(false);
const grupoSearchText = ref('');
const grupoDropdownRef = ref(null);
const grupoSearchInputRef = ref(null);

const equipoSelectOpen = ref(false);
const equipoSearchText = ref('');
const equipoDropdownRef = ref(null);
const equipoSearchInputRef = ref(null);

const dificultadSelectOpen = ref(false);
const dificultadDropdownRef = ref(null);

const chipsPopulares = ['Bíceps', 'Espalda', 'Abdomen', 'Pecho', 'Piernas', 'Hombros', 'Tríceps', 'Glúteos', 'Pantorrillas'];

const toggleGrupoDropdown = () => {
    grupoSelectOpen.value = !grupoSelectOpen.value;
    equipoSelectOpen.value = false;
    dificultadSelectOpen.value = false;
    if (grupoSelectOpen.value) {
        grupoSearchText.value = '';
        nextTick(() => grupoSearchInputRef.value?.focus());
    }
};

const toggleEquipoDropdown = () => {
    equipoSelectOpen.value = !equipoSelectOpen.value;
    grupoSelectOpen.value = false;
    dificultadSelectOpen.value = false;
    if (equipoSelectOpen.value) {
        equipoSearchText.value = '';
        nextTick(() => equipoSearchInputRef.value?.focus());
    }
};

const toggleDificultadDropdown = () => {
    dificultadSelectOpen.value = !dificultadSelectOpen.value;
    grupoSelectOpen.value = false;
    equipoSelectOpen.value = false;
};

const gruposFiltrados = computed(() => {
    if (!grupoSearchText.value.trim()) return gruposMusculares.value;
    const q = grupoSearchText.value.toLowerCase().trim();
    return gruposMusculares.value.filter((g) => g.toLowerCase().includes(q));
});

const equipamientosFiltrados = computed(() => {
    if (!equipoSearchText.value.trim()) return equipamientos.value;
    const q = equipoSearchText.value.toLowerCase().trim();
    return equipamientos.value.filter((e) => e.toLowerCase().includes(q));
});

const seleccionarGrupo = (grupo) => {
    grupoMuscularFiltro.value = grupo;
    grupoSelectOpen.value = false;
    grupoSearchText.value = '';
    fetchEjercicios(1);
};

const seleccionarEquipamiento = (eq) => {
    equipamientoFiltro.value = eq;
    equipoSelectOpen.value = false;
    equipoSearchText.value = '';
    fetchEjercicios(1);
};

const seleccionarDificultad = (dif) => {
    dificultadFiltro.value = dif;
    dificultadSelectOpen.value = false;
    fetchEjercicios(1);
};

const seleccionarChip = (chip) => {
    if (grupoMuscularFiltro.value === chip) {
        grupoMuscularFiltro.value = '';
    } else {
        grupoMuscularFiltro.value = chip;
    }
    musculoFiltroBodyMap.value = '';
    fetchEjercicios(1);
};

const limpiarFiltrosRapidos = () => {
    grupoMuscularFiltro.value = '';
    musculoFiltroBodyMap.value = '';
    ejercicioAComparar.value = null;
    fetchEjercicios(1);
};

const handleClickOutsideFiltros = (e) => {
    if (grupoDropdownRef.value && !grupoDropdownRef.value.contains(e.target)) {
        grupoSelectOpen.value = false;
    }
    if (equipoDropdownRef.value && !equipoDropdownRef.value.contains(e.target)) {
        equipoSelectOpen.value = false;
    }
    if (dificultadDropdownRef.value && !dificultadDropdownRef.value.contains(e.target)) {
        dificultadSelectOpen.value = false;
    }
};

const mostrarModal = ref(false);
const ejercicioSeleccionado = ref(null);
const mostrarDetail = ref(false);

const verDetalle = (ej) => {
    ejercicioSeleccionado.value = ej;
    mostrarDetail.value = true;
};

const paginaActual = ref(1);
const totalPages = ref(1);
const total = ref(0);

const nuevo = ref({
    nombre: '',
    equipamiento: '',
    grupo_muscular: '',
    descripcion: '',
    dificultad: 'intermedio',
});

// === Body map & Músculos (High-Definition Anatomy) ===
const currentAnatomyData = computed(() => {
    return vistaAnatomia.value === 'front' ? maleFrontPaths : maleBackPaths;
});

const neutralSlugs = ['head', 'hair', 'hands', 'knees', 'ankles', 'feet'];
const isInteractiveMuscle = (slug) => !neutralSlugs.includes(slug);

const hoveredMuscleSlug = ref(null);
const onMuscleHover = (slug) => {
    if (isInteractiveMuscle(slug)) hoveredMuscleSlug.value = slug;
};
const onMuscleLeave = () => {
    hoveredMuscleSlug.value = null;
};

const muscleDataMap = {
    chest: { group: 'Pecho', label: 'Pecho / Pectorales', dbSlug: 'pectoral-major' },
    abs: { group: 'Abdomen', label: 'Abdomen', dbSlug: 'rectus-abdominis' },
    obliques: { group: 'Abdomen', label: 'Oblicuos', dbSlug: 'obliques' },
    biceps: { group: 'Bíceps', label: 'Bíceps', dbSlug: 'biceps' },
    triceps: { group: 'Tríceps', label: 'Tríceps', dbSlug: 'triceps' },
    deltoids: { group: 'Hombros', label: 'Hombros / Deltoides', dbSlug: 'deltoid-anterior' },
    trapezius: { group: 'Espalda', label: 'Trapecios', dbSlug: 'trapezius' },
    'upper-back': { group: 'Espalda', label: 'Espalda alta / Dorsales', dbSlug: 'latissimus-dorsi' },
    'lower-back': { group: 'Espalda', label: 'Espalda baja / Lumbar', dbSlug: 'lower-back' },
    forearm: { group: 'Antebrazos', label: 'Antebrazos', dbSlug: 'forearms' },
    gluteal: { group: 'Glúteos', label: 'Glúteos', dbSlug: 'gluteus-maximus' },
    quadriceps: { group: 'Piernas', label: 'Cuádriceps', dbSlug: 'quadriceps' },
    hamstring: { group: 'Piernas', label: 'Isquiotibiales / Femorales', dbSlug: 'hamstrings' },
    calves: { group: 'Pantorrillas', label: 'Gemelos / Pantorrillas', dbSlug: 'gastrocnemius' },
    tibialis: { group: 'Pantorrillas', label: 'Tibial anterior', dbSlug: 'tibialis' },
    neck: { group: 'Cuello', label: 'Cuello', dbSlug: 'neck' },
    adductors: { group: 'Piernas', label: 'Aductores', dbSlug: 'adductors' },
    serratus: { group: 'Abdomen', label: 'Serratos', dbSlug: 'serratus' },
    'hip-flexors': { group: 'Piernas', label: 'Flexores de cadera', dbSlug: 'hip-flexors' },
};

const musculosCatalogo = ref([]);
const ejercicioAComparar = ref(null);
const musculoFiltroBodyMap = ref('');
const bodyMapExpandido = ref(false);
const muscleRecency = ref([]);

const muscleLabels = computed(() => {
    const map = {};
    for (const m of musculosCatalogo.value) map[m.slug] = m.nombre_es;
    return map;
});

const getMuscleTitle = (slug) => {
    if (neutralSlugs.includes(slug)) return '';
    return muscleDataMap[slug]?.label || muscleLabels.value[slug] || slug;
};

const activeMuscleLabel = computed(() => {
    if (musculoFiltroBodyMap.value && muscleDataMap[musculoFiltroBodyMap.value]) {
        return `${muscleDataMap[musculoFiltroBodyMap.value].label} activo`;
    }
    if (grupoMuscularFiltro.value) {
        return `${grupoMuscularFiltro.value} activo`;
    }
    if (ejercicioAComparar.value) {
        return `${ejercicioAComparar.value.nombre} activo`;
    }
    return 'Toca un músculo para filtrar';
});

const isMuscleActive = (slug) => {
    if (neutralSlugs.includes(slug)) return false;
    if (musculoFiltroBodyMap.value === slug) return true;
    const info = muscleDataMap[slug];
    if (grupoMuscularFiltro.value && info?.group) {
        const gf = grupoMuscularFiltro.value.toLowerCase();
        const grp = info.group.toLowerCase();
        if (gf === grp || gf.includes(grp) || grp.includes(gf)) return true;
    }
    if (ejercicioAComparar.value?.musculos) {
        const matchesExercise = ejercicioAComparar.value.musculos.some((m) => {
            const mSlug = m.slug?.toLowerCase();
            const mName = m.nombre_es?.toLowerCase();
            return (
                mSlug === slug ||
                mSlug === info?.dbSlug ||
                (info?.group && mName?.includes(info.group.toLowerCase()))
            );
        });
        if (matchesExercise) return true;
    }
    return false;
};

const getMuscleFill = (slug) => {
    if (neutralSlugs.includes(slug)) {
        return '#141c2e'; // Dark obsidian base for joints/head/feet
    }
    if (isMuscleActive(slug)) {
        return '#6366f1'; // Glowing Neon Indigo
    }
    if (hoveredMuscleSlug.value === slug) {
        return '#4f46e5'; // Bright Indigo
    }
    const rec = muscleRecency.value?.find((r) => {
        const info = muscleDataMap[slug];
        return r.slug === slug || r.slug === info?.dbSlug;
    });
    if (rec) {
        if (rec.days_since <= 3) return '#3730a3';
        if (rec.days_since <= 14) return '#312e81';
        return '#1e293b';
    }
    return '#1e293b';
};

const getMuscleStroke = (slug) => {
    if (neutralSlugs.includes(slug)) {
        return 'rgba(255, 255, 255, 0.05)';
    }
    if (isMuscleActive(slug)) {
        return '#c7d2fe';
    }
    if (hoveredMuscleSlug.value === slug) {
        return '#38bdf8';
    }
    return 'rgba(255, 255, 255, 0.12)';
};

const getMuscleStrokeWidth = (slug) => {
    if (isMuscleActive(slug)) return 1.8;
    if (hoveredMuscleSlug.value === slug) return 1.6;
    return 0.8;
};

const onMuscleClick = (slug) => {
    if (!isInteractiveMuscle(slug)) return;
    const info = muscleDataMap[slug];
    const groupName = info?.group || slug;

    if (musculoFiltroBodyMap.value === slug || grupoMuscularFiltro.value === groupName) {
        musculoFiltroBodyMap.value = '';
        grupoMuscularFiltro.value = '';
    } else {
        musculoFiltroBodyMap.value = slug;
        grupoMuscularFiltro.value = groupName;
    }
    ejercicioAComparar.value = null;
    fetchEjercicios(1);
};

const recencyLevels = computed(() => {
    const levels = {};
    for (const r of muscleRecency.value) {
        const d = r.days_since;
        if (d <= 3) levels[r.slug] = 0;
        else if (d <= 7) levels[r.slug] = 1;
        else if (d <= 14) levels[r.slug] = 2;
        else if (d <= 30) levels[r.slug] = 3;
        else levels[r.slug] = 4;
    }
    return levels;
});

const bodyMapLevels = computed(() => {
    const ej = ejercicioAComparar.value;
    if (ej?.musculos) {
        const levels = {};
        for (const m of ej.musculos) {
            levels[m.slug] = m.pivot?.tipo === 'primario' ? 4 : m.pivot?.tipo === 'secundario' ? 2 : 1;
        }
        return levels;
    }
    return recencyLevels.value;
});

const filtrarPorMusculo = (slug, nombre) => {
    onMuscleClick(slug);
};

const onMuscleClickBodyMap = (slug) => {
    onMuscleClick(slug);
};

const limpiarFiltroMusculo = () => {
    musculoFiltroBodyMap.value = '';
    grupoMuscularFiltro.value = '';
    fetchEjercicios(1);
};

const seleccionarEjercicioBodyMap = (ej) => {
    if (ejercicioAComparar.value?.id === ej.id) {
        ejercicioAComparar.value = null;
    } else {
        ejercicioAComparar.value = ej;
    }
};

const fetchUserInfo = async () => {
    try {
        await auth.fetchUser();
    } catch (error) {
        console.error('Error al obtener usuario:', error);
    }
};

const fetchStreak = async () => {
    try {
        // El endpoint correcto es /api/stats/resumen (incluye current_streak).
        // /api/stats a secas no existe → 404.
        const response = await cachedAxiosGet('/api/stats/resumen', {}, { ttl: 60_000 });
        if (response.data?.current_streak !== undefined) {
            streak.value = response.data.current_streak;
        }
    } catch (e) {
        // default 2
    }
};

const fetchGruposMusculares = async () => {
    try {
        const response = await cachedAxiosGet('/api/ejercicios/grupos-musculares', {}, { ttl: 5 * 60_000 });
        gruposMusculares.value = response.data || [];
    } catch (error) {
        console.error('Error al obtener grupos musculares:', error);
    }
};

const fetchEquipamientos = async () => {
    try {
        const response = await cachedAxiosGet('/api/ejercicios/equipamientos', {}, { ttl: 5 * 60_000 });
        equipamientos.value = response.data || [];
    } catch (error) {
        console.error('Error al obtener equipamientos:', error);
    }
};

const fetchMusculos = async () => {
    try {
        const response = await cachedAxiosGet('/api/musculos', {}, { ttl: 60 * 60_000 });
        musculosCatalogo.value = response.data || [];
    } catch (error) {
        console.error('Error al obtener músculos:', error);
    }
};

const fetchMuscleRecency = async () => {
    try {
        const response = await cachedAxiosGet('/api/body-map/muscle-recency', {}, { ttl: 5 * 60_000 });
        muscleRecency.value = response.data?.recency || [];
    } catch (error) {
        console.error('Error al obtener recencia:', error);
    }
};

const fetchEjercicios = async (page = 1) => {
    try {
        const params = { page };
        if (busqueda.value) params.busqueda = busqueda.value;
        if (grupoMuscularFiltro.value) params.grupo_muscular = grupoMuscularFiltro.value;
        if (equipamientoFiltro.value) params.equipamiento = equipamientoFiltro.value;
        if (dificultadFiltro.value) params.dificultad = dificultadFiltro.value;
        if (musculoFiltroBodyMap.value) params.musculo_slug = musculoFiltroBodyMap.value;
        params.orden = ordenAsc.value ? 'asc' : 'desc';

        const response = await axios.get('/api/ejercicios', { params });
        ejercicios.value = response.data.data || response.data || [];
        paginaActual.value = response.data.current_page || 1;
        totalPages.value = response.data.last_page || 1;
        total.value = response.data.total || ejercicios.value.length;
    } catch (error) {
        console.error('Error al cargar ejercicios:', error);
    }
};

const toggleOrden = () => {
    ordenAsc.value = !ordenAsc.value;
    fetchEjercicios(1);
};

function relativeTime(fechaStr) {
    if (!fechaStr) return { text: 'Nunca', color: 'gray' };
    const fecha = new Date(fechaStr);
    const ahora = new Date();
    const diffMs = ahora - fecha;
    const diffDias = Math.floor(diffMs / (1000 * 60 * 60 * 24));

    if (diffDias === 0) return { text: 'Hoy', color: 'green' };
    if (diffDias === 1) return { text: 'Ayer', color: 'green' };
    if (diffDias < 7) return { text: `Hace ${diffDias} días`, color: 'green' };
    if (diffDias < 14) return { text: 'Hace 1 semana', color: 'yellow' };
    if (diffDias < 30) return { text: `Hace ${Math.floor(diffDias / 7)} sem`, color: 'yellow' };
    return { text: `Hace ${Math.floor(diffDias / 30)} mes`, color: 'orange' };
}

function isRecent(fechaStr) {
    if (!fechaStr) return false;
    const fecha = new Date(fechaStr);
    const ahora = new Date();
    const diffDias = Math.floor((ahora - fecha) / (1000 * 60 * 60 * 24));
    return diffDias <= 2;
}

function isTrainedToday(fechaStr) {
    if (!fechaStr) return false;
    const fecha = new Date(fechaStr);
    const ahora = new Date();
    return (
        fecha.getDate() === ahora.getDate() &&
        fecha.getMonth() === ahora.getMonth() &&
        fecha.getFullYear() === ahora.getFullYear()
    );
}

function getBorderColor(ejercicio) {
    const gm = (ejercicio.grupo_muscular || '').toLowerCase();
    if (gm.includes('pecho') || gm.includes('bicep') || gm.includes('bícep')) return 'border-l-neon-indigo';
    if (gm.includes('abdomen') || gm.includes('core')) return 'border-l-neon-violet';
    if (gm.includes('espalda') || gm.includes('dorsal')) return 'border-l-blue-500';
    if (gm.includes('pierna') || gm.includes('gemelo') || gm.includes('pantorrilla')) return 'border-l-cyan-500';
    if (gm.includes('hombro') || gm.includes('trapecio')) return 'border-l-purple-500';
    return 'border-l-neon-indigo';
}

function getDificultadBadge(dif) {
    if (dif === 'principiante') return 'bg-emerald-500/15 border border-emerald-500/30 text-emerald-300';
    if (dif === 'avanzado') return 'bg-purple-500/15 border border-purple-500/30 text-purple-300';
    return 'bg-amber-500/15 border border-amber-500/30 text-amber-300';
}

const toggleFavorito = async (ej, ev) => {
    if (ev) ev.stopPropagation();
    const prev = ej.is_favorite;
    ej.is_favorite = !prev;
    try {
        await axios.post(`/api/ejercicios/${ej.id}/favorite`);
        toast.success(ej.is_favorite ? 'Agregado a favoritos' : 'Eliminado de favoritos');
    } catch (err) {
        console.error('Error al togglear favorito:', err);
        ej.is_favorite = prev;
        toast.error('No se pudo actualizar el favorito');
    }
};

const quickLog = async (ej, ev) => {
    if (ev) ev.stopPropagation();
    try {
        await axios.post(`/api/ejercicios/${ej.id}/quick-log`);
        ej.last_trained_at = new Date().toISOString();
        toast.success(`✓ ${ej.nombre} marcado como hecho hoy`);
    } catch (err) {
        console.error('Error en quick log:', err);
        toast.error('No se pudo registrar el ejercicio');
    }
};

const visiblePages = computed(() => {
    const pages = [];
    const maxVisible = 5;
    let start = Math.max(1, paginaActual.value - Math.floor(maxVisible / 2));
    let end = Math.min(totalPages.value, start + maxVisible - 1);

    if (end - start < maxVisible - 1) {
        start = Math.max(1, end - maxVisible + 1);
    }

    for (let i = start; i <= end; i++) {
        pages.push(i);
    }
    return pages;
});

const cambiarPagina = (page) => {
    if (page >= 1 && page <= totalPages.value) {
        fetchEjercicios(page);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
};

const buscar = () => {
    fetchEjercicios(1);
};

const limpiarBusqueda = () => {
    busqueda.value = '';
    grupoMuscularFiltro.value = '';
    equipamientoFiltro.value = '';
    dificultadFiltro.value = '';
    musculoFiltroBodyMap.value = '';
    grupoSearchText.value = '';
    equipoSearchText.value = '';
    grupoSelectOpen.value = false;
    equipoSelectOpen.value = false;
    dificultadSelectOpen.value = false;
    ejercicioAComparar.value = null;
    fetchEjercicios(1);
};

const agregar = async () => {
    if (!nuevo.value.nombre || !nuevo.value.equipamiento) {
        toast.error('Nombre y equipamiento son requeridos');
        return;
    }
    try {
        await axios.post('/api/ejercicios', nuevo.value);
        nuevo.value = {
            nombre: '',
            equipamiento: '',
            grupo_muscular: '',
            descripcion: '',
            dificultad: 'intermedio',
        };
        mostrarModal.value = false;
        toast.success('Ejercicio agregado correctamente');
        fetchEjercicios(1);
    } catch (error) {
        console.error('Error al agregar:', error);
        toast.error('Error al crear el ejercicio');
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutsideFiltros);
    fetchUserInfo();
    fetchStreak();
    fetchGruposMusculares();
    fetchEquipamientos();
    fetchMusculos();
    fetchMuscleRecency();
    fetchEjercicios(1);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutsideFiltros);
});
</script>

<style scoped>
/* Kinetic Obsidian specific adjustments */
</style>
