<template>
    <div
        v-if="open"
        class="fixed inset-0 z-50 flex flex-col bg-[var(--color-obsidian-base)] text-white select-none overflow-hidden"
        role="dialog"
        aria-modal="true"
        aria-label="Modo Entrenamiento Activo"
    >
        <!-- Top Bar: Cronómetro, Rutina y Controles (Kinetic Obsidian) -->
        <header
            class="px-3 py-2 sm:px-4 sm:py-2.5 bg-[var(--color-obsidian-surface)]/95 border-b border-[var(--color-obsidian-border)] flex items-center justify-between gap-2.5 backdrop-blur-xl shrink-0"
        >
            <div class="flex items-center gap-2 sm:gap-3 min-w-0">
                <button
                    type="button"
                    @click="$emit('minimize')"
                    class="w-8 h-8 sm:w-9 sm:h-9 rounded-full bg-[var(--color-obsidian-elevated)] hover:bg-[var(--color-obsidian-overlay)] text-gray-300 hover:text-white transition-colors cursor-pointer border border-[var(--color-obsidian-border)] flex items-center justify-center shrink-0"
                    aria-label="Minimizar sesión"
                    title="Volver / Minimizar (continúa en segundo plano)"
                >
                    <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M15 19l-7-7 7-7"
                        />
                    </svg>
                </button>

                <div class="min-w-0">
                    <p
                        class="text-[10px] font-black uppercase tracking-[0.16em] text-violet-300 truncate flex items-center gap-1.5"
                    >
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        {{ store.session.dia }} · {{ store.session.rutina_nombre }}
                    </p>
                    <div class="flex items-center gap-2 mt-0.5">
                        <span class="text-xl sm:text-2xl font-mono font-black tracking-tight tabular-nums">
                            {{ formattedTime }}
                        </span>
                        <span v-if="store.isPaused" class="obs-pill obs-pill-orange">
                            ⏸ Pausa
                        </span>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <!-- Botón Pausa / Reanudar -->
                <button
                    type="button"
                    @click="togglePause"
                    class="p-2 sm:p-2.5 rounded-xl border border-[var(--color-obsidian-border-strong)] bg-[var(--color-obsidian-elevated)] hover:bg-[var(--color-obsidian-overlay)] text-gray-200 transition-all cursor-pointer"
                    :title="store.isPaused ? 'Reanudar cronómetro' : 'Pausar cronómetro'"
                    :aria-label="store.isPaused ? 'Reanudar' : 'Pausar'"
                >
                    <svg
                        v-if="!store.isPaused"
                        class="w-4 h-4 sm:w-5 sm:h-5 text-amber-400"
                        fill="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z" />
                    </svg>
                    <svg
                        v-else
                        class="w-4 h-4 sm:w-5 sm:h-5 text-emerald-400"
                        fill="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path d="M8 5v14l11-7z" />
                    </svg>
                </button>

                <!-- Botón Finalizar -->
                <button
                    type="button"
                    @click="handleFinalizar"
                    class="px-3 sm:px-4 py-2 sm:py-2.5 rounded-xl bg-gradient-to-br from-[var(--color-violet-deep)] via-[var(--color-violet-primary)] to-[var(--color-violet-light)] hover:brightness-110 text-white text-xs font-black shadow-[0_8px_24px_var(--color-violet-glow)] transition-all cursor-pointer active:scale-95 flex items-center gap-1.5"
                >
                    <svg class="w-3.5 h-3.5 fill-current shrink-0" viewBox="0 0 24 24">
                        <rect x="5" y="5" width="14" height="14" rx="2.5" />
                    </svg>
                    <span>Finalizar</span>
                </button>
            </div>
        </header>

        <!-- Barra de Progreso Global -->
        <div
            class="w-full bg-[var(--color-obsidian-surface)] border-b border-[var(--color-obsidian-border)] px-3 py-1.5 sm:px-4 sm:py-2 shrink-0 flex items-center justify-between gap-3 text-xs"
        >
            <span class="text-gray-300 font-semibold whitespace-nowrap">
                Progreso:
                <strong class="text-emerald-300 tabular-nums">{{ store.totalSeriesCompletadas }}</strong>
                /
                <span class="text-gray-400">{{ store.totalSeriesObjetivo }}</span>
                series
            </span>
            <div class="flex-1 h-1.5 sm:h-2 bg-[var(--color-obsidian-elevated)] rounded-full overflow-hidden mx-1 border border-[var(--color-obsidian-border)]">
                <div
                    class="h-full bg-gradient-to-r from-[var(--color-violet-deep)] via-[var(--color-violet-primary)] to-emerald-400 transition-all duration-300"
                    :style="{ width: `${store.progresoPorcentaje}%` }"
                ></div>
            </div>
            <span class="font-black text-violet-300 tabular-nums whitespace-nowrap">
                {{ store.progresoPorcentaje }}%
            </span>
        </div>

        <!-- Contenedor Principal Adaptable (Mobile + Web Responsive 2 Columnas) -->
        <main class="flex-1 overflow-y-auto px-2.5 sm:px-4 py-2.5 sm:py-3.5 overscroll-contain">
            <div
                v-if="store.currentEjercicio"
                class="max-w-6xl xl:max-w-7xl mx-auto w-full flex flex-col md:grid md:grid-cols-12 gap-2.5 sm:gap-3 lg:gap-4 items-start"
            >
                <!-- COLUMNA IZQUIERDA (Desktop: col-span-5) -->
                <div class="w-full md:col-span-5 lg:col-span-5 flex flex-col gap-2.5 sm:gap-3">
                    <!-- CARD HERO UNIFICADA DEL EJERCICIO (Visual + Ejercicio + Mínimo / Máximo + Sugerencia) -->
                    <section
                        class="obs-card-elevated p-2.5 sm:p-3.5 rounded-2xl border border-[var(--color-obsidian-border-strong)] bg-[var(--color-obsidian-surface)] shadow-xl relative"
                        data-testid="exercise-media"
                    >
                        <!-- Metadata invisible para accesibilidad y tests -->
                        <span class="sr-only" data-testid="ejercicio-progress-pill">
                            EJERCICIO {{ store.unidadProgreso.actual }} DE {{ store.unidadProgreso.total }}
                        </span>
                        <span class="sr-only">
                            {{ store.currentEjercicio.series_objetivo }} series
                        </span>

                        <div class="flex items-start gap-3 sm:gap-4">
                            <!-- Demostración Visual con fondo blanco (Lado Izquierdo) -->
                            <div
                                class="relative w-20 h-20 sm:w-24 sm:h-24 md:w-28 md:h-28 rounded-2xl bg-white p-1 flex items-center justify-center shrink-0 overflow-hidden shadow-inner cursor-pointer group self-center"
                                @click="gifHovered = !gifHovered"
                                :title="gifHovered ? 'Animación activa (tocá para pausar)' : 'Tocá para animar'"
                            >
                                <img
                                    v-if="vistaVisible && (ejercicioMedia?.gif_url || ejercicioMedia?.image_url)"
                                    :key="mediaEjercicio?.nombre"
                                    :src="gifHovered && ejercicioMedia.gif_url ? ejercicioMedia.gif_url : (ejercicioMedia.image_url || ejercicioMedia.gif_url)"
                                    :alt="`Demostración de ${mediaEjercicio?.nombre}`"
                                    class="h-full w-full object-contain transition-transform duration-200 group-hover:scale-105 select-none"
                                    loading="lazy"
                                />
                                <div
                                    v-else-if="!vistaVisible"
                                    class="flex flex-col items-center justify-center text-gray-500 text-[10px] text-center p-2"
                                >
                                    <span class="text-sm">👁️</span>
                                    <span>Vista oculta</span>
                                </div>
                                <div
                                    v-else
                                    class="flex flex-col items-center justify-center text-gray-400 text-xs"
                                >
                                    <span class="text-2xl">🏋️</span>
                                </div>

                                <!-- Flechas para alternar media en superset -->
                                <button
                                    v-if="superseriePartner && mediaEjercicioIndex !== store.session.currentEjercicioIndex"
                                    type="button"
                                    @click.stop="mediaEjercicioIndex = store.session.currentEjercicioIndex"
                                    data-testid="media-prev"
                                    :title="`Ver ${store.currentEjercicio.nombre}`"
                                    aria-label="Ver imagen del ejercicio actual"
                                    class="absolute left-1 top-1/2 -translate-y-1/2 z-10 p-1 rounded-full bg-violet-600/90 hover:bg-violet-500 text-white shadow transition-all cursor-pointer"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                                    </svg>
                                </button>
                                <button
                                    v-if="superseriePartner && mediaEjercicioIndex === store.session.currentEjercicioIndex"
                                    type="button"
                                    @click.stop="mediaEjercicioIndex = superseriePartner.index"
                                    data-testid="media-next"
                                    :title="`Ver ${superseriePartner.ejercicio.nombre}`"
                                    aria-label="Ver imagen del compañero de superset"
                                    class="absolute right-1 top-1/2 -translate-y-1/2 z-10 p-1 rounded-full bg-violet-600/90 hover:bg-violet-500 text-white shadow transition-all cursor-pointer"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Información y Métricas (Lado Derecho) -->
                            <div class="flex-1 min-w-0 flex flex-col justify-between py-0.5">
                                <!-- Fila 1: Badge Serie y Selector 1/6 con navegación y toggle ojo -->
                                <div class="flex items-center justify-between gap-1.5 flex-wrap">
                                    <span
                                        class="px-2.5 py-0.5 rounded-full bg-indigo-600/30 border border-indigo-500/40 text-indigo-300 text-[10px] sm:text-[11px] font-black uppercase tracking-wider"
                                    >
                                        SERIE #{{ activeSerieNumero }} DE {{ store.currentEjercicio?.series_objetivo || 1 }}
                                        <template v-if="currentTargetEsfuerzoHero">
                                            · 🎯 {{ currentTargetEsfuerzoHero.reps }}r @ {{ currentTargetEsfuerzoHero.tipo === 'fallo' ? 'FALLO' : 'RIR ' + currentTargetEsfuerzoHero.valor }}
                                        </template>
                                    </span>

                                    <!-- Controles de Navegación de Ejercicio (Anterior / Contador / Siguiente) -->
                                    <div class="flex items-center gap-1 bg-[var(--color-obsidian-surface)] border border-[var(--color-obsidian-border-strong)] rounded-xl p-0.5 shadow-sm">
                                        <!-- Botón Ejercicio Anterior -->
                                        <button
                                            type="button"
                                            @click="store.prevEjercicio"
                                            :disabled="store.session.currentEjercicioIndex === 0"
                                            class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-[var(--color-obsidian-elevated)] hover:bg-[var(--color-obsidian-overlay)] border border-[var(--color-obsidian-border)] text-gray-300 hover:text-white disabled:opacity-20 disabled:cursor-not-allowed flex items-center justify-center transition-all cursor-pointer"
                                            title="Ejercicio anterior"
                                            aria-label="Ejercicio anterior"
                                        >
                                            <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                                            </svg>
                                        </button>

                                        <!-- Contador X / Total -->
                                        <span class="px-1.5 text-[11px] sm:text-xs font-black text-white tabular-nums tracking-wide">
                                            {{ store.unidadProgreso.actual }}/{{ store.unidadProgreso.total }}
                                        </span>

                                        <!-- Botón Siguiente Ejercicio (Destacado y fácil de ver) -->
                                        <button
                                            type="button"
                                            @click="store.nextEjercicio"
                                            :disabled="store.session.currentEjercicioIndex >= store.session.ejercicios.length - 1"
                                            class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-violet-600/40 hover:bg-violet-600/70 border border-violet-500/50 text-violet-200 hover:text-white disabled:opacity-20 disabled:cursor-not-allowed flex items-center justify-center transition-all cursor-pointer shadow-[0_0_10px_rgba(139,92,246,0.25)]"
                                            title="Siguiente ejercicio"
                                            aria-label="Siguiente ejercicio"
                                        >
                                            <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                            </svg>
                                        </button>

                                        <!-- Botón Ojito para ocultar/mostrar vista del ejercicio -->
                                        <button
                                            type="button"
                                            @click="vistaVisible = !vistaVisible"
                                            :aria-pressed="!vistaVisible"
                                            :aria-label="vistaVisible ? 'Ocultar vista del ejercicio' : 'Mostrar vista del ejercicio'"
                                            :title="vistaVisible ? 'Ocultar vista' : 'Mostrar vista'"
                                            data-testid="toggle-exercise-view"
                                            class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg flex items-center justify-center text-gray-400 hover:text-white hover:bg-[var(--color-obsidian-overlay)] transition-colors cursor-pointer"
                                        >
                                            <svg v-if="vistaVisible" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7zM15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.575-2.706M6.223 6.223A9.956 9.956 0 0112 5c4.478 0 8.268 2.943 9.542 7a9.97 9.97 0 01-1.272 2.61M9.88 9.88a3 3 0 104.243 4.243M3 3l18 18" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <!-- Fila 2: Nombre del Ejercicio (o par de superserie) -->
                                <div v-if="!superserieOrder" class="mt-0.5">
                                    <div class="flex items-center justify-between gap-1.5 flex-wrap">
                                        <h2 class="text-sm sm:text-base font-black text-white leading-tight truncate" :title="mediaEjercicio?.nombre || store.currentEjercicio.nombre">
                                            {{ mediaEjercicio?.nombre || store.currentEjercicio.nombre }}
                                        </h2>
                                        <!-- Objetivo compacto de Hoy -->
                                        <div class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-lg bg-emerald-500/15 border border-emerald-500/35 text-emerald-300 font-bold text-[11px] sm:text-xs shrink-0">
                                            <span v-if="planSummaryHero">🎯 {{ planSummaryHero }}</span>
                                            <span v-else>🎯 {{ store.currentEjercicio.series_objetivo }} series × {{ formatRepsTarget(store.currentEjercicio) }}</span>
                                            <span v-if="store.currentEjercicio.descanso_min" class="text-emerald-400/40">·</span>
                                            <span v-if="store.currentEjercicio.descanso_min" class="text-gray-300 font-semibold text-[10px]">⏱️ {{ store.currentEjercicio.descanso_min }}m</span>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="mt-0.5 min-w-0">
                                    <div class="flex items-center gap-1.5 min-w-0">
                                        <span class="px-1.5 py-0.5 rounded-md bg-amber-500/20 text-amber-300 text-[9px] font-black uppercase tracking-wider shrink-0 border border-amber-500/40">
                                            ⚡ SUPERSERIE
                                        </span>
                                        <h2 class="text-xs sm:text-sm font-black text-white leading-tight truncate" :title="`${superserieOrder.first.ejercicio.nombre} + ${superserieOrder.second.ejercicio.nombre}`">
                                            {{ superserieOrder.first.ejercicio.nombre }} + {{ superserieOrder.second.ejercicio.nombre }}
                                        </h2>
                                    </div>
                                    <div class="flex items-center gap-1.5 flex-wrap mt-0.5 text-[10px] sm:text-[11px]">
                                        <span class="px-2 py-0.5 rounded-md bg-violet-500/20 text-violet-300 border border-violet-500/30 font-bold">
                                            1. {{ superserieOrder.first.ejercicio.series_objetivo }}s × {{ formatRepsTarget(superserieOrder.first.ejercicio) }}
                                        </span>
                                        <span class="px-2 py-0.5 rounded-md bg-violet-500/20 text-violet-300 border border-violet-500/30 font-bold">
                                            2. {{ superserieOrder.second.ejercicio.series_objetivo }}s × {{ formatRepsTarget(superserieOrder.second.ejercicio) }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Fila 3: Bloques MÍNIMO y MÁXIMO (Última vez) -->
                                <!-- Caso 1: Ejercicio individual -->
                                <div v-if="!superserieOrder" class="mt-1">
                                    <div
                                        class="grid grid-cols-2 gap-1.5"
                                        data-testid="last-exercise-card"
                                    >
                                        <!-- MÍNIMO -->
                                        <div
                                            class="px-2 py-1 rounded-xl bg-[var(--color-obsidian-surface)] border border-cyan-500/25 flex items-center justify-between gap-1"
                                            data-testid="last-exercise-min"
                                        >
                                            <div class="flex items-center gap-1 text-[9px] font-black text-teal-400 uppercase shrink-0">
                                                <svg class="w-2.5 h-2.5 text-teal-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3a3 3 0 00-3 3v1H7a2 2 0 00-2 2v9a2 2 0 002 2h10a2 2 0 002-2V9a2 2 0 00-2-2h-2V6a3 3 0 00-3-3zm-1 4a1 1 0 012 0v1h-2V7z" />
                                                </svg>
                                                <span>MÍN</span>
                                            </div>
                                            <div class="text-[11px] sm:text-xs font-black text-white tabular-nums text-right truncate">
                                                {{ formatPeso(lastExerciseData?.peso_min ?? lastExerciseData?.peso_top ?? 0) }} kg
                                                <span class="text-[9px] text-gray-400 font-normal">({{ (lastExerciseData?.reps_en_peso_min ?? lastExerciseData?.reps_en_peso_top) ?? 0 }} reps)</span>
                                            </div>
                                        </div>

                                        <!-- MÁXIMO (TOP) -->
                                        <div
                                            class="px-2 py-1 rounded-xl bg-[var(--color-obsidian-surface)] border border-fuchsia-500/25 flex items-center justify-between gap-1"
                                            data-testid="last-exercise-max"
                                        >
                                            <div class="flex items-center gap-1 text-[9px] font-black text-fuchsia-400 uppercase shrink-0">
                                                <span>▲ TOP</span>
                                                <span
                                                    v-if="lastExerciseData?.ultimo_esfuerzo"
                                                    class="px-1 py-0.2 rounded bg-violet-900/60 text-violet-300 text-[8px] font-black shrink-0"
                                                >
                                                    {{ lastExerciseData.ultimo_esfuerzo.tipo.toUpperCase() }} {{ lastExerciseData.ultimo_esfuerzo.valor }}
                                                </span>
                                            </div>
                                            <div class="text-[11px] sm:text-xs font-black text-white tabular-nums text-right truncate">
                                                {{ formatPeso(lastExerciseData?.peso_max ?? lastExerciseData?.peso_top ?? 0) }} kg
                                                <span class="text-[9px] text-gray-400 font-normal">({{ (lastExerciseData?.reps_en_peso_max ?? lastExerciseData?.reps_en_peso_top) ?? 0 }} reps)</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Caso 2: Superserie (muestra min y max para CADA UNO de los 2 ejercicios) -->
                                <div
                                    v-else
                                    class="grid grid-cols-1 md:grid-cols-2 gap-2 mt-2"
                                    data-testid="superset-dual-history"
                                >
                                    <!-- Ejercicio 1 de la superserie -->
                                    <div
                                        class="p-2 rounded-xl bg-[var(--color-obsidian-surface)] border border-violet-500/30 flex flex-col justify-between space-y-1.5"
                                        data-testid="superset-history-card-1"
                                    >
                                        <div class="flex items-center justify-between gap-1 border-b border-violet-500/20 pb-1">
                                            <span class="text-[10px] sm:text-[11px] font-black text-violet-200 uppercase truncate" :title="superserieOrder.first.ejercicio.nombre">
                                                1. {{ superserieOrder.first.ejercicio.nombre }}
                                            </span>
                                            <span
                                                v-if="lastExerciseDataFirst?.ultimo_esfuerzo"
                                                class="px-1.5 py-0.2 rounded-full bg-violet-900/60 border border-violet-500/40 text-violet-300 text-[8px] font-black shrink-0"
                                            >
                                                {{ lastExerciseDataFirst.ultimo_esfuerzo.tipo.toUpperCase() }} {{ lastExerciseDataFirst.ultimo_esfuerzo.valor }}
                                            </span>
                                        </div>
                                        <div class="grid grid-cols-2 gap-1.5">
                                            <div class="p-1 rounded-lg bg-[var(--color-obsidian-elevated)] border border-cyan-500/20 text-left">
                                                <div class="text-[8px] font-black text-teal-400 uppercase">MÍNIMO</div>
                                                <div class="text-xs font-black text-white tabular-nums">
                                                    {{ formatPeso(lastExerciseDataFirst?.peso_min ?? lastExerciseDataFirst?.peso_top ?? 0) }} kg
                                                </div>
                                                <div class="text-[9px] text-gray-400 tabular-nums">
                                                    x {{ (lastExerciseDataFirst?.reps_en_peso_min ?? lastExerciseDataFirst?.reps_en_peso_top) ?? 0 }} reps
                                                </div>
                                            </div>
                                            <div class="p-1 rounded-lg bg-[var(--color-obsidian-elevated)] border border-fuchsia-500/20 text-left">
                                                <div class="text-[8px] font-black text-fuchsia-400 uppercase">MÁXIMO</div>
                                                <div class="text-xs font-black text-white tabular-nums">
                                                    {{ formatPeso(lastExerciseDataFirst?.peso_max ?? lastExerciseDataFirst?.peso_top ?? 0) }} kg
                                                </div>
                                                <div class="text-[9px] text-gray-400 tabular-nums">
                                                    x {{ (lastExerciseDataFirst?.reps_en_peso_max ?? lastExerciseDataFirst?.reps_en_peso_top) ?? 0 }} reps
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Ejercicio 2 de la superserie -->
                                    <div
                                        class="p-2 rounded-xl bg-[var(--color-obsidian-surface)] border border-violet-500/30 flex flex-col justify-between space-y-1.5"
                                        data-testid="superset-history-card-2"
                                    >
                                        <div class="flex items-center justify-between gap-1 border-b border-violet-500/20 pb-1">
                                            <span class="text-[10px] sm:text-[11px] font-black text-violet-200 uppercase truncate" :title="superserieOrder.second.ejercicio.nombre">
                                                2. {{ superserieOrder.second.ejercicio.nombre }}
                                            </span>
                                            <span
                                                v-if="lastExerciseDataSecond?.ultimo_esfuerzo"
                                                class="px-1.5 py-0.2 rounded-full bg-violet-900/60 border border-violet-500/40 text-violet-300 text-[8px] font-black shrink-0"
                                            >
                                                {{ lastExerciseDataSecond.ultimo_esfuerzo.tipo.toUpperCase() }} {{ lastExerciseDataSecond.ultimo_esfuerzo.valor }}
                                            </span>
                                        </div>
                                        <div class="grid grid-cols-2 gap-1.5">
                                            <div class="p-1 rounded-lg bg-[var(--color-obsidian-elevated)] border border-cyan-500/20 text-left">
                                                <div class="text-[8px] font-black text-teal-400 uppercase">MÍNIMO</div>
                                                <div class="text-xs font-black text-white tabular-nums">
                                                    {{ formatPeso(lastExerciseDataSecond?.peso_min ?? lastExerciseDataSecond?.peso_top ?? 0) }} kg
                                                </div>
                                                <div class="text-[9px] text-gray-400 tabular-nums">
                                                    x {{ (lastExerciseDataSecond?.reps_en_peso_min ?? lastExerciseDataSecond?.reps_en_peso_top) ?? 0 }} reps
                                                </div>
                                            </div>
                                            <div class="p-1 rounded-lg bg-[var(--color-obsidian-elevated)] border border-fuchsia-500/20 text-left">
                                                <div class="text-[8px] font-black text-fuchsia-400 uppercase">MÁXIMO</div>
                                                <div class="text-xs font-black text-white tabular-nums">
                                                    {{ formatPeso(lastExerciseDataSecond?.peso_max ?? lastExerciseDataSecond?.peso_top ?? 0) }} kg
                                                </div>
                                                <div class="text-[9px] text-gray-400 tabular-nums">
                                                    x {{ (lastExerciseDataSecond?.reps_en_peso_max ?? lastExerciseDataSecond?.reps_en_peso_top) ?? 0 }} reps
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Fila 4: Sugerencia -->
                                <div
                                    v-if="!superserieOrder && recomendacion"
                                    class="flex items-start gap-1.5 text-[10px] sm:text-[11px] text-emerald-400 leading-tight mt-1.5 font-medium"
                                >
                                    <svg class="w-3.5 h-3.5 text-emerald-400 shrink-0 mt-0.5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                    <span>
                                        <strong class="font-bold">Sugerencia:</strong>
                                        {{ recomendacion.mensaje }}
                                        <span
                                            v-if="recomendacion.pesoSugerido != null && recomendacion.pesoSugerido !== (lastExerciseData?.peso_max ?? lastExerciseData?.peso_top)"
                                            class="font-black tabular-nums"
                                        >
                                            ({{ formatPeso(recomendacion.pesoSugerido) }} kg)
                                        </span>
                                    </span>
                                </div>
                                <div
                                    v-else-if="superserieOrder && (recomendacionFirst || recomendacionSecond)"
                                    class="flex flex-col gap-1 mt-1.5 text-[10px] sm:text-[11px] text-emerald-400 leading-tight font-medium"
                                >
                                    <div v-if="recomendacionFirst" class="flex items-start gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-emerald-400 shrink-0 mt-0.5" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                        </svg>
                                        <span>
                                            <strong class="font-bold">Sugerencia {{ superserieOrder.first.ejercicio.nombre }}:</strong>
                                            {{ recomendacionFirst.mensaje }}
                                        </span>
                                    </div>
                                    <div v-if="recomendacionSecond" class="flex items-start gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-emerald-400 shrink-0 mt-0.5" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                        </svg>
                                        <span>
                                            <strong class="font-bold">Sugerencia {{ superserieOrder.second.ejercicio.nombre }}:</strong>
                                            {{ recomendacionSecond.mensaje }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Panel destacado: Plan de series y esfuerzo / Prescripción (visible en pantallas grandes o complementario) -->
                    <div
                        v-if="store.currentEjercicio.notas"
                        class="hidden md:block p-2.5 sm:p-3 rounded-xl bg-gradient-to-br from-violet-950/40 via-[var(--color-obsidian-elevated)] to-[var(--color-obsidian-surface)] border border-violet-500/30 text-left space-y-2 shadow-inner"
                        data-testid="ejercicio-prescripcion-box"
                    >
                        <div class="flex items-center justify-between gap-2 border-b border-violet-500/20 pb-1.5">
                            <span class="flex items-center gap-1.5 text-[11px] font-black uppercase tracking-wider text-violet-300">
                                <span>📋</span>
                                <span>Plan de series y esfuerzo</span>
                            </span>
                        </div>

                        <!-- Desglose por bloques estructurados (ej: 2x6 con RIR 1) -->
                        <div
                            v-if="parseEsfuerzoBlocksActive(store.currentEjercicio.notas).length"
                            class="grid grid-cols-1 sm:grid-cols-2 gap-1.5"
                        >
                            <div
                                v-for="(block, i) in parseEsfuerzoBlocksActive(store.currentEjercicio.notas)"
                                :key="i"
                                class="flex items-center justify-between px-2.5 py-1.5 rounded-lg bg-[var(--color-obsidian-surface)] border text-xs"
                                :class="esfuerzoBlockClass(block)"
                            >
                                <span class="font-bold text-white flex items-center gap-1.5">
                                    <span class="text-violet-400 font-black">⚡</span>
                                    {{ block.series }} {{ block.series === 1 ? 'serie' : 'series' }} × {{ block.reps }} reps
                                </span>
                                <span class="font-black">
                                    con {{ esfuerzoBlockLabel(block) }}
                                </span>
                            </div>
                        </div>

                        <!-- Nota textual adicional (Rest-pause, técnicas, etc.) o completa si no coincide con el regex -->
                        <div
                            v-if="!parseEsfuerzoBlocksActive(store.currentEjercicio.notas).length || extraNotasText(store.currentEjercicio.notas)"
                            class="text-[11px] text-gray-300 flex items-start gap-1.5 pt-0.5"
                        >
                            <span class="text-amber-400 font-bold shrink-0">ℹ</span>
                            <span class="font-medium">
                                {{ extraNotasText(store.currentEjercicio.notas) || store.currentEjercicio.notas }}
                            </span>
                        </div>
                    </div>

                    <!-- Bloque "Superserie" en la columna izquierda -->
                    <div
                        v-if="superseriePartner && superserieOrder"
                        class="obs-card p-3 border-violet-500/30 bg-violet-500/5"
                        data-testid="superset-left-block"
                    >
                        <div class="flex items-center gap-2 mb-2">
                            <span class="text-base">⚡</span>
                            <p class="text-[11px] font-black uppercase tracking-[0.14em] text-violet-200">
                                Superserie {{ superseriePartner.ejercicio.superserie_grupo }}
                            </p>
                        </div>

                        <div class="space-y-1.5">
                            <!-- PRIMERO del par -->
                            <div
                                class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg border"
                                :class="
                                    superserieOrder.currentRole === 'first'
                                        ? 'bg-violet-500/15 border-violet-500/35'
                                        : 'bg-emerald-900/15 border-emerald-500/25'
                                "
                                :data-testid="`superset-left-first-${superserieOrder.first.index}`"
                            >
                                <span
                                    class="w-1.5 h-1.5 rounded-full shrink-0"
                                    :class="
                                        superserieOrder.currentRole === 'first'
                                            ? 'bg-emerald-400'
                                            : 'bg-emerald-500/70'
                                    "
                                ></span>
                                <span
                                    class="text-[10px] font-black uppercase tracking-wider w-16 shrink-0"
                                    :class="
                                        superserieOrder.currentRole === 'first'
                                            ? 'text-emerald-300'
                                            : 'text-emerald-400/70'
                                    "
                                >
                                    {{ superserieOrder.currentRole === 'first' ? 'Ahora' : 'Ya hiciste' }}
                                </span>
                                <span
                                    class="text-xs sm:text-sm font-bold truncate"
                                    :class="
                                        superserieOrder.currentRole === 'first'
                                            ? 'text-white'
                                            : 'text-gray-300 line-through decoration-emerald-500/40'
                                    "
                                >
                                    {{ superserieOrder.first.ejercicio.nombre }}
                                </span>
                            </div>

                            <!-- Conector visual entre los dos -->
                            <div class="flex items-center gap-2 px-2.5">
                                <span class="text-violet-400 text-[10px] font-black">↓</span>
                                <span class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">
                                    {{ superserieOrder.currentRole === 'first' ? 'Seguí directo con' : 'Ahora' }}
                                </span>
                            </div>

                            <!-- SEGUNDO del par -->
                            <div
                                class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg border"
                                :class="
                                    superserieOrder.currentRole === 'second'
                                        ? 'bg-violet-500/15 border-violet-500/35'
                                        : 'bg-[var(--color-obsidian-surface)] border-[var(--color-obsidian-border)]'
                                "
                                :data-testid="`superset-left-second-${superserieOrder.second.index}`"
                            >
                                <span
                                    class="w-1.5 h-1.5 rounded-full shrink-0"
                                    :class="
                                        superserieOrder.currentRole === 'second'
                                            ? 'bg-emerald-400'
                                            : 'bg-violet-400'
                                    "
                                ></span>
                                <span
                                    class="text-[10px] font-black uppercase tracking-wider w-16 shrink-0"
                                    :class="
                                        superserieOrder.currentRole === 'second'
                                            ? 'text-emerald-300'
                                            : 'text-violet-300'
                                    "
                                >
                                    {{ superserieOrder.currentRole === 'second' ? 'Ahora' : 'Después' }}
                                </span>
                                <span
                                    class="text-xs sm:text-sm font-bold truncate text-white"
                                >
                                    {{ superserieOrder.second.ejercicio.nombre }}
                                </span>
                            </div>
                        </div>

                        <p class="mt-2 text-[10px] text-gray-400 italic">
                            <template v-if="superserieOrder.currentRole === 'first'">
                                Sin descanso entre ambos. Registrá una serie en cada uno antes de la próxima pausa.
                            </template>
                            <template v-else>
                                Último del par. Después de esta serie arranca el descanso.
                            </template>
                        </p>
                    </div>

                    <!-- Lista de Series ya completadas en este ejercicio (Desktop) -->
                    <section
                        v-if="store.currentEjercicio?.sets?.length"
                        class="obs-card p-3 space-y-2 max-h-48 overflow-y-auto hidden md:block"
                    >
                        <div class="flex items-center justify-between">
                            <h3 class="text-[11px] font-black uppercase tracking-wider text-gray-300">
                                Series registradas ({{ store.currentEjercicio.sets.length }})
                            </h3>
                            <span class="text-[10px] text-violet-300 font-semibold truncate max-w-[160px]">
                                {{ store.currentEjercicio.nombre }}
                            </span>
                        </div>
                        <div class="space-y-1">
                            <div
                                v-for="(s, idx) in store.currentEjercicio.sets"
                                :key="idx"
                                class="flex items-center justify-between px-2.5 py-1.5 rounded-lg bg-[var(--color-obsidian-surface)] border border-[var(--color-obsidian-border)] text-xs"
                            >
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span
                                        class="font-black tabular-nums"
                                        :class="
                                            s.tipo_serie === 'calentamiento'
                                                ? 'text-indigo-300'
                                                : 'text-emerald-300'
                                        "
                                    >
                                        <template v-if="s.tipo_serie === 'calentamiento'">
                                            Calentamiento {{ s.series_numero }}
                                        </template>
                                        <template v-else>
                                            #{{ s.series_numero }}
                                        </template>
                                    </span>
                                    <span class="font-black text-white tabular-nums">{{ s.peso }} kg</span>
                                    <span class="text-gray-500">×</span>
                                    <span class="font-black text-white tabular-nums">{{ s.reps }} reps</span>
                                    <span
                                        v-if="s.tipo_serie && s.tipo_serie !== 'efectiva'"
                                        class="obs-pill obs-pill-amber text-[9px]"
                                    >
                                        {{ s.tipo_serie }}
                                    </span>
                                    <span
                                        v-if="s.esfuerzo_valor != null"
                                        class="obs-pill obs-pill-neutral text-[9px]"
                                    >
                                        {{ s.esfuerzo_tipo?.toUpperCase() }} {{ s.esfuerzo_valor }}
                                    </span>
                                </div>
                                <span class="text-[10px] text-gray-500 font-mono tabular-nums">
                                    {{ formatSetTime(s.completed_at) }}
                                </span>
                            </div>
                        </div>
                    </section>
                </div>

                <!-- COLUMNA DERECHA (Desktop: col-span-7) Focus Card: Configurar Serie -->
                <div class="w-full md:col-span-7 lg:col-span-7 flex flex-col gap-2.5 sm:gap-3">
                    <!-- Header explicativo cuando es superset -->
                    <div
                        v-if="superseriePartner && superserieOrder"
                        class="obs-card p-2.5 sm:p-3 flex items-center gap-2 border-violet-500/30 bg-violet-500/5"
                        data-testid="superset-banner"
                    >
                        <span class="text-base">⚡</span>
                        <div class="min-w-0 flex-1">
                            <p class="text-[11px] font-black uppercase tracking-[0.14em] text-violet-200">
                                Superserie {{ superseriePartner.ejercicio.superserie_grupo }}
                            </p>
                            <p class="text-[11px] text-gray-400">
                                <template v-if="superserieOrder.currentRole === 'first'">
                                    Hacé
                                    <strong class="text-white">{{ superserieOrder.first.ejercicio.nombre }}</strong>
                                    y seguí directo con
                                    <strong class="text-white">{{ superserieOrder.second.ejercicio.nombre }}</strong>
                                    sin descansar entre ellos.
                                </template>
                                <template v-else>
                                    Hacé
                                    <strong class="text-white">{{ superserieOrder.second.ejercicio.nombre }}</strong>
                                    — ya hiciste
                                    <strong class="text-white">{{ superserieOrder.first.ejercicio.nombre }}</strong>
                                    antes, seguí sin descansar.
                                </template>
                            </p>
                        </div>
                    </div>

                    <!-- Cuando NO es superserie: una sola card -->
                    <template v-if="!superseriePartner">
                        <SetConfigCard
                            :ejercicio="store.currentEjercicio"
                            :ejercicio-index="store.session.currentEjercicioIndex"
                            :last-data="lastExerciseData"
                            @complete="onSetComplete"
                        />

                        <!-- En mobile: lista desplegable debajo de la botonera para no empujarla fuera de pantalla -->
                        <div
                            v-if="store.currentEjercicio?.sets?.length"
                            class="md:hidden"
                        >
                            <details class="obs-card p-2 rounded-xl border border-[var(--color-obsidian-border)] group">
                                <summary class="flex items-center justify-between text-xs font-bold text-gray-300 cursor-pointer list-none select-none px-1">
                                    <span class="flex items-center gap-1.5">
                                        <span class="text-emerald-400 font-black">✓</span>
                                        <span>Series registradas de {{ store.currentEjercicio.nombre }} ({{ store.currentEjercicio.sets.length }})</span>
                                    </span>
                                    <span class="text-[10px] text-violet-400 group-open:rotate-180 transition-transform">▼</span>
                                </summary>
                                <div class="space-y-1 mt-2 pt-2 border-t border-[var(--color-obsidian-border)]">
                                    <div
                                        v-for="(s, idx) in store.currentEjercicio.sets"
                                        :key="idx"
                                        class="flex items-center justify-between px-2 py-1 rounded-lg bg-[var(--color-obsidian-surface)] text-[11px]"
                                    >
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <span
                                                class="font-black tabular-nums"
                                                :class="
                                                    s.tipo_serie === 'calentamiento'
                                                        ? 'text-indigo-300'
                                                        : 'text-emerald-300'
                                                "
                                            >
                                                <template v-if="s.tipo_serie === 'calentamiento'">
                                                    Calentamiento {{ s.series_numero }}
                                                </template>
                                                <template v-else>
                                                    #{{ s.series_numero }}
                                                </template>
                                            </span>
                                            <span class="font-black text-white tabular-nums">{{ s.peso }} kg</span>
                                            <span class="text-gray-500">×</span>
                                            <span class="font-black text-white tabular-nums">{{ s.reps }} reps</span>
                                            <span
                                                v-if="s.tipo_serie && s.tipo_serie !== 'efectiva'"
                                                class="obs-pill obs-pill-amber text-[9px]"
                                            >
                                                {{ s.tipo_serie }}
                                            </span>
                                            <span
                                                v-if="s.esfuerzo_valor != null"
                                                class="obs-pill obs-pill-neutral text-[9px]"
                                            >
                                                {{ s.esfuerzo_tipo?.toUpperCase() }} {{ s.esfuerzo_valor }}
                                            </span>
                                        </div>
                                        <span class="text-[10px] text-gray-500 font-mono tabular-nums">
                                            {{ formatSetTime(s.completed_at) }}
                                        </span>
                                    </div>
                                </div>
                            </details>
                        </div>
                    </template>

                    <!-- Cuando ES superset: 2 cards lado a lado (desktop) / stacked (mobile) + 1 solo botón de completar -->
                    <div
                        v-else-if="superserieOrder"
                        class="space-y-3"
                    >
                        <div
                            class="grid grid-cols-1 lg:grid-cols-2 gap-3"
                            data-testid="superset-grid"
                        >
                            <SetConfigCard
                                ref="cardFirstRef"
                                :ejercicio="superserieOrder.first.ejercicio"
                                :ejercicio-index="superserieOrder.first.index"
                                :show-exercise-name="true"
                                :hide-submit-button="true"
                                :last-data="lastExerciseDataFirst"
                                @complete="onSetComplete"
                            />
                            <SetConfigCard
                                ref="cardSecondRef"
                                :ejercicio="superserieOrder.second.ejercicio"
                                :ejercicio-index="superserieOrder.second.index"
                                :show-exercise-name="true"
                                :hide-submit-button="true"
                                :last-data="lastExerciseDataSecond"
                                @complete="onSetComplete"
                            />
                        </div>

                        <!-- Botón Único para completar la ronda de la Superserie -->
                        <div class="pt-1">
                            <button
                                v-if="!isSupersetComplete"
                                type="button"
                                @click="completarRondaSuperserie"
                                data-testid="btn-completar-superset"
                                class="w-full py-4 sm:py-5 rounded-2xl bg-gradient-to-br from-[var(--color-violet-deep)] via-[var(--color-violet-primary)] to-[var(--color-violet-light)] hover:brightness-110 active:scale-[0.98] text-white text-sm sm:text-base md:text-lg font-black tracking-wider shadow-[0_12px_32px_var(--color-violet-glow)] flex items-center justify-center gap-3 transition-all cursor-pointer border border-white/10"
                            >
                                <svg class="w-5 h-5 sm:w-6 sm:h-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="3"
                                        d="M5 13l4 4L19 7"
                                    />
                                </svg>
                                <span>
                                    ⚡ COMPLETAR RONDA #{{ supersetRondaNumero }} DE SUPERSERIE
                                </span>
                            </button>
                            <div
                                v-else
                                class="w-full py-4 sm:py-4.5 rounded-2xl bg-emerald-900/30 border border-emerald-500/40 text-emerald-300 text-sm sm:text-base font-black tracking-wider flex items-center justify-center gap-2.5"
                            >
                                ✓ Superserie completa
                            </div>
                        </div>
                    </div>

                    <!-- CTA Finalizar Sesión (solo cuando todo está completo) -->
                    <button
                        v-if="store.isSessionComplete"
                        type="button"
                        @click="handleFinalizar"
                        data-testid="btn-finalizar-sesion"
                        class="w-full py-4 sm:py-5 rounded-2xl bg-gradient-to-br from-emerald-600 via-emerald-500 to-teal-500 hover:brightness-110 active:scale-[0.98] text-white text-base sm:text-lg font-black tracking-wider shadow-[0_12px_36px_rgba(16,185,129,0.45)] flex items-center justify-center gap-3 transition-all cursor-pointer border border-white/15"
                    >
                        <svg class="w-6 h-6 sm:w-7 sm:h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="3"
                                d="M5 13l4 4L19 7"
                            />
                        </svg>
                        <span>FINALIZAR SESIÓN</span>
                        <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2.5"
                                d="M9 5l7 7-7 7"
                            />
                        </svg>
                    </button>

                    <!-- Botón Deshacer (solo sobre series del current ejercicio) -->
                    <div v-if="store.canUndo && !store.isSessionComplete" class="text-center pt-0.5">
                        <button
                            type="button"
                            @click="deshacer"
                            class="text-xs font-bold text-rose-300 hover:text-rose-200 underline underline-offset-4 cursor-pointer"
                        >
                            ↩ Deshacer última serie completada
                        </button>
                    </div>
                </div>
            </div>

            <!-- Estado de recuperación si por algún motivo no hay ejercicios activos cargados -->
            <div
                v-else
                class="max-w-md mx-auto my-12 text-center p-6 rounded-2xl bg-[var(--color-obsidian-surface)] border border-[var(--color-obsidian-border)]"
            >
                <div class="text-4xl mb-3">🏋️</div>
                <h3 class="text-lg font-black text-white mb-2">No hay ejercicios activos en esta sesión</h3>
                <p class="text-xs text-gray-400 mb-5">
                    La sesión se inició antes de sincronizar los ejercicios del día.
                </p>
                <div class="flex items-center justify-center gap-3">
                    <button
                        type="button"
                        @click="store.refreshNotasActuales"
                        class="px-4 py-2.5 rounded-xl bg-violet-600 hover:bg-violet-500 text-white text-xs font-bold transition-colors cursor-pointer"
                    >
                        🔄 Sincronizar ejercicios
                    </button>
                    <button
                        type="button"
                        @click="store.discard(); $emit('minimize')"
                        class="px-4 py-2.5 rounded-xl bg-[var(--color-obsidian-elevated)] hover:bg-[var(--color-obsidian-overlay)] border border-[var(--color-obsidian-border)] text-gray-300 text-xs font-bold transition-colors cursor-pointer"
                    >
                        Reiniciar sesión
                    </button>
                </div>
            </div>
        </main>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import { useTrainingSessionStore } from '@/stores/trainingSession';
import { useRestTimerStore } from '@/stores/restTimer';
import { useOfflineSeries } from '@/composables/useOfflineSeries';
import { useWakeLock } from '@/composables/useWakeLock';
import SetConfigCard from './SetConfigCard.vue';

const props = defineProps({
    open: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['minimize', 'finish', 'discard']);

const store = useTrainingSessionStore();
const restTimer = useRestTimerStore();
const offline = useOfflineSeries();
const wakeLock = useWakeLock();

// === Media del ejercicio actual (image_url + gif_url) ===
// Se carga via /api/ejercicios/media cuando cambia el ejercicio activo.
// Cache simple en `mediaCache` para no re-peir el mismo nombre.
const ejercicioMedia = ref(null);
const gifHovered = ref(true);
// Visibilidad del bloque "Vista del ejercicio". Por defecto se muestra; el
// usuario puede ocultarlo con el ojito si quiere mas espacio en pantalla.
const vistaVisible = ref(true);
const mediaCache = new Map();
let mediaFetchSeq = 0;

/**
 * Índice del ejercicio cuya imagen se está mostrando. Por defecto sigue al
 * `currentEjercicioIndex`, pero cuando hay superset el usuario puede
 * alternar con las flechas para ver la imagen del compañero sin perder el
 * foco en la card de carga.
 */
const mediaEjercicioIndex = ref(store.session.currentEjercicioIndex);
const mediaEjercicio = computed(() => {
    const ejs = store.session.ejercicios || [];
    return ejs[mediaEjercicioIndex.value] || ejs[store.session.currentEjercicioIndex] || null;
});

const formatRepsTarget = (ej) => {
    if (!ej) return '';
    const min = ej.reps_min;
    const max = ej.reps_max;
    if (min != null && max != null && min !== '' && max !== '') {
        return String(min) === String(max) ? `${min} reps` : `${min}–${max} reps`;
    }
    return `${min || max || 8} reps`;
};

async function loadEjercicioMedia(nombre) {
    if (!nombre) {
        ejercicioMedia.value = null;
        return;
    }
    if (mediaCache.has(nombre)) {
        ejercicioMedia.value = mediaCache.get(nombre);
        return;
    }
    const seq = ++mediaFetchSeq;
    try {
        const { data } = await axios.get('/api/ejercicios/media', {
            params: { name: nombre },
        });
        // Ignorar respuestas viejas si el user cambió de ejercicio mientras cargaba
        if (seq !== mediaFetchSeq) return;
        mediaCache.set(nombre, data);
        ejercicioMedia.value = data;
    } catch (e) {
        if (seq !== mediaFetchSeq) return;
        // 404 u otro error → no mostrar bloque
        ejercicioMedia.value = null;
    }
}

/**
 * Cuando cambia el ejercicio activo (currentEjercicioIndex) reseteamos el
 * media para que muestre la imagen del current. Si el usuario ya estaba
 * mirando al partner, lo dejamos donde estaba (caso típico: navegó con la
 * flecha y después cambió de ejercicio → la imagen salta al nuevo current).
 */
watch(
    () => store.session.currentEjercicioIndex,
    (newIdx) => {
        // Solo reseteamos si no estamos mirando al partner dentro del mismo par
        const partner = store.superseriePartnerFor(newIdx);
        const isLookingAtPartner =
            partner && mediaEjercicioIndex.value === partner.index;
        if (!isLookingAtPartner) {
            mediaEjercicioIndex.value = newIdx;
        }
    },
);

watch(
    () => mediaEjercicio.value?.nombre,
    (nombre) => {
        gifHovered.value = true;
        loadEjercicioMedia(nombre);
    },
    { immediate: true },
);

// Datos de la última vez que el usuario hizo este ejercicio.
// null mientras carga, {} sin encontrado=true si no hay histórico.
const lastExerciseData = ref(null);
const lastExerciseDataFirst = ref(null);
const lastExerciseDataSecond = ref(null);
let lastExerciseFetchToken = 0;

const cardFirstRef = ref(null);
const cardSecondRef = ref(null);

// Formateo del cronómetro de la sesión
const formattedTime = computed(() => {
    const s = store.elapsed;
    const hrs = Math.floor(s / 3600);
    const mins = Math.floor((s % 3600) / 60);
    const secs = s % 60;
    if (hrs > 0) {
        return `${String(hrs).padStart(2, '0')}:${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
    }
    return `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
});

const formatSetTime = (isoString) => {
    if (!isoString) return '';
    const d = new Date(isoString);
    return `${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`;
};

const togglePause = () => {
    if (store.isPaused) {
        store.resume();
    } else {
        store.pause();
    }
};

/**
 * Compuerzo de superserie: si el ejercicio actual pertenece a un grupo de
 * superset y hay otro ejercicio con el mismo grupo, lo retornamos junto con
 * su índice. Si no, null (modo "ejercicio simple", una sola card).
 */
const superseriePartner = computed(() => {
    if (!store.currentEjercicio) return null;
    return store.superseriePartnerFor(store.session.currentEjercicioIndex);
});

/**
 * Orden del par de superserie en función del currentEjercicioIndex.
 *
 * - `current`: el ejercicio que el usuario está haciendo ahora.
 * - `next`: el otro del par (el que sigue si todavía no lo hiciste, o el
 *   que ya hiciste si estás en el segundo).
 *
 * Como el orden de la rutina es lineal, el "primero" del par siempre es el
 * que tiene el índice MENOR. Esto nos sirve para que el banner y el bloque
 * izquierdo muestren el orden correcto:
 *
 *   currentIdx < partnerIdx → current es el primero, partner es el segundo
 *   currentIdx > partnerIdx → partner es el primero (ya hecho), current es el segundo
 */
const superserieOrder = computed(() => {
    const partner = superseriePartner.value;
    if (!partner) return null;
    const currentIdx = store.session.currentEjercicioIndex;
    const partnerIdx = partner.index;
    if (currentIdx < partnerIdx) {
        return {
            first: { index: currentIdx, ejercicio: store.currentEjercicio },
            second: { index: partnerIdx, ejercicio: partner.ejercicio },
            currentRole: 'first',
        };
    }
    return {
        first: { index: partnerIdx, ejercicio: partner.ejercicio },
        second: { index: currentIdx, ejercicio: store.currentEjercicio },
        currentRole: 'second',
    };
});

/**
 * Trae el "última vez" del ejercicio actual (o de ambos si es superserie)
 * para el Hero card y las cards de configuración.
 */
watch(
    [
        () => store.currentEjercicio?.nombre,
        () => superserieOrder.value?.first.ejercicio.nombre,
        () => superserieOrder.value?.second.ejercicio.nombre,
        () => store.session?.dia,
        () => store.session?.rutina_nombre,
    ],
    async ([currentNombre, firstNombre, secondNombre, dia, rutinaNombre]) => {
        if (!currentNombre) {
            lastExerciseData.value = null;
            lastExerciseDataFirst.value = null;
            lastExerciseDataSecond.value = null;
            return;
        }

        const token = ++lastExerciseFetchToken;
        const client = window?.axios || axios;
        const baseParams = {
            dia: dia || undefined,
            rutina_nombre: rutinaNombre || undefined,
        };

        if (superserieOrder.value && firstNombre && secondNombre) {
            try {
                const [res1, res2] = await Promise.all([
                    client.get('/api/historial/ultimo', { params: { ...baseParams, ejercicio: firstNombre } }),
                    client.get('/api/historial/ultimo', { params: { ...baseParams, ejercicio: secondNombre } }),
                ]);
                if (token !== lastExerciseFetchToken) return;
                lastExerciseDataFirst.value = res1?.data || null;
                lastExerciseDataSecond.value = res2?.data || null;
                lastExerciseData.value = res1?.data || null;
            } catch (err) {
                if (token === lastExerciseFetchToken) {
                    lastExerciseDataFirst.value = null;
                    lastExerciseDataSecond.value = null;
                    lastExerciseData.value = null;
                }
            }
        } else {
            try {
                const { data } = await client.get('/api/historial/ultimo', {
                    params: { ...baseParams, ejercicio: currentNombre },
                });
                if (token !== lastExerciseFetchToken) return;
                lastExerciseData.value = data || null;
                lastExerciseDataFirst.value = null;
                lastExerciseDataSecond.value = null;
            } catch (err) {
                if (token === lastExerciseFetchToken) {
                    lastExerciseData.value = null;
                    lastExerciseDataFirst.value = null;
                    lastExerciseDataSecond.value = null;
                }
            }
        }
    },
    { immediate: true }
);

// Formateo amigable: 2.5, 60, 100 (sin decimales raros para enteros).
const formatPeso = (val) => {
    const n = Number(val);
    if (!Number.isFinite(n)) return '0';
    return Number.isInteger(n) ? String(n) : n.toFixed(1).replace(/\.0$/, '');
};

// Recomendación basada en el esfuerzo percibido del set top de la última vez.
const computeRecomendacion = (data) => {
    if (!data || !data.encontrado || data.peso_top == null) return null;

    const esf = data.ultimo_esfuerzo;
    const pesoBase = Number(data.peso_top) || 0;
    const repsBase = Number(data.reps_en_peso_top) || 0;

    if (!esf) {
        return {
            icon: '🔁',
            colorClass: 'text-gray-300',
            pesoSugerido: pesoBase,
            mensaje: `mantenés ${formatPeso(pesoBase)} kg × ${repsBase || '?'} reps como en la última sesión.`,
        };
    }

    const tipo = (esf.tipo || '').toLowerCase();
    const valor = Number(esf.valor);

    if (tipo === 'rir') {
        if (valor >= 3) {
            return {
                icon: '⬆️',
                colorClass: 'text-emerald-300',
                pesoSugerido: pesoBase + 2.5,
                mensaje: `te quedaron ${valor} reps en reserva. Probá subir un poco para progresar.`,
            };
        }
        if (valor === 0) {
            return {
                icon: '⚠️',
                colorClass: 'text-amber-300',
                pesoSugerido: Math.max(0, pesoBase - 2.5),
                mensaje: 'la última vez llegaste al fallo. Bajá un poco o repetí el peso con mejor técnica.',
            };
        }
        // RIR 1-2 → óptimo
        return {
            icon: '✅',
            colorClass: 'text-emerald-300',
            pesoSugerido: pesoBase,
            mensaje: `estabas en la zona óptima (RIR ${valor}). Mantené el peso y buscá mejorar la técnica.`,
        };
    }

    if (tipo === 'rpe') {
        if (valor <= 7) {
            return {
                icon: '⬆️',
                colorClass: 'text-emerald-300',
                pesoSugerido: pesoBase + 2.5,
                mensaje: `fue muy fácil (RPE ${valor}). Probá subir peso para desafiarte.`,
            };
        }
        if (valor >= 10) {
            return {
                icon: '⚠️',
                colorClass: 'text-amber-300',
                pesoSugerido: Math.max(0, pesoBase - 2.5),
                mensaje: 'la última vez fue al fallo absoluto (RPE 10). Bajá o repetí el peso.',
            };
        }
        // RPE 8-9 → óptimo
        return {
            icon: '✅',
            colorClass: 'text-emerald-300',
            pesoSugerido: pesoBase,
            mensaje: `rendimiento óptimo (RPE ${valor}). Mantené el peso.`,
        };
    }

    return {
        icon: '🔁',
        colorClass: 'text-gray-300',
        pesoSugerido: pesoBase,
        mensaje: `repetí ${formatPeso(pesoBase)} kg × ${repsBase || '?'} reps como en la última sesión.`,
    };
};

const recomendacion = computed(() => computeRecomendacion(lastExerciseData.value));
const recomendacionFirst = computed(() => computeRecomendacion(lastExerciseDataFirst.value));
const recomendacionSecond = computed(() => computeRecomendacion(lastExerciseDataSecond.value));

const isSupersetComplete = computed(() => {
    if (!superserieOrder.value) return false;
    const ej1 = superserieOrder.value.first.ejercicio;
    const ej2 = superserieOrder.value.second.ejercicio;
    const eff1 = (ej1.sets || []).filter((s) => s.tipo_serie !== 'calentamiento').length;
    const eff2 = (ej2.sets || []).filter((s) => s.tipo_serie !== 'calentamiento').length;
    const target1 = Number(ej1.series_objetivo) || 0;
    const target2 = Number(ej2.series_objetivo) || 0;
    return eff1 >= target1 && eff2 >= target2;
});

const supersetRondaNumero = computed(() => {
    if (!superserieOrder.value) return 1;
    const ej1 = superserieOrder.value.first.ejercicio;
    const ej2 = superserieOrder.value.second.ejercicio;
    const eff1 = (ej1.sets || []).filter((s) => s.tipo_serie !== 'calentamiento').length;
    const eff2 = (ej2.sets || []).filter((s) => s.tipo_serie !== 'calentamiento').length;
    return Math.max(eff1, eff2) + 1;
});

const completarRondaSuperserie = async () => {
    if (!superserieOrder.value) return;

    const payloadFirst = cardFirstRef.value?.getPayload();
    const payloadSecond = cardSecondRef.value?.getPayload();

    const firstComplete = cardFirstRef.value?.ejercicioCompleto;
    const secondComplete = cardSecondRef.value?.ejercicioCompleto;

    // Completar en orden lineal (primero el first, luego el second)
    if (payloadFirst && !firstComplete) {
        await onSetComplete(payloadFirst, { skipTimer: true });
    }
    if (payloadSecond && !secondComplete) {
        await onSetComplete(payloadSecond, { skipTimer: true });
    }

    // Iniciar temporizador de descanso combinado de la superserie
    const ej1 = superserieOrder.value.first.ejercicio;
    const ej2 = superserieOrder.value.second.ejercicio;
    const d1 = Number(ej1.descanso_min) || 1.5;
    const d2 = Number(ej2.descanso_min) || 1.5;
    const descansoFinal = Math.max(d1, d2);
    const descansoSegundos = Math.max(15, Math.round(descansoFinal * 60));
    restTimer.start(descansoSegundos, `${ej1.nombre} + ${ej2.nombre}`);
};

/**
 * Handler que recibe el evento `complete` del SetConfigCard (sea el del
 * ejercicio principal o el del compañero de superset). Persiste la serie
 * para el ejercicioIndex correcto y dispara el rest timer del ejercicio
 * que se acaba de registrar.
 */
const onSetComplete = async (payload, { skipTimer = false } = {}) => {
    const {
        ejercicioIndex,
        peso,
        reps,
        tipo_serie,
        esfuerzo_tipo,
        esfuerzo_valor,
        nota_user,
    } = payload;
    const ej = store.session.ejercicios[ejercicioIndex];
    if (!ej) return;

    const isWarmup = tipo_serie === 'calentamiento';
    const isCurrent = ejercicioIndex === store.session.currentEjercicioIndex;

    // Numero de serie que va al backend: si es el current ejercicio usamos el
    // contador global; si es el partner de superset usamos el contador local
    // de ese ejercicio (el store ya lo calcula, pero acá lo necesitamos
    // antes de llamar a recordSetAt para la persistencia offline).
    let currentSerieNum;
    if (isCurrent) {
        currentSerieNum = isWarmup
            ? (store.session.currentCalentamientoNumero || 1)
            : store.session.currentSerieNumero;
    } else {
        const completedEffective = (ej.sets || []).filter(
            (s) => s.tipo_serie !== 'calentamiento'
        ).length;
        currentSerieNum = completedEffective + 1;
    }

    // 1. Guardar en Pinia store (en el índice correcto)
    store.recordSetAt(ejercicioIndex, {
        peso,
        reps,
        tipo_serie,
        esfuerzo_tipo,
        esfuerzo_valor,
        nota_user,
    });

    // 2. Haptic feedback
    if (typeof navigator !== 'undefined' && navigator.vibrate) {
        navigator.vibrate([80, 50, 80]);
    }

    // 3. Persistir en backend / IndexedDB offline
    try {
        await offline.recordSet({
            fecha: new Date().toISOString().split('T')[0],
            rutina_nombre: store.session.rutina_nombre,
            dia: store.session.dia,
            ejercicio_nombre: ej.nombre,
            series_numero: currentSerieNum,
            series_completadas: 1,
            reps_min: String(ej.reps_min || '8'),
            reps_max: String(ej.reps_max || '10'),
            reps_realizadas: reps,
            descanso_min: Number(ej.descanso_min || 1.5),
            peso,
            completado: true,
            tipo_serie,
            sesion_uuid: store.session.id,
            esfuerzo_tipo,
            esfuerzo_valor,
            nota_user,
        });
    } catch (e) {
        console.warn('Error guardando serie en offline/API:', e);
    }

    // 4. Iniciar temporizador de descanso si no se saltea (por superserie)
    if (!skipTimer) {
        const descansoSegundos = Math.max(15, Math.round((Number(ej.descanso_min) || 1.5) * 60));
        restTimer.start(descansoSegundos, ej.nombre);
    }
};

const deshacer = () => {
    // El form local ahora vive dentro del SetConfigCard del ejercicio
    // principal, así que solo necesitamos pedirle al store que revierta
    // la última serie del current ejercicio. La card re-renderiza con
    // los valores previos al watch interno.
    store.undoLastSet();
};

const handleFinalizar = () => {
    emit('finish');
};

// Parsea bloques de esfuerzo desde notas (mismo formato que RutinaAcordeon.vue).
//   "2x6 RIR 1" | "2*6 RIP 1" | "2x6 RPE 8" | "2x8 FALLO" | "2x8 AL FALLO TÉCNICO"
//   → { series, reps, tipo:'rir'|'rpe'|'fallo', valor|null }
const parseEsfuerzoBlocksActive = (notas) => {
    if (!notas) return [];
    const blocks = [];
    const re = /(\d+)\s*[xX\*]\s*(\d+)\s*(?:R[I1][RP]\s*(\d+)|RPE\s*(\d+)|(?:AL\s+)?FALLO(?:\s+T[ÉE]CNICO)?)/gi;
    for (const m of String(notas).matchAll(re)) {
        if (m[3] !== undefined) {
            blocks.push({ series: Number(m[1]), reps: Number(m[2]), tipo: 'rir', valor: Number(m[3]) });
        } else if (m[4] !== undefined) {
            blocks.push({ series: Number(m[1]), reps: Number(m[2]), tipo: 'rpe', valor: Number(m[4]) });
        } else {
            blocks.push({ series: Number(m[1]), reps: Number(m[2]), tipo: 'fallo', valor: null });
        }
    }
    return blocks;
};

// Obtiene texto descriptivo adicional de notas que no sea la parte de series/RIR/RPE
const extraNotasText = (notas) => {
    if (!notas) return '';
    const re = /(\d+)\s*[xX\*]\s*(\d+)\s*(?:R[I1][RP]\s*(\d+)|RPE\s*(\d+)|(?:AL\s+)?FALLO(?:\s+T[ÉE]CNICO)?)/gi;
    const cleaned = String(notas)
        .replace(re, '')
        .replace(/^\s*\+\s*/g, '')
        .replace(/\s*\+\s*$/g, '')
        .trim();
    return cleaned;
};

// Retrocompat: alias al parser nuevo.
const parseRirBlocksActive = (notas) => parseEsfuerzoBlocksActive(notas);

const esfuerzoBlockLabel = (b) => {
    if (b.tipo === 'fallo') return 'FALLO';
    return `${b.tipo.toUpperCase()} ${b.valor}`;
};

const esfuerzoBlockClass = (b) => {
    if (b.tipo === 'fallo') return 'bg-rose-500/25 text-rose-200 border border-rose-500/40';
    if (b.tipo === 'rir' && b.valor === 0) return 'bg-rose-500/25 text-rose-300 border border-rose-500/40';
    return 'bg-amber-500/20 text-amber-300 border border-amber-500/30';
};

const activeSetsEfectivos = computed(() => {
    return (store.currentEjercicio?.sets || []).filter((s) => s.tipo_serie !== 'calentamiento').length;
});

const activeSerieNumero = computed(() => {
    return activeSetsEfectivos.value + 1;
});

const rirBlocksHero = computed(() => {
    return parseEsfuerzoBlocksActive(store.currentEjercicio?.notas);
});

const currentTargetEsfuerzoHero = computed(() => {
    if (!rirBlocksHero.value || rirBlocksHero.value.length === 0) return null;
    const setNum = activeSerieNumero.value;
    let acumulado = 0;
    for (const b of rirBlocksHero.value) {
        acumulado += b.series;
        if (setNum <= acumulado) {
            return { tipo: b.tipo, valor: b.valor, reps: b.reps, bloque: b };
        }
    }
    return null;
});

const planSummaryHero = computed(() => {
    if (!rirBlocksHero.value || rirBlocksHero.value.length === 0) return null;
    return rirBlocksHero.value
        .map((b) => `${b.series}×${b.reps} ${b.tipo === 'fallo' ? 'FALLO' : 'RIR ' + b.valor}`)
        .join(' + ');
});

onMounted(() => {
    if (props.open) {
        wakeLock.requestWakeLock();
        // Refrescar las `notas` (y metadata liviana) desde el backend por si
        // la rutina fue editada desde otro dispositivo mientras la sesión
        // estaba activa. No-op si no hay sesión o si el backend no responde.
        store.refreshNotasActuales();
    }
});

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            wakeLock.requestWakeLock();
            store.refreshNotasActuales();
        } else {
            wakeLock.releaseWakeLock();
        }
    }
);

onUnmounted(() => {
    wakeLock.releaseWakeLock();
});
</script>
