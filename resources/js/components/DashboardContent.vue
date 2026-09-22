<template>
    <div
        class="min-h-screen bg-[#090D16] text-gray-100 py-0 pb-28 md:py-6 md:pb-8 overflow-x-hidden"
    >
        <SyncBadge :pending="offline.pendingCount.value" :syncing="offline.isSyncing.value" />

        <!-- Indicador pull-to-refresh (mobile) -->
        <div
            v-show="pullOffset > 4 || isRefreshing"
            :style="{ height: pullOffset + 'px' }"
            class="md:hidden flex items-center justify-center overflow-hidden transition-[height] duration-150 max-w-md mx-auto"
            aria-live="polite"
            role="status"
        >
            <div
                class="flex flex-col items-center gap-1 text-xs font-semibold text-gray-400"
            >
                <svg
                    class="w-5 h-5 animate-spin text-[#6366F1]"
                    v-if="isRefreshing"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <circle
                        class="opacity-25"
                        cx="12"
                        cy="12"
                        r="10"
                        stroke="currentColor"
                        stroke-width="4"
                    ></circle>
                    <path
                        class="opacity-75"
                        fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                    ></path>
                </svg>
                <svg
                    class="w-5 h-5 text-[#6366F1]"
                    v-else
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M19 14l-7 7m0 0l-7-7m7 7V3"
                    />
                </svg>
                <span>{{ isRefreshing ? 'Actualizando…' : 'Deslizá hacia abajo' }}</span>
            </div>
        </div>

        <template v-if="rutinaStore.seleccionada">
            <!-- ============================================================ -->
            <!-- MOBILE-FIRST: shell exacto del mockup Figma Kinetic Obsidian -->
            <!-- ============================================================ -->
            <div class="md:hidden w-full">
                <!-- BEGIN: TopBar (sticky) -->
                <header
                    class="sticky top-0 z-40 bg-[#090D16]/90 backdrop-blur-md px-5 pt-3 pb-3 border-b border-[#232F4D]/60 flex items-center justify-between"
                >
                    <!-- Brand + saludo -->
                    <div class="flex items-center gap-3">
                        <div
                            class="h-10 w-10 rounded-xl bg-gradient-to-tr from-[#6366F1] to-[#8B5CF6] flex items-center justify-center shadow-lg shadow-[#6366F1]/25 ring-1 ring-white/20"
                        >
                            <span class="text-white font-extrabold text-xl tracking-tight">G</span>
                        </div>
                        <div>
                            <span
                                class="text-[11px] font-bold tracking-widest text-[#8B5CF6] uppercase"
                            >
                                {{ diaSemanaEs }}, {{ fechaCorta }}
                            </span>
                            <h1 class="text-base font-bold text-white flex items-center gap-1.5">
                                ¡A entrenar, {{ userFirstName }}!
                            </h1>
                        </div>
                    </div>
                    <!-- Streak chip + bell -->
                    <div class="flex items-center gap-2.5">
                        <div
                            v-if="stats.streak > 0"
                            class="flex items-center gap-1.5 bg-[#F59E0B]/10 border border-[#F59E0B]/30 px-3 py-1.5 rounded-full"
                            title="Racha activa de entrenamientos"
                        >
                            <svg
                                class="w-4 h-4 text-[#F59E0B] animate-pulse fill-current"
                                viewbox="0 0 24 24"
                            >
                                <path
                                    d="M12.7 1.8c-.4-.4-1.1-.3-1.3.2-.6 1.4-1.5 2.8-2.6 3.9C6.8 8.1 5 11.2 5 14.5 5 18.6 8.1 22 12 22s7-3.4 7-7.5c0-4.1-2.5-7.9-5-10.4-.6-.6-1-1.4-1.3-2.3zM12 20c-2.8 0-5-2.2-5-5 0-2.3 1.3-4.5 2.8-6.1.4-.4.8-.8 1.2-1.3.5 1.5 1.4 3 2.6 4.2 1.3 1.3 2.4 2.8 2.4 4.2 0 2.2-1.8 4-4 4z"
                                ></path>
                            </svg>
                            <span class="text-xs font-black text-[#F59E0B]">{{ stats.streak }} DÍAS</span>
                        </div>
                        <button
                            aria-label="Notificaciones"
                            type="button"
                            class="h-9 w-9 rounded-full bg-[#111726] border border-[#232F4D] flex items-center justify-center text-gray-300 hover:text-white transition-colors"
                        >
                            <svg
                                class="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                viewbox="0 0 24 24"
                            >
                                <path
                                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                ></path>
                            </svg>
                        </button>
                    </div>
                </header>
                <!-- END: TopBar -->

                <!-- BEGIN: MainContent -->
                <main class="px-4 pt-4 space-y-5 max-w-md mx-auto w-full pb-32">
                    <!-- Active session banner -->
                    <div
                        v-if="session.isActive"
                        class="relative overflow-hidden rounded-2xl border border-emerald-500/40 bg-gradient-to-r from-emerald-900/95 via-teal-900/95 to-violet-900/95 p-4 text-white shadow-xl flex flex-wrap items-center justify-between gap-3 animate-fade-in"
                    >
                        <div
                            class="pointer-events-none absolute -right-10 -top-10 w-40 h-40 rounded-full bg-emerald-500/30 blur-3xl"
                        ></div>
                        <div class="flex items-center gap-3">
                            <span class="relative flex h-3.5 w-3.5">
                                <span
                                    class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"
                                ></span>
                                <span
                                    class="relative inline-flex rounded-full h-3.5 w-3.5 bg-emerald-500"
                                ></span>
                            </span>
                            <div>
                                <p
                                    class="text-sm font-black tracking-tight flex items-center gap-2"
                                >
                                    <span>Entrenamiento en curso · {{ formattedActiveTime }}</span>
                                    <span
                                        v-if="session.isPaused"
                                        class="text-[10px] font-bold bg-amber-500/30 text-amber-300 px-1.5 py-0.5 rounded"
                                        >Pausado</span
                                    >
                                </p>
                                <p class="text-xs text-emerald-200/80">
                                    {{
                                        session.currentEjercicio?.nombre ||
                                        'Sesión iniciada'
                                    }}
                                    · {{ session.totalSeriesCompletadas }}/{{
                                        session.totalSeriesObjetivo
                                    }}
                                    series
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                @click="abrirModoEntrenamiento"
                                class="px-4 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-white text-xs font-bold shadow-md active:scale-95 transition-all"
                            >
                                ⚡ Continuar
                            </button>
                            <button
                                type="button"
                                @click="descartarSesion"
                                title="Descartar entrenamiento en curso"
                                aria-label="Descartar entrenamiento en curso"
                                class="px-3 py-2 rounded-xl bg-gray-800/80 hover:bg-rose-500/30 text-gray-300 hover:text-rose-200 text-xs font-semibold border border-gray-700/50 hover:border-rose-500/50 transition-all"
                            >
                                ✕
                            </button>
                        </div>
                    </div>

                    <!-- BEGIN: HeroCard (Principal Routine CTA) -->
                    <section
                        class="relative overflow-hidden rounded-3xl border border-[#8B5CF6]/30 p-5 shadow-xl shadow-[#1c1f38]/40"
                        style="
                            background: radial-gradient(
                                    circle at 80% 20%,
                                    rgba(139, 92, 246, 0.22) 0%,
                                    rgba(99, 102, 241, 0.05) 55%,
                                    transparent 70%
                                ),
                                linear-gradient(180deg, #1c1f38 0%, #14182b 50%, #0f1424 100%);
                        "
                        data-purpose="active-workout-hero"
                    >
                        <div class="flex items-start justify-between mb-3">
                            <div class="space-y-1">
                                <div
                                    class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-[#6366F1]/20 border border-[#8B5CF6]/30 text-[11px] font-semibold text-[#A78BFA]"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full bg-[#8B5CF6]"></span>
                                    {{ nombreRutina }}
                                </div>
                                <h2 class="text-2xl font-black text-white tracking-tight pt-1">
                                    {{ diaActual }}: {{ diaActualGrupo }}
                                </h2>
                                <p
                                    class="text-xs text-gray-300 font-medium flex items-center gap-2"
                                >
                                    <span>{{ ejerciciosDelDia.length }} ejercicios</span>
                                    <span class="text-gray-600">•</span>
                                    <span>{{ seriesTotales }} series</span>
                                    <span class="text-gray-600">•</span>
                                    <span>~{{ duracionEstimada }} min</span>
                                </p>
                            </div>
                            <button
                                v-if="!session.isActive"
                                aria-pressed="true"
                                type="button"
                                class="flex items-center gap-1.5 bg-emerald-500/10 border border-emerald-500/30 px-2.5 py-1 rounded-full text-[11px] font-semibold text-emerald-400"
                            >
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewbox="0 0 24 24">
                                    <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"></path>
                                </svg>
                                Focus Mode
                            </button>
                        </div>

                        <button
                            type="button"
                            @click="abrirModoEntrenamiento"
                            class="w-full mt-3 py-3.5 px-6 rounded-2xl bg-gradient-to-r from-[#6366F1] via-[#6366F1] to-[#8B5CF6] text-white font-bold text-base shadow-lg shadow-[#6366F1]/40 active:scale-[0.98] transition-all flex items-center justify-center gap-2 group"
                        >
                            <div class="w-7 h-7 rounded-full bg-white/20 flex items-center justify-center">
                                <svg
                                    class="w-4 h-4 fill-white translate-x-0.5"
                                    viewbox="0 0 24 24"
                                >
                                    <path d="M8 5v14l11-7z"></path>
                                </svg>
                            </div>
                            <span>Comenzar entrenamiento</span>
                        </button>

                        <div
                            class="mt-4 pt-3 border-t border-[#232F4D] flex items-center justify-between text-xs text-gray-400"
                        >
                            <span class="font-medium text-gray-300">Progreso de la sesión</span>
                            <span class="font-bold text-[#A78BFA]">
                                {{ seriesCompletadas }} / {{ seriesTotales }} series
                                ({{ progresoDia }}%)
                            </span>
                        </div>
                        <div class="w-full h-1.5 bg-[#111726] rounded-full mt-1.5 overflow-hidden">
                            <div
                                class="h-full bg-gradient-to-r from-[#8B5CF6] to-[#6366F1] rounded-full transition-all"
                                :style="{ width: progresoDia + '%' }"
                            ></div>
                        </div>
                    </section>
                    <!-- END: HeroCard -->

                    <!-- BEGIN: QuickStatsGrid -->
                    <section class="grid grid-cols-3 gap-2.5">
                        <div
                            class="bg-[#111726]/90 border border-[#232F4D] rounded-2xl p-3 flex flex-col justify-between"
                        >
                            <div
                                class="text-[10px] font-bold uppercase tracking-wider text-gray-400"
                            >
                                Sets 30 días
                            </div>
                            <div class="mt-1 flex items-baseline gap-1">
                                <span class="text-xl font-extrabold text-white">
                                    {{ stats.totalSets30d }}
                                </span>
                                <span
                                    v-if="stats.trend30d !== 0"
                                    class="text-[10px] font-semibold"
                                    :class="
                                        stats.trend30d > 0 ? 'text-emerald-400' : 'text-rose-400'
                                    "
                                >
                                    {{ stats.trend30d > 0 ? '+' : '' }}{{ stats.trend30d }}%
                                </span>
                            </div>
                            <div class="text-[10px] text-gray-300 mt-0.5">Volumen alto</div>
                        </div>
                        <div
                            class="bg-[#111726]/90 border border-[#232F4D] rounded-2xl p-3 flex flex-col justify-between"
                        >
                            <div
                                class="text-[10px] font-bold uppercase tracking-wider text-gray-400"
                            >
                                Último entreno
                            </div>
                            <div class="mt-1 text-lg font-extrabold text-white">
                                {{ ultimoEntrenoLabel }}
                            </div>
                            <div class="text-[10px] text-gray-300 mt-0.5">
                                {{ ultimoEntrenoSub }}
                            </div>
                        </div>
                        <div
                            class="bg-[#111726]/90 border border-[#232F4D] rounded-2xl p-3 flex flex-col justify-between"
                        >
                            <div
                                class="text-[10px] font-bold uppercase tracking-wider text-gray-400"
                            >
                                Racha actual
                            </div>
                            <div class="mt-1 flex items-center gap-1">
                                <span class="text-xl font-extrabold text-[#F59E0B]">
                                    {{ stats.streak }}
                                </span>
                                <span class="text-xs">🔥</span>
                            </div>
                            <div class="text-[10px] text-gray-300 mt-0.5">
                                Récord: {{ stats.longestStreak }} días
                            </div>
                        </div>
                    </section>
                    <!-- END: QuickStatsGrid -->

                    <!-- BEGIN: DaySelectorTabs -->
                    <section>
                        <div class="flex items-center justify-between mb-2">
                            <span
                                class="text-xs font-bold uppercase tracking-wider text-gray-400"
                                >Plan actual</span
                            >
                            <button
                                type="button"
                                @click="cambiarRutina"
                                class="text-xs font-semibold text-[#A78BFA] hover:text-[#8B5CF6] transition-colors"
                            >
                                Cambiar rutina
                            </button>
                        </div>
                        <div class="flex items-center gap-2 overflow-x-auto pb-1" style="-ms-overflow-style: none; scrollbar-width: none;">
                            <button
                                v-for="dia in todosLosDias"
                                :key="dia"
                                @click="cambiarDia(dia)"
                                type="button"
                                :class="[
                                    'px-4 py-2 rounded-xl text-xs shrink-0 whitespace-nowrap transition-colors',
                                    diaActual === dia
                                        ? 'bg-[#6366F1] text-white font-bold shadow-md shadow-[#6366F1]/30 ring-1 ring-[#A78BFA] flex items-center gap-1.5'
                                        : 'bg-[#111726]/90 text-gray-400 hover:text-white border border-[#232F4D] font-semibold',
                                ]"
                            >
                                <span
                                    v-if="diaActual === dia"
                                    class="w-2 h-2 rounded-full bg-white"
                                ></span>
                                {{ dia }}
                                <span v-if="diaGrupoPara(dia)" class="opacity-80">
                                    ({{ diaGrupoPara(dia) }})
                                </span>
                            </button>
                        </div>
                    </section>
                    <!-- END: DaySelectorTabs -->

                    <!-- BEGIN: ExerciseListSection -->
                    <section class="space-y-3" data-purpose="todays-exercise-list">
                        <div class="flex items-center justify-between pt-1">
                            <div class="flex items-baseline gap-2">
                                <h3
                                    class="text-sm font-bold text-white uppercase tracking-wider"
                                >
                                    Ejercicios de hoy
                                </h3>
                                <span class="text-xs font-semibold text-gray-300">
                                    {{ ejerciciosCompletadosCount }}/{{
                                        ejerciciosDelDia.length
                                    }}
                                    completados
                                </span>
                            </div>
                            <button
                                type="button"
                                @click="expandirTodos = !expandirTodos"
                                class="text-xs font-medium text-gray-400 bg-[#111726] border border-[#232F4D] px-2.5 py-1 rounded-lg hover:text-white"
                            >
                                {{ expandirTodos ? 'Colapsar' : 'Expandir todos' }}
                            </button>
                        </div>

                        <article
                            v-for="(ej, idx) in ejerciciosDelDia"
                            :key="ej.nombre"
                            class="bg-[#111726]/90 border border-[#232F4D] rounded-2xl p-3.5 shadow-sm"
                        >
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div
                                        :class="[
                                            'w-8 h-8 rounded-xl flex items-center justify-center font-bold text-xs',
                                            ej.estaCompleto
                                                ? 'bg-[#10B981]/15 border border-[#10B981]/40 text-emerald-400'
                                                : ej.tieneAlgunaCompletada
                                                  ? 'bg-[#6366F1]/15 border border-[#6366F6]/40 text-[#A78BFA]'
                                                  : 'bg-[#161F36] border border-[#232F4D] text-gray-300',
                                        ]"
                                    >
                                        {{ idx + 1 }}
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-bold text-white">
                                            {{ ej.nombre }}
                                        </h4>
                                        <p class="text-[11px] text-gray-300">
                                            {{ ej.meta }}
                                        </p>
                                    </div>
                                </div>
                                <span
                                    class="text-xs font-bold px-2 py-0.5 rounded-md"
                                    :class="
                                        ej.estaCompleto
                                            ? 'bg-emerald-500/15 border border-emerald-500/30 text-emerald-400'
                                            : 'bg-[#161F36] border border-[#232F4D] text-[#A78BFA]'
                                    "
                                >
                                    {{ ej.completadas }}/{{ ej.sets.length }} series
                                </span>
                            </div>
                            <!-- Series pills -->
                            <div
                                v-if="expandirTodos || ej.estaCompleto"
                                class="grid grid-cols-4 gap-1.5 pt-2"
                            >
                                <div
                                    v-for="(set, sIdx) in ej.sets"
                                    :key="sIdx"
                                    :class="[
                                        'rounded-lg py-1 text-center border',
                                        set.completado
                                            ? 'bg-emerald-500/10 border-emerald-500/30'
                                            : 'bg-[#161F36] border-[#232F4D]',
                                    ]"
                                >
                                    <span
                                        class="text-[10px] block font-mono"
                                        :class="set.completado ? 'text-emerald-400' : 'text-gray-400'"
                                        >S{{ sIdx + 1 }}</span
                                    >
                                    <span
                                        class="text-xs font-bold"
                                        :class="set.completado ? 'text-emerald-200' : 'text-gray-200'"
                                    >
                                        {{ formatSetLabel(set) }}
                                    </span>
                                </div>
                            </div>
                        </article>
                    </section>
                    <!-- END: ExerciseListSection -->

                    <!-- BEGIN: ProgressChartSection -->
                    <section
                        v-if="chartPoints.length > 0"
                        class="bg-[#111726]/80 border border-[#232F4D] rounded-3xl p-4 space-y-3"
                    >
                        <div class="flex items-start justify-between">
                            <div>
                                <div class="flex items-center gap-1.5">
                                    <span class="text-base">📈</span>
                                    <h3 class="text-sm font-bold text-white">
                                        Peso máximo por día (30d)
                                    </h3>
                                </div>
                                <p class="text-xs text-gray-400">
                                    Progreso registrado en Press de banca
                                </p>
                            </div>
                            <span
                                class="text-xs font-bold text-[#A5B4FC] bg-[#1E1B4B]/60 border border-[#6366F1]/30 px-2.5 py-1 rounded-lg"
                            >
                                Max: {{ maxPesoChart }} kg
                            </span>
                        </div>
                        <div class="pt-2">
                            <div class="w-full h-36 relative">
                                <svg
                                    class="w-full h-full overflow-visible"
                                    preserveaspectratio="none"
                                    viewbox="0 0 320 120"
                                >
                                    <defs>
                                        <linearGradient
                                            id="chartGradient"
                                            x1="0%"
                                            x2="0%"
                                            y1="0%"
                                            y2="100%"
                                        >
                                            <stop
                                                offset="0%"
                                                stop-color="#8B5CF6"
                                                stop-opacity="0.45"
                                            ></stop>
                                            <stop
                                                offset="100%"
                                                stop-color="#6366F1"
                                                stop-opacity="0.0"
                                            ></stop>
                                        </linearGradient>
                                    </defs>
                                    <line
                                        stroke="#2A354F"
                                        stroke-dasharray="2 3"
                                        stroke-width="0.75"
                                        x1="0"
                                        x2="320"
                                        y1="20"
                                        y2="20"
                                    ></line>
                                    <line
                                        stroke="#2A354F"
                                        stroke-dasharray="2 3"
                                        stroke-width="0.75"
                                        x1="0"
                                        x2="320"
                                        y1="60"
                                        y2="60"
                                    ></line>
                                    <line
                                        stroke="#2A354F"
                                        stroke-dasharray="2 3"
                                        stroke-width="0.75"
                                        x1="0"
                                        x2="320"
                                        y1="100"
                                        y2="100"
                                    ></line>
                                    <path :d="chartAreaPath" fill="url(#chartGradient)"></path>
                                    <path
                                        :d="chartLinePath"
                                        fill="none"
                                        stroke="#8B5CF6"
                                        stroke-linecap="round"
                                        stroke-width="3"
                                    ></path>
                                    <circle
                                        v-for="(pt, i) in chartPoints"
                                        :key="i"
                                        :cx="pt.x"
                                        :cy="pt.y"
                                        :fill="pt.highlight ? '#A78BFA' : '#6366F1'"
                                        :r="pt.highlight ? 5 : 4"
                                        stroke="#ffffff"
                                        :stroke-width="pt.highlight ? 2.5 : 2"
                                    ></circle>
                                </svg>
                            </div>
                            <div
                                class="flex justify-between text-[10px] text-gray-500 font-mono pt-2 border-t border-[#232F4D]"
                            >
                                <span v-for="(l, i) in chartLabels" :key="i">{{ l }}</span>
                            </div>
                        </div>
                    </section>
                    <!-- END: ProgressChartSection -->

                    <!-- Footer CTA -->
                    <div class="pt-1 pb-4">
                        <button
                            v-if="!esUltimoDia"
                            type="button"
                            @click="siguienteDia"
                            class="w-full py-3 px-4 rounded-2xl bg-[#111726] border border-[#232F4D] text-gray-300 hover:text-white hover:border-[#2a354f] font-semibold text-xs flex items-center justify-center gap-2 transition-all"
                        >
                            <span>
                                Avanzar al siguiente día:
                                <strong>{{ diasSiguienteLabel }}</strong>
                            </span>
                            <svg
                                class="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                viewbox="0 0 24 24"
                            >
                                <path
                                    d="M9 5l7 7-7 7"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                ></path>
                            </svg>
                        </button>
                        <button
                            v-else
                            type="button"
                            @click="finalizarRutina"
                            class="w-full py-3 px-4 rounded-2xl bg-gradient-to-r from-emerald-500 to-teal-500 text-white font-bold text-xs flex items-center justify-center gap-2 shadow-lg shadow-emerald-500/30 active:scale-[0.98] transition-all"
                        >
                            🎉 Finalizar rutina
                        </button>
                    </div>
                </main>
                <!-- END: MainContent -->

                <!-- BEGIN: Bottom Fixed Save Button -->
                <div
                    class="fixed inset-x-0 bottom-0 z-40 border-t border-[#232F4D] bg-[#090D16]/95 backdrop-blur pb-[env(safe-area-inset-bottom)]"
                >
                    <div class="max-w-md mx-auto px-4 py-3">
                        <button
                            @click="guardarSesion"
                            class="w-full rounded-xl bg-[#6366F1] hover:bg-[#4F46E5] text-white px-4 py-3 text-sm font-bold shadow-lg shadow-[#6366F1]/30 active:scale-[0.99] transition-all"
                        >
                            Guardar sesión
                        </button>
                    </div>
                </div>
                <!-- END: Bottom Fixed Save Button -->
            </div>

            <!-- ============================================================ -->
            <!-- DESKTOP: layout actual (preservado) -->
            <!-- ============================================================ -->
            <div class="hidden md:block">
                <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                    <Breadcrumbs :items="[{ label: 'Inicio' }]" class="mb-3" />

                    <div data-tour="home-hero" class="mb-4">
                        <HomeHero />
                    </div>

                    <!-- Banner de Sesión Activa -->
                    <div
                        v-if="session.isActive"
                        class="mb-4 relative overflow-hidden rounded-2xl border border-emerald-500/40 bg-gradient-to-r from-emerald-900/95 via-teal-900/95 to-violet-900/95 p-4 text-white shadow-xl flex flex-wrap items-center justify-between gap-3 animate-fade-in"
                    >
                        <div
                            class="pointer-events-none absolute -right-10 -top-10 w-40 h-40 rounded-full bg-emerald-500/30 blur-3xl"
                        ></div>
                        <div class="flex items-center gap-3">
                            <span class="relative flex h-3.5 w-3.5">
                                <span
                                    class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"
                                ></span>
                                <span
                                    class="relative inline-flex rounded-full h-3.5 w-3.5 bg-emerald-500"
                                ></span>
                            </span>
                            <div>
                                <p
                                    class="text-sm font-black tracking-tight flex items-center gap-2"
                                >
                                    <span>Entrenamiento en curso · {{ formattedActiveTime }}</span>
                                    <span
                                        v-if="session.isPaused"
                                        class="text-[10px] font-bold bg-amber-500/30 text-amber-300 px-1.5 py-0.5 rounded"
                                        >Pausado</span
                                    >
                                </p>
                                <p class="text-xs text-emerald-200/80">
                                    {{
                                        session.currentEjercicio?.nombre ||
                                        'Sesión iniciada'
                                    }}
                                    · {{ session.totalSeriesCompletadas }}/{{
                                        session.totalSeriesObjetivo
                                    }}
                                    series
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                @click="abrirModoEntrenamiento"
                                class="px-4 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-white text-xs font-bold shadow-md cursor-pointer transition-all active:scale-95"
                            >
                                ⚡ Continuar
                            </button>
                            <button
                                type="button"
                                @click="descartarSesion"
                                class="px-3 py-2 rounded-xl bg-gray-800/80 hover:bg-gray-700 text-gray-400 hover:text-rose-300 text-xs font-semibold cursor-pointer transition-all"
                            >
                                Descartar
                            </button>
                        </div>
                    </div>

                    <div
                        v-else
                        class="mb-4 obs-card-elevated p-4 md:p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3"
                    >
                        <div>
                            <h3
                                class="text-sm sm:text-base font-bold text-gray-900 dark:text-white flex items-center gap-2"
                            >
                                <span>⚡ Modo Entrenamiento Activo</span>
                                <span
                                    class="text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300"
                                    >Focus Mode</span
                                >
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                Seguimiento guiado serie por serie, botones táctiles aumentados y
                                descanso automático.
                            </p>
                        </div>
                        <button
                            type="button"
                            @click="abrirModoEntrenamiento"
                            class="shrink-0 px-4 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-emerald-600 hover:from-indigo-500 hover:to-emerald-500 text-white text-xs sm:text-sm font-bold shadow-lg shadow-indigo-600/20 transition-all active:scale-95 cursor-pointer flex items-center gap-2"
                        >
                            <span>▶ Iniciar Sesión de Hoy</span>
                        </button>
                    </div>

                    <div data-tour="rutina-header">
                        <DashboardRutinaHeader
                            :nivel="rutinaStore.seleccionada.nivel"
                            :dias="rutinaStore.seleccionada.dias"
                            :dia-actual="diaActual"
                            @cambiar="cambiarRutina"
                        />
                    </div>

                    <div data-tour="stats">
                        <DashboardStats
                            :series-totales="seriesTotales"
                            :series-completadas="seriesCompletadas"
                            :series-pendientes="seriesPendientes"
                            :peso-registrado="pesoRegistrado"
                            :peso-promedio="pesoPromedio"
                            :reps-registradas="repsRegistradas"
                            :progreso-dia="progresoDia"
                        />
                    </div>

                    <div
                        class="mb-5 md:mb-6 sticky top-14 z-20 -mx-4 px-4 py-2 bg-gray-50/90 dark:bg-[var(--color-obsidian-base)]/90 backdrop-blur supports-[backdrop-filter]:bg-gray-50/70 supports-[backdrop-filter]:dark:bg-[var(--color-obsidian-base)]/70 md:static md:mx-0 md:px-0 md:py-0 md:bg-transparent md:backdrop-blur-none"
                        data-tour="day-selector"
                    >
                        <div
                            class="flex flex-nowrap gap-2 overflow-x-auto scrollbar-hide -mx-4 px-4 md:mx-0 md:px-0 md:flex-wrap md:overflow-visible"
                        >
                            <button
                                v-for="dia in todosLosDias"
                                :key="dia"
                                @click="cambiarDia(dia)"
                                :class="[
                                    'shrink-0 px-4 py-2 rounded-full font-semibold transition-all whitespace-nowrap text-sm',
                                    diaActual === dia
                                        ? 'bg-[var(--color-violet-primary)] text-white shadow-[0_4px_14px_var(--color-violet-glow)]'
                                        : 'bg-white dark:bg-[var(--color-obsidian-elevated)] text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[var(--color-obsidian-surface)] border border-gray-200 dark:border-[var(--color-obsidian-border)]',
                                ]"
                            >
                                {{ dia }}
                            </button>
                        </div>
                    </div>

                    <div data-tour="series-list">
                        <div v-if="isInitialLoading" class="space-y-3 mb-6">
                            <SkeletonLoader variant="card" />
                            <SkeletonLoader variant="card" />
                            <SkeletonLoader variant="card" />
                        </div>
                        <DashboardSeriesList
                            v-else
                            :filas-serie="filasSerie"
                            :dia-index="diaIndex"
                            :texto-boton-siguiente="textoBotonSiguiente"
                            :boton-siguiente-class="botonSiguienteClass"
                            @guardar="guardarFila"
                            @dia-anterior="diaAnterior"
                            @guardar-sesion="guardarSesion"
                            @siguiente-dia="siguienteDia"
                        />
                    </div>
                </div>

                <DashboardHeatmap :historial="historialRutina" class="mt-6 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8" data-tour="heatmap" />
                <DashboardWeeklyChart
                    :historial="historialRutina"
                    class="mt-6 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8"
                    data-tour="weekly-chart"
                />
            </div>
        </template>

        <EmptyStateIllustrated
            v-else
            variant="no-rutinas"
            title="No hay rutina seleccionada"
            description="Elegí una rutina para empezar a registrar tus series y llevar el control de tu progreso."
            cta-text="Seleccionar Rutina"
            cta-icon="M12 6v6m0 0v6m0-6h6m-6 0H6"
            @cta="window.location.href = '/rutinas'"
        />

        <!-- Onboarding tour -->
        <OnboardingTour :tour="onboarding" />

        <!-- Modales de Modo Entrenamiento Activo -->
        <ActiveWorkoutModal
            :open="showActiveWorkoutModal"
            @minimize="showActiveWorkoutModal = false"
            @finish="onFinishActiveWorkout"
        />

        <WorkoutSummaryModal
            :open="showWorkoutSummaryModal"
            @cancel="
                showWorkoutSummaryModal = false;
                showActiveWorkoutModal = true;
            "
            @saved="onWorkoutSummarySaved"
        />

        <!-- Bottom sheet: resumen al cambiar de día -->
        <Teleport to="body">
            <Transition name="sheet-fade">
                <div
                    v-if="showDaySummary"
                    class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/60 backdrop-blur-sm"
                    @click.self="showDaySummary = false"
                >
                    <div
                        class="sheet-content w-full sm:max-w-md bg-[#161F36] sm:rounded-3xl rounded-t-3xl rounded-b-none sm:rounded-b-3xl shadow-2xl border border-[#232F4D] overflow-hidden max-h-[90vh] flex flex-col"
                    >
                        <div class="sm:hidden pt-2 pb-1 flex justify-center">
                            <div class="w-10 h-1.5 rounded-full bg-[#2a354f]"></div>
                        </div>

                        <div
                            class="bg-gradient-to-br from-emerald-500 via-emerald-600 to-teal-600 px-6 py-5 text-white"
                        >
                            <div
                                class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider opacity-90"
                            >
                                <span>✓</span>
                                <span>Día completado</span>
                            </div>
                            <h3 class="mt-1 text-2xl font-black">
                                {{ daySummary.diaLabel }}
                            </h3>
                        </div>

                        <div class="px-6 py-5 space-y-4 overflow-y-auto">
                            <div class="grid grid-cols-3 gap-3 text-center">
                                <div class="rounded-xl bg-[#6366F1]/15 px-3 py-3">
                                    <p class="text-[10px] font-bold uppercase tracking-wider text-[#A78BFA]">
                                        Series
                                    </p>
                                    <p class="mt-0.5 text-2xl font-black text-[#A5B4FC] tabular-nums">
                                        {{ daySummary.completadas }}/{{ daySummary.total }}
                                    </p>
                                </div>
                                <div class="rounded-xl bg-[#F59E0B]/15 px-3 py-3">
                                    <p class="text-[10px] font-bold uppercase tracking-wider text-[#F59E0B]">
                                        Volumen
                                    </p>
                                    <p class="mt-0.5 text-2xl font-black text-[#FBBF24] tabular-nums">
                                        {{ formatVolumen(daySummary.volumen) }}
                                    </p>
                                    <p class="text-[10px] text-[#F59E0B]/80">kg totales</p>
                                </div>
                                <div class="rounded-xl bg-[#10B981]/15 px-3 py-3">
                                    <p class="text-[10px] font-bold uppercase tracking-wider text-emerald-400">
                                        PRs
                                    </p>
                                    <p class="mt-0.5 text-2xl font-black text-emerald-400 tabular-nums">
                                        {{ daySummary.prs }}
                                    </p>
                                    <p class="text-[10px] text-emerald-400/80">récords</p>
                                </div>
                            </div>

                            <div
                                v-if="daySummary.mejorSet"
                                class="rounded-xl bg-gradient-to-r from-emerald-900/40 to-teal-900/40 border border-emerald-700/40 px-4 py-3"
                            >
                                <div
                                    class="flex items-center gap-2 text-xs font-bold text-emerald-300 uppercase tracking-wider"
                                >
                                    <span>🏆</span>
                                    <span>Mejor set</span>
                                </div>
                                <p class="mt-1 text-base font-bold text-white">
                                    {{ daySummary.mejorSet.ejercicio }}
                                </p>
                                <p class="text-sm font-semibold text-emerald-300 tabular-nums">
                                    {{ daySummary.mejorSet.peso }} kg ×
                                    {{ daySummary.mejorSet.reps }} reps
                                </p>
                            </div>
                        </div>

                        <div
                            class="px-6 py-4 bg-[#090D16]/60 border-t border-[#232F4D] flex gap-3 pb-[max(env(safe-area-inset-bottom),1rem)]"
                        >
                            <button
                                type="button"
                                @click="showDaySummary = false"
                                class="flex-1 px-4 py-2.5 rounded-xl border border-[#232F4D] bg-[#111726] text-sm font-semibold text-gray-300 hover:bg-[#161F36] transition-colors"
                            >
                                Quedarme acá
                            </button>
                            <button
                                v-if="daySummary.tieneSiguiente"
                                type="button"
                                @click="avanzarAlSiguienteDia"
                                class="flex-1 px-4 py-2.5 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 text-white text-sm font-bold shadow-md transition-all"
                            >
                                Ir al {{ daySummary.siguienteLabel }} →
                            </button>
                            <button
                                v-else
                                type="button"
                                @click="finalizarRutinaDesdeResumen"
                                class="flex-1 px-4 py-2.5 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 text-white text-sm font-bold shadow-md transition-all"
                            >
                                🎉 Finalizar rutina
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { useRutinaStore } from '../stores/rutina';
import axios from 'axios';
import { useToast } from '../composables/useToast';
import { usePullToRefresh } from '../composables/usePullToRefresh';
import { useOnboarding } from '../composables/useOnboarding';
import { useOfflineSeries } from '@/composables/useOfflineSeries';
import { useWakeLock } from '@/composables/useWakeLock';
import { useTrainingSessionStore } from '@/stores/trainingSession';
import SyncBadge from './training/SyncBadge.vue';
import EmptyState from './EmptyState.vue'; // legacy, reemplazado por EmptyStateIllustrated gradualmente
import EmptyStateIllustrated from './EmptyStateIllustrated.vue';
import OnboardingTour from './OnboardingTour.vue';
import confetti from 'canvas-confetti';
import Breadcrumbs from './Breadcrumbs.vue';
import HomeHero from './HomeHero.vue';
import DashboardRutinaHeader from './dashboard/DashboardRutinaHeader.vue';
import DashboardStats from './dashboard/DashboardStats.vue';
import DashboardSeriesList from './dashboard/DashboardSeriesList.vue';
import DashboardHeatmap from './dashboard/DashboardHeatmap.vue';
import DashboardWeeklyChart from './dashboard/DashboardWeeklyChart.vue';
import SkeletonLoader from './common/SkeletonLoader.vue';
import { useRestTimerStore } from '../stores/restTimer';
import ActiveWorkoutModal from './training/ActiveWorkoutModal.vue';
import WorkoutSummaryModal from './training/WorkoutSummaryModal.vue';

const rutinaStore = useRutinaStore();

// === Modo entrenamiento (Oleada 1) ===
const offline = useOfflineSeries();
const wake = useWakeLock();
const session = useTrainingSessionStore();

onMounted(() => {
    if (session.isActive && wake.supported) {
        wake.requestWakeLock();
    }
});

// Onboarding tour: 5 steps por el dashboard, se muestra la primera vez
const onboarding = useOnboarding('dashboard-tour', [
    {
        selector: '[data-tour="rutina-header"]',
        title: 'Tu rutina actual',
        body: 'Acá ves el nombre, nivel y días de tu rutina activa. Tocá el nombre para cambiarla.',
        position: 'bottom',
    },
    {
        selector: '[data-tour="stats"]',
        title: 'Stats del día',
        body: 'Tu progreso en tiempo real: series completadas vs pendientes, peso levantado, reps.',
        position: 'bottom',
    },
    {
        selector: '[data-tour="day-selector"]',
        title: 'Selector de día',
        body: 'Cambiá entre los días de tu rutina. Cada día tiene ejercicios diferentes.',
        position: 'bottom',
    },
    {
        selector: '[data-tour="series-list"]',
        title: 'Tus series',
        body: 'Registrá cada serie acá. Marcá como completada cuando termines, y guardá al final.',
        position: 'top',
    },
    {
        selector: '[data-tour="heatmap"]',
        title: 'Tu constancia',
        body: 'Acá ves todos los días que entrenaste. ¡La constancia es la clave!',
        position: 'top',
    },
]);
const toast = useToast();
const showSuccess = (m) => toast.success(m);
const showError = (m) => toast.error(m);
const showWarning = (m) => toast.warning(m);

const filasSerie = ref([]);
const historialRutina = ref([]);
const diaActual = ref('Día 1');
const cicloInicio = ref(null);
const todosLosDias = ref([]);

const showActiveWorkoutModal = ref(false);
const showWorkoutSummaryModal = ref(false);
const isInitialLoading = ref(true);

// =====================================================================
// === MOBILE-FIRST HELPERS (Kinetic Obsidian) ===
// =====================================================================

// === Topbar / Hero ===
const userFirstName = computed(() => {
    const n = (window.__user?.name || '').trim();
    if (!n) return 'crack';
    return n.split(' ')[0];
});

const diaSemanaEs = computed(() => {
    if (dashboardToday.value?.hoy?.dia_semana_es) {
        // viene como "Domingo" — perfecto
        return dashboardToday.value.hoy.dia_semana_es;
    }
    // fallback: cálculo local
    const dias = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
    return dias[new Date().getDay()];
});

const fechaCorta = computed(() => {
    const d = dashboardToday.value?.hoy?.fecha
        ? new Date(dashboardToday.value.hoy.fecha + 'T00:00:00')
        : new Date();
    const meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    return `${d.getDate()} ${meses[d.getMonth()]}`;
});

const nombreRutina = computed(() => {
    const r = dashboardToday.value?.rutina?.nombre;
    return r || rutinaStore.seleccionada?.nivel || 'Personalizada';
});

// === Stats del hero ===
const stats = ref({
    streak: 0,
    longestStreak: 0,
    totalSets30d: 0,
    trend30d: 0,
});

const dashboardToday = ref(null);

const cargarDashboardToday = async () => {
    try {
        const r = await axios.get('/api/dashboard/today');
        dashboardToday.value = r.data;
        stats.value.streak = r.data?.stats?.streak ?? 0;
        stats.value.totalSets30d = r.data?.stats?.total_sets_30d ?? 0;
    } catch (e) {
        console.warn('dashboard/today falló, fallback a /api/stats/resumen', e);
    }
    try {
        const r2 = await axios.get('/api/stats/resumen');
        stats.value.longestStreak = r2.data?.longest_streak ?? 0;
        stats.value.streak = stats.value.streak || r2.data?.current_streak || 0;
    } catch (e) {
        console.warn('stats/resumen no se pudo cargar', e);
    }
};

// Trend 30d: comparar sets 30d actuales vs. los 30 días anteriores
const cargarTrend30d = async () => {
    // Lo calculamos en cliente desde historialRutina si está disponible
    const h = historialRutina.value || [];
    if (!h.length) return;
    const hoy = new Date();
    const hace30 = new Date(hoy.getTime() - 30 * 86400000);
    const hace60 = new Date(hoy.getTime() - 60 * 86400000);
    const en30 = h.filter((r) => {
        const f = new Date(r.fecha);
        return f >= hace30 && f <= hoy && r.completado;
    }).length;
    const en30Prev = h.filter((r) => {
        const f = new Date(r.fecha);
        return f >= hace60 && f < hace30 && r.completado;
    }).length;
    if (en30Prev === 0) {
        stats.value.trend30d = en30 > 0 ? 100 : 0;
    } else {
        stats.value.trend30d = Math.round(((en30 - en30Prev) / en30Prev) * 100);
    }
};

// === "Último entreno" labels ===
const ultimoEntrenoLabel = computed(() => {
    const d = dashboardToday.value?.stats?.days_since_last_workout;
    if (d === null || d === undefined) return '—';
    if (d === 0) return 'Hoy';
    if (d === 1) return 'Ayer';
    if (d >= 2 && d < 7) return `Hace ${d}d`;
    if (d >= 7 && d < 30) return `Hace ${Math.floor(d / 7)}sem`;
    return `Hace ${Math.floor(d / 30)}m`;
});

const ultimoEntrenoSub = computed(() => {
    const d = dashboardToday.value?.stats?.days_since_last_workout;
    if (d === null || d === undefined) return 'Sin registros aún';
    if (d === 0) return 'Entrenaste hoy 💪';
    if (d === 1) return 'Día de descanso OK';
    if (d >= 2 && d <= 3) return 'Volvé a la carga';
    if (d >= 4 && d <= 6) return 'Pasaron varios días';
    return 'Hacés rato que no entrás';
});

// === Día / grupo muscular ===
// Mapea "Día 1" → "Torso" según los grupos musculares únicos del día
const grupoDelDiaActual = computed(() => {
    // Agrupa filasSerie por grupo muscular del día actual
    const filasDelDia = filasSerie.value.filter((f) => f.dia === diaActual.value);
    if (!filasDelDia.length) return '';
    // Sin un campo "grupo_muscular" directo en la tabla, usamos heurística:
    // los nombres de ejercicio suelen tener el músculo al inicio (ej. "Press banca")
    // y los días se nombran por grupo. Si el día es "Día 1" genérico,
    // sacamos el grupo más frecuente como aproximación.
    // Por simplicidad: devolver lo que diga el nombre del día si incluye paréntesis,
    // si no, devolver string vacío.
    return '';
});

// Grupo "Torso"/"Pierna"/etc para mostrar en el h2 del hero.
// Lo derivamos del campo `notas` del ejercicio o del nombre del día
// (muchas rutinas lo ponen en `rutina.dia_actual` con formato "Día 1: Torso").
const diaActualGrupo = computed(() => {
    // 1) Si el nombre del día actual ya trae un grupo entre paréntesis/":" lo usamos
    const m = String(diaActual.value).match(/(?:\(|:)\s*([^)\:]+)\s*\)?/);
    if (m) return m[1];
    // 2) Mapeo por índice de día (heurística común: 1=Torso, 2=Pierna, 3=Full Body)
    const i = diaIndex.value;
    const fallback = ['', 'Torso', 'Pierna', 'Full Body', 'Torso', 'Pierna'];
    return fallback[i] || '';
});

// Misma lógica para el nombre de un día cualquiera (usado en el pill "Día 1 (Torso)")
const diaGrupoPara = (dia) => {
    const m = String(dia).match(/(?:\(|:)\s*([^)\:]+)\s*\)?/);
    if (m) return m[1];
    const i = todosLosDias.value.indexOf(dia);
    const fallback = ['', 'Torso', 'Pierna', 'Full Body', 'Torso', 'Pierna'];
    return fallback[i] || '';
};

// === Ejercicios del día (agrupados por nombre) ===
const ejerciciosDelDia = computed(() => {
    const filasDelDia = filasSerie.value.filter((f) => f.dia === diaActual.value);
    const map = new Map();
    filasDelDia.forEach((f) => {
        if (!map.has(f.ejercicio_nombre)) {
            map.set(f.ejercicio_nombre, []);
        }
        map.get(f.ejercicio_nombre).push(f);
    });
    return Array.from(map.entries()).map(([nombre, sets]) => {
        sets.sort((a, b) => (a.series_numero || 0) - (b.series_numero || 0));
        const completadas = sets.filter((s) => s.completado).length;
        return {
            nombre,
            sets,
            completadas,
            total: sets.length,
            estaCompleto: completadas === sets.length && sets.length > 0,
            tieneAlgunaCompletada: completadas > 0,
            meta: buildMeta(sets[0]),
        };
    });
});

const ejerciciosCompletadosCount = computed(
    () => ejerciciosDelDia.value.filter((e) => e.estaCompleto).length
);

const buildMeta = (fila) => {
    if (!fila) return '';
    const partes = [];
    if (fila.reps_min && fila.reps_max) partes.push(`${fila.reps_min}–${fila.reps_max} reps`);
    if (fila.peso) partes.push(`Último: ${fila.peso} kg`);
    return partes.join(' · ');
};

const formatSetLabel = (set) => {
    const r = set.reps_realizadas ?? set.reps_min ?? '';
    const p = set.peso ?? '';
    if (r && p) return `${r} × ${p}kg`;
    if (p) return `${p}kg`;
    if (r) return `${r} reps`;
    return '—';
};

// === Duración estimada ===
const duracionEstimada = computed(() => {
    const totalSets = filasSerie.value.filter((f) => f.dia === diaActual.value).length;
    if (!totalSets) return 0;
    // ~45s por serie + descanso promedio (1.5min) entre series
    const mins = Math.round((totalSets * (45 + 90)) / 60);
    return Math.max(mins, 10);
});

// === Día siguiente / último ===
const diasSiguienteLabel = computed(() => {
    const i = diaIndex.value;
    if (i < 0 || i >= todosLosDias.value.length - 1) return '';
    return todosLosDias.value[i + 1];
});

// === Expandir / colapsar todos los sets ===
const expandirTodos = ref(true);

// === Chart "Peso máximo por día (30d)" ===
const maxPesoChart = computed(() => {
    if (!chartPoints.value.length) return 0;
    return Math.max(...chartPoints.value.map((p) => p.value || 0));
});

// Construimos puntos a partir del historialRutina: por día, max peso en
// ejercicio "Press de banca" (o primer ejercicio con peso, como fallback).
const chartSeries = computed(() => {
    const h = historialRutina.value || [];
    if (!h.length) return { points: [], labels: [] };
    const hoy = new Date();
    const desde = new Date(hoy.getTime() - 30 * 86400000);
    // 7 puntos distribuidos entre hace30 y hoy
    const N = 7;
    const puntos = [];
    for (let i = 0; i < N; i++) {
        const f = new Date(desde.getTime() + ((hoy.getTime() - desde.getTime()) * i) / (N - 1));
        puntos.push({ fecha: f, peso: 0 });
    }
    // Para cada punto, tomar el peso máximo registrado entre los 5 días previos
    h.forEach((r) => {
        if (!r.completado || !r.peso || Number(r.peso) <= 0) return;
        const fr = new Date(r.fecha);
        if (fr < desde || fr > hoy) return;
        // Buscar el punto más cercano
        let mejorI = 0;
        let mejorDelta = Infinity;
        for (let i = 0; i < N; i++) {
            const delta = Math.abs(puntos[i].fecha - fr);
            if (delta < mejorDelta) {
                mejorDelta = delta;
                mejorI = i;
            }
        }
        puntos[mejorI].peso = Math.max(puntos[mejorI].peso, Number(r.peso));
    });
    // Si todos los puntos quedaron en 0 (sin datos), devolvemos array vacío
    const max = Math.max(...puntos.map((p) => p.peso));
    if (max === 0) return { points: [], labels: [] };
    // Labels: dd/mm
    const labels = puntos.map((p) => {
        const dd = String(p.fecha.getDate()).padStart(2, '0');
        const mm = String(p.fecha.getMonth() + 1).padStart(2, '0');
        return `${dd}/${mm}`;
    });
    return { points: puntos, labels };
});

const chartPoints = computed(() => {
    const { points, labels } = chartSeries.value;
    if (!points.length) return [];
    const W = 320;
    const H = 120;
    const max = Math.max(...points.map((p) => p.peso));
    const min = Math.min(...points.map((p) => p.peso));
    const rango = Math.max(max - min, 1);
    return points.map((p, i) => {
        const x = 15 + ((W - 30) * i) / (points.length - 1);
        // Invertir Y (SVG: 0 arriba)
        const y = 100 - ((p.peso - min) / rango) * 80 + 5;
        return {
            x: Math.round(x),
            y: Math.round(y),
            value: p.peso,
            label: labels[i],
            highlight: p.peso === max && max > 0,
        };
    });
});

const chartLinePath = computed(() => {
    const pts = chartPoints.value;
    if (!pts.length) return '';
    let d = `M ${pts[0].x} ${pts[0].y}`;
    for (let i = 1; i < pts.length; i++) {
        const prev = pts[i - 1];
        const cur = pts[i];
        const cpx = (prev.x + cur.x) / 2;
        d += ` Q ${cpx} ${prev.y} ${cur.x} ${cur.y}`;
    }
    return d;
});

const chartAreaPath = computed(() => {
    const pts = chartPoints.value;
    if (!pts.length) return '';
    let d = `M ${pts[0].x} ${pts[0].y}`;
    for (let i = 1; i < pts.length; i++) {
        const prev = pts[i - 1];
        const cur = pts[i];
        const cpx = (prev.x + cur.x) / 2;
        d += ` Q ${cpx} ${prev.y} ${cur.x} ${cur.y}`;
    }
    d += ` L ${pts[pts.length - 1].x} 115 L ${pts[0].x} 115 Z`;
    return d;
});

const chartLabels = computed(() => {
    const pts = chartPoints.value;
    return pts.map((p) => p.label);
});

// =====================================================================
// === FIN MOBILE-FIRST HELPERS ===
// =====================================================================

const formattedActiveTime = computed(() => {
    const s = session.elapsed;
    const hrs = Math.floor(s / 3600);
    const mins = Math.floor((s % 3600) / 60);
    const secs = s % 60;
    if (hrs > 0) {
        return `${String(hrs).padStart(2, '0')}:${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
    }
    return `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
});

const abrirModoEntrenamiento = async () => {
    if (!session.isActive) {
        const exercisesMap = new Map();
        filasSerie.value.forEach((f) => {
            if (!exercisesMap.has(f.ejercicio_nombre)) {
                exercisesMap.set(f.ejercicio_nombre, {
                    nombre: f.ejercicio_nombre,
                    series_objetivo: 0,
                    reps_min: f.reps_min,
                    reps_max: f.reps_max,
                    descanso_min: f.descanso_min,
                    superserie_grupo: f.superserie_grupo,
                });
            }
            exercisesMap.get(f.ejercicio_nombre).series_objetivo += 1;
        });

        const ejerciciosList = Array.from(exercisesMap.values());

        session.start({
            rutina_nombre: getRutinaNombre(),
            dia: diaActual.value,
            ejercicios: ejerciciosList,
        });

        try {
            await axios.post('/api/sesiones/iniciar', {
                uuid: session.session.id,
                rutina_nombre: getRutinaNombre(),
                dia: diaActual.value,
                started_at: session.session.startedAt,
                series_totales: filasSerie.value.length,
            });
        } catch (e) {
            console.warn('Sesión iniciada offline:', e);
        }
    }
    showActiveWorkoutModal.value = true;
};

const onFinishActiveWorkout = () => {
    showActiveWorkoutModal.value = false;
    showWorkoutSummaryModal.value = true;
};

const onWorkoutSummarySaved = async (resumen) => {
    showWorkoutSummaryModal.value = false;
    showSuccess('🎉 ¡Sesión guardada exitosamente!');
    await fetchHistorialRutina();
    await fetchRutinasDelDia();
    await cargarDashboardToday();
};

const descartarSesion = async () => {
    const ok = await toast.confirm(
        '¿Seguro que querés descartar la sesión actual? Se perderá el tiempo y progreso activo de esta sesión.',
        { confirmLabel: 'Sí, descartar', cancelLabel: 'Continuar' }
    );
    if (!ok) return;

    const uuid = session.session.id;
    session.discard();
    try {
        if (uuid) {
            await axios.delete(`/api/sesiones/${uuid}`);
        }
    } catch (e) {
        // Silencioso si no hay red
    }
    showWarning('Sesión descartada.');
};

const diaIndex = computed(() => todosLosDias.value.indexOf(diaActual.value));
const esUltimoDia = computed(() => diaIndex.value === todosLosDias.value.length - 1);

const seriesTotales = computed(() => filasSerie.value.length);
const seriesCompletadas = computed(() => filasSerie.value.filter((f) => f.completado).length);
const seriesPendientes = computed(() => Math.max(seriesTotales.value - seriesCompletadas.value, 0));

const pesoRegistrado = computed(() =>
    filasSerie.value
        .reduce((t, f) => {
            const p = Number(f.peso);
            return Number.isFinite(p) ? t + p : t;
        }, 0)
        .toFixed(1)
);

const pesoPromedio = computed(() => {
    const pesos = filasSerie.value
        .map((f) => Number(f.peso))
        .filter((p) => Number.isFinite(p) && p > 0);
    if (!pesos.length) return '0.0';
    return (pesos.reduce((t, p) => t + p, 0) / pesos.length).toFixed(1);
});

const repsRegistradas = computed(() =>
    filasSerie.value.reduce((t, f) => {
        const r = Number(f.reps_realizadas);
        return Number.isFinite(r) ? t + r : t;
    }, 0)
);

const progresoDia = computed(() =>
    seriesTotales.value ? Math.round((seriesCompletadas.value / seriesTotales.value) * 100) : 0
);

const textoBotonSiguiente = computed(() => {
    if (esUltimoDia.value) {
        return seriesCompletadas.value === seriesTotales.value
            ? '🎉 Finalizar Rutina'
            : '⚠️ Terminar e Iniciar';
    }
    return seriesPendientes.value > 0 ? '⚠️ Siguiente Día →' : 'Siguiente Día →';
});

const botonSiguienteClass = computed(() => {
    if (esUltimoDia.value && seriesPendientes.value > 0) return 'bg-orange-500 hover:bg-orange-600';
    if (!esUltimoDia.value && seriesPendientes.value > 0)
        return 'bg-yellow-500 hover:bg-yellow-600';
    return 'bg-green-600 hover:bg-green-700';
});

const getRutinaNombre = () => rutinaStore.seleccionada?.nivel || '';

const triggerConfetti = () => {
    const duration = 3000;
    const end = Date.now() + duration;
    const colors = ['#10b981', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6'];
    (function frame() {
        confetti({ particleCount: 5, angle: 60, spread: 55, origin: { x: 0 }, colors });
        confetti({ particleCount: 5, angle: 120, spread: 55, origin: { x: 1 }, colors });
        if (Date.now() < end) requestAnimationFrame(frame);
    })();
};

const fetchUserRutina = async () => {
    try {
        const response = await axios.get('/api/user-rutina');
        if (response.data) {
            const nivelCompleto = `${response.data.nivel} ${response.data.modalidad}`;
            rutinaStore.seleccionar(nivelCompleto, 'Todos los días');
            diaActual.value = response.data.dia_actual || 'Día 1';
            cicloInicio.value = response.data.ciclo_inicio || null;
        } else {
            rutinaStore.limpiar();
        }
    } catch (error) {
        console.error('Error:', error);
    }
};

const fetchHistorialRutina = async () => {
    if (!rutinaStore.seleccionada) {
        historialRutina.value = [];
        return;
    }
    try {
        const response = await axios.get('/api/historial', {
            params: { rutina_nombre: getRutinaNombre() },
        });
        historialRutina.value = Array.isArray(response.data) ? response.data : [];
    } catch (error) {
        console.error('Error:', error);
        historialRutina.value = [];
    }
};

const construirFilasSerie = (rutinasDelDia) => {
    const historialCicloActual = cicloInicio.value
        ? historialRutina.value.filter((r) => (r.fecha || '').slice(0, 10) >= cicloInicio.value)
        : historialRutina.value;

    const registrosAnteriores = cicloInicio.value
        ? new Map(
              historialRutina.value
                  .filter((r) => r.dia === diaActual.value)
                  .filter((r) => (r.fecha || '').slice(0, 10) < cicloInicio.value)
                  .sort((a, b) => {
                      const fa = (a.fecha || '').slice(0, 10);
                      const fb = (b.fecha || '').slice(0, 10);
                      if (fa !== fb) return fb.localeCompare(fa);
                      return (b.id || 0) - (a.id || 0);
                  })
                  .reduce((acc, r) => {
                      const key = `${r.ejercicio_nombre}-${r.series_numero}`;
                      if (!acc.has(key)) acc.set(key, r);
                      return acc;
                  }, new Map())
          )
        : new Map();

    const registros = new Map(
        historialCicloActual
            .filter((r) => r.dia === diaActual.value)
            .map((r) => [`${r.ejercicio_nombre}-${r.series_numero}`, r])
    );

    const filteredRutinas = rutinasDelDia.filter((r) => r.dia === diaActual.value);
    const blocks = [];
    const processedSuperseries = new Set();

    filteredRutinas.forEach((rutina) => {
        if (rutina.superserie_grupo) {
            if (!processedSuperseries.has(rutina.superserie_grupo)) {
                processedSuperseries.add(rutina.superserie_grupo);
                const supersetExercises = filteredRutinas.filter(
                    (r) => r.superserie_grupo === rutina.superserie_grupo
                );
                blocks.push({
                    isSuperset: true,
                    grupo: rutina.superserie_grupo,
                    exercises: supersetExercises,
                });
            }
        } else {
            blocks.push({ isSuperset: false, exercise: rutina });
        }
    });

    const allSets = [];
    blocks.forEach((block) => {
        if (!block.isSuperset) {
            const rutina = block.exercise;
            const totalSeries = Number(rutina.series) || 1;
            for (let index = 0; index < totalSeries; index++) {
                const serieNumero = index + 1;
                const registro = registros.get(`${rutina.ejercicio_nombre}-${serieNumero}`);
                const anterior = registrosAnteriores.get(
                    `${rutina.ejercicio_nombre}-${serieNumero}`
                );
                allSets.push({
                    uid: `${rutina.id}-${diaActual.value}-${serieNumero}`,
                    rutina_nombre: getRutinaNombre(),
                    dia: diaActual.value,
                    ejercicio_nombre: rutina.ejercicio_nombre,
                    series_numero: serieNumero,
                    series_completadas:
                        registro?.series_completadas ?? (registro?.completado ? 1 : 0),
                    reps_min: rutina.reps_min,
                    reps_max: rutina.reps_max,
                    reps_realizadas: registro?.reps_realizadas ?? null,
                    descanso_min: rutina.descanso_min,
                    peso: registro?.peso ?? null,
                    completado: registro?.completado ?? false,
                    superserie_grupo: null,
                    esfuerzo_tipo: registro?.esfuerzo_tipo ?? null,
                    esfuerzo_valor: registro?.esfuerzo_valor ?? null,
                    notas: rutina.notas || null,
                    previous_record: anterior
                        ? {
                              peso: anterior.peso ?? null,
                              reps_realizadas: anterior.reps_realizadas ?? null,
                              esfuerzo_tipo: anterior.esfuerzo_tipo ?? null,
                              esfuerzo_valor: anterior.esfuerzo_valor ?? null,
                          }
                        : null,
                });
            }
        } else {
            const exercises = block.exercises;
            const maxSeries = Math.max(...exercises.map((r) => Number(r.series) || 1));
            for (let index = 0; index < maxSeries; index++) {
                const serieNumero = index + 1;
                exercises.forEach((rutina) => {
                    const totalSeries = Number(rutina.series) || 1;
                    if (serieNumero <= totalSeries) {
                        const registro = registros.get(`${rutina.ejercicio_nombre}-${serieNumero}`);
                        const anterior = registrosAnteriores.get(
                            `${rutina.ejercicio_nombre}-${serieNumero}`
                        );
                        allSets.push({
                            uid: `${rutina.id}-${diaActual.value}-${serieNumero}`,
                            rutina_nombre: getRutinaNombre(),
                            dia: diaActual.value,
                            ejercicio_nombre: rutina.ejercicio_nombre,
                            series_numero: serieNumero,
                            series_completadas:
                                registro?.series_completadas ?? (registro?.completado ? 1 : 0),
                            reps_min: rutina.reps_min,
                            reps_max: rutina.reps_max,
                            reps_realizadas: registro?.reps_realizadas ?? null,
                            descanso_min: rutina.descanso_min,
                            peso: registro?.peso ?? null,
                            completado: registro?.completado ?? false,
                            superserie_grupo: block.grupo,
                            esfuerzo_tipo: registro?.esfuerzo_tipo ?? null,
                            esfuerzo_valor: registro?.esfuerzo_valor ?? null,
                            notas: rutina.notas || null,
                            previous_record: anterior
                                ? {
                                      peso: anterior.peso ?? null,
                                      reps_realizadas: anterior.reps_realizadas ?? null,
                                      esfuerzo_tipo: anterior.esfuerzo_tipo ?? null,
                                      esfuerzo_valor: anterior.esfuerzo_valor ?? null,
                                  }
                                : null,
                        });
                    }
                });
            }
        }
    });

    filasSerie.value = allSets;
};

const fetchRutinasDelDia = async () => {
    if (!rutinaStore.seleccionada) return;
    try {
        const nivel = rutinaStore.seleccionada.nivel.split(' ')[0];
        const modalidad = rutinaStore.seleccionada.nivel.substring(nivel.length + 1);
        const response = await axios.get('/api/rutinas', { params: { nivel, modalidad } });
        const diasUnicos = [...new Set(response.data.map((r) => r.dia))].sort();
        todosLosDias.value = diasUnicos;
        await fetchHistorialRutina();
        construirFilasSerie(response.data);
        cargarTrend30d();
    } catch (error) {
        console.error('Error:', error);
    } finally {
        isInitialLoading.value = false;
    }
};

const guardarFila = async (fila, silencioso = false) => {
    try {
        const result = await offline.recordSet({
            fecha: new Date().toISOString().split('T')[0],
            rutina_nombre: fila.rutina_nombre,
            dia: fila.dia,
            ejercicio_nombre: fila.ejercicio_nombre,
            series_numero: fila.series_numero,
            series_completadas: fila.completado ? 1 : 0,
            reps_min: fila.reps_min,
            reps_max: fila.reps_max,
            reps_realizadas:
                fila.reps_realizadas === '' || fila.reps_realizadas == null
                    ? null
                    : Number(fila.reps_realizadas),
            descanso_min: fila.descanso_min,
            peso: fila.peso === '' || fila.peso == null ? null : Number(fila.peso),
            completado: fila.completado,
            superserie_grupo: fila.superserie_grupo,
            esfuerzo_tipo: fila.esfuerzo_tipo || null,
            esfuerzo_valor: fila.esfuerzo_valor ?? null,
        });
        if (result.status === 'queued' && !silencioso) {
            showError?.(
                'Sin conexion: guardado en este dispositivo, se sincroniza al volver online.'
            );
        } else if (result.status === 'lost' && !silencioso) {
            showError?.(
                'No se pudo guardar: estas sin conexion y tu navegador no soporta guardado offline.'
            );
        }
        if (!silencioso && fila.completado && deberiaIniciarTemporizador(fila)) {
            iniciarTemporizador(fila);
        }

        if (!silencioso && fila.completado && fila.peso && Number(fila.peso) > 0) {
            const pesoActual = Number(fila.peso);
            const maxHistorico = historialRutina.value
                .filter(
                    (r) =>
                        r.ejercicio_nombre === fila.ejercicio_nombre &&
                        r.completado &&
                        r.peso &&
                        Number(r.peso) > 0
                )
                .filter((r) => {
                    if (!cicloInicio.value) return true;
                    return (r.fecha || '').slice(0, 10) < cicloInicio.value;
                })
                .reduce((max, r) => Math.max(max, Number(r.peso) || 0), 0);

            if (pesoActual > maxHistorico) {
                const repsTxt =
                    fila.reps_realizadas != null ? ` × ${fila.reps_realizadas} reps` : '';
                showSuccess?.(
                    `🏆 ¡NUEVO PR en ${fila.ejercicio_nombre}! ${pesoActual} kg${repsTxt}`
                );
                triggerConfetti();
            }
        }
    } catch (error) {
        if (!silencioso) {
            console.error('Error:', error);
            showError('No se pudo guardar la serie. Intenta de nuevo.');
        }
        throw error;
    }
};

const guardarProgreso = async () => {
    try {
        await axios.post('/api/user-rutina/dia', {
            dia_actual: diaActual.value,
        });
    } catch (error) {
        console.error('Error:', error);
    }
};

const cambiarDia = async (dia) => {
    diaActual.value = dia;
    await guardarProgreso();
    await fetchRutinasDelDia();
};

const siguienteDia = async () => {
    if (seriesPendientes.value > 0) {
        const ok = await toast.confirm(
            `Tenés ${seriesPendientes.value} series sin completar. ¿Querés avanzar de todas formas?`,
            { confirmLabel: 'Avanzar', cancelLabel: 'Seguir acá' }
        );
        if (!ok) return;
    }
    prepararResumenDelDia();
    showDaySummary.value = true;
};

const showDaySummary = ref(false);
const daySummary = ref({
    diaLabel: '',
    siguienteLabel: '',
    tieneSiguiente: false,
    completadas: 0,
    total: 0,
    volumen: 0,
    prs: 0,
    mejorSet: null,
});

const formatVolumen = (v) => {
    if (!v) return '0';
    return v >= 1000 ? (v / 1000).toFixed(1) + 'k' : Math.round(v).toString();
};

const prepararResumenDelDia = () => {
    const idxActual = todosLosDias.value.indexOf(diaActual.value);
    const esUltimo = idxActual === todosLosDias.value.length - 1;
    const filasDelDia = filasSerie.value.filter((f) => f.dia === diaActual.value);
    const completadas = filasDelDia.filter((f) => f.completado);
    const volumen = completadas.reduce((t, f) => {
        const p = Number(f.peso) || 0;
        const r = Number(f.reps_realizadas) || 0;
        return t + p * r;
    }, 0);

    let prsCount = 0;
    if (cicloInicio.value) {
        completadas.forEach((f) => {
            if (!f.peso) return;
            const maxAntes = historialRutina.value
                .filter(
                    (r) =>
                        r.ejercicio_nombre === f.ejercicio_nombre &&
                        r.completado &&
                        r.peso &&
                        Number(r.peso) > 0 &&
                        (r.fecha || '').slice(0, 10) < cicloInicio.value
                )
                .reduce((max, r) => Math.max(max, Number(r.peso) || 0), 0);
            if (Number(f.peso) > maxAntes) prsCount++;
        });
    }

    let mejor = null;
    completadas.forEach((f) => {
        const vol = (Number(f.peso) || 0) * (Number(f.reps_realizadas) || 0);
        if (!mejor || vol > mejor.vol) {
            mejor = {
                ejercicio: f.ejercicio_nombre,
                peso: f.peso,
                reps: f.reps_realizadas,
                vol,
            };
        }
    });

    daySummary.value = {
        diaLabel: diaActual.value,
        siguienteLabel: esUltimo ? '' : todosLosDias.value[idxActual + 1],
        tieneSiguiente: !esUltimo,
        completadas: completadas.length,
        total: filasDelDia.length,
        volumen,
        prs: prsCount,
        mejorSet: mejor,
    };
};

const avanzarAlSiguienteDia = async () => {
    showDaySummary.value = false;
    const idxActual = todosLosDias.value.indexOf(diaActual.value);
    if (idxActual < todosLosDias.value.length - 1) {
        diaActual.value = todosLosDias.value[idxActual + 1];
        await guardarProgreso();
        fetchRutinasDelDia();
    }
};

const finalizarRutinaDesdeResumen = async () => {
    showDaySummary.value = false;
    await finalizarRutina();
};

const diaAnterior = async () => {
    if (diaIndex.value > 0) {
        diaActual.value = todosLosDias.value[diaIndex.value - 1];
        await guardarProgreso();
        fetchRutinasDelDia();
    }
};

const cambiarRutina = async () => {
    const ok = await toast.confirm('¿Estás seguro de cambiar de rutina?', {
        confirmLabel: 'Sí, cambiar',
        cancelLabel: 'Cancelar',
    });
    if (!ok) return;
    rutinaStore.limpiar();
    filasSerie.value = [];
    historialRutina.value = [];
    diaActual.value = 'Día 1';
    window.location.href = '/rutinas';
};

const guardarSesion = async () => {
    if (!filasSerie.value.length) {
        showWarning('No hay ejercicios para guardar.');
        return;
    }
    try {
        await Promise.all(filasSerie.value.map((f) => guardarFila(f, true)));
        showSuccess('✓ Sesión guardada correctamente');
    } catch (error) {
        console.error('Error:', error);
        showError('No se pudo guardar la sesión. Intenta de nuevo.');
    }
};

const finalizarRutina = async () => {
    if (!rutinaStore.seleccionada) return;
    try {
        await Promise.all(filasSerie.value.map((f) => guardarFila(f, true)));
        const nivel = rutinaStore.seleccionada.nivel.split(' ')[0];
        const modalidad = rutinaStore.seleccionada.nivel.substring(nivel.length + 1);
        const response = await axios.post('/api/historial/finalizar-rutina', { nivel, modalidad });
        diaActual.value = response.data.dia_actual || 'Día 1';
        await guardarProgreso();
        await fetchRutinasDelDia();
        triggerConfetti();
        showSuccess('🎉 ¡Felicidades! Has completado la rutina. Se reinició al Día 1.');
    } catch (error) {
        console.error('Error:', error);
        showError('No se pudo finalizar la rutina. Intenta de nuevo.');
    }
};

onMounted(async () => {
    rutinaStore.hidratar();
    await fetchUserRutina();
    if (rutinaStore.seleccionada) {
        await fetchHistorialRutina();
        fetchRutinasDelDia();
    }
    await cargarDashboardToday();

    if (rutinaStore.seleccionada && onboarding.shouldShow()) {
        setTimeout(() => onboarding.start(), 600);
    }
});

watch(
    () => rutinaStore.seleccionada,
    (newVal) => {
        if (newVal) {
            fetchHistorialRutina();
            fetchRutinasDelDia();
        }
    }
);

const refreshDashboard = async () => {
    await fetchUserRutina();
    if (rutinaStore.seleccionada) {
        await fetchHistorialRutina();
        await fetchRutinasDelDia();
    }
    await cargarDashboardToday();
};
const { isPulling, isRefreshing, pullOffset } = usePullToRefresh(window, refreshDashboard);

const restTimer = useRestTimerStore();

const deberiaIniciarTemporizador = (fila) => {
    if (!fila.superserie_grupo) return true;
    const setsEnRonda = filasSerie.value.filter(
        (f) =>
            f.superserie_grupo === fila.superserie_grupo && f.series_numero === fila.series_numero
    );
    return setsEnRonda.every((f) => f.completado);
};

const iniciarTemporizador = (fila) => {
    const descansoMinutos = parseFloat(fila.descanso_min) || 1.5;
    const totalSegundos = Math.round(descansoMinutos * 60);
    const nombreLabel = fila.superserie_grupo
        ? `Descanso Superserie ${fila.superserie_grupo}`
        : fila.ejercicio_nombre;
    restTimer.start({
        exerciseName: nombreLabel,
        durationSeconds: totalSegundos,
    });
};
</script>

<style scoped>
/* #4 Bottom sheet: slide-up desde abajo en mobile, fade en desktop */
.sheet-fade-enter-active,
.sheet-fade-leave-active {
    transition: opacity 0.25s ease;
}
.sheet-fade-enter-from,
.sheet-fade-leave-to {
    opacity: 0;
}

/* El contenido se desliza desde abajo */
.sheet-fade-enter-active .sheet-content,
.sheet-fade-leave-active .sheet-content {
    transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.sheet-fade-enter-from .sheet-content,
.sheet-fade-leave-to .sheet-content {
    transform: translateY(100%);
}

/* En desktop (sm:) el slide es solo fade (centrado) */
@media (min-width: 640px) {
    .sheet-fade-enter-from .sheet-content,
    .sheet-fade-leave-to .sheet-content {
        transform: translateY(0) scale(0.96);
    }
}
</style>
