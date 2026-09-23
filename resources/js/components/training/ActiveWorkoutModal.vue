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
            class="px-4 py-3 bg-[var(--color-obsidian-surface)]/95 border-b border-[var(--color-obsidian-border)] flex items-center justify-between gap-3 backdrop-blur-xl shrink-0"
        >
            <div class="flex items-center gap-3 min-w-0">
                <button
                    type="button"
                    @click="$emit('minimize')"
                    class="p-2 rounded-xl bg-[var(--color-obsidian-elevated)] hover:bg-[var(--color-obsidian-overlay)] text-gray-300 transition-colors cursor-pointer border border-[var(--color-obsidian-border)]"
                    aria-label="Minimizar sesión"
                    title="Minimizar (continúa en segundo plano)"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M19 9l-7 7-7-7"
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
                        <span class="text-2xl font-mono font-black tracking-tight tabular-nums">
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
                    class="p-2.5 rounded-xl border border-[var(--color-obsidian-border-strong)] bg-[var(--color-obsidian-elevated)] hover:bg-[var(--color-obsidian-overlay)] text-gray-200 transition-all cursor-pointer"
                    :title="store.isPaused ? 'Reanudar cronómetro' : 'Pausar cronómetro'"
                    :aria-label="store.isPaused ? 'Reanudar' : 'Pausar'"
                >
                    <svg
                        v-if="!store.isPaused"
                        class="w-5 h-5 text-amber-400"
                        fill="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z" />
                    </svg>
                    <svg
                        v-else
                        class="w-5 h-5 text-emerald-400"
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
                    class="px-4 py-2.5 rounded-xl bg-gradient-to-br from-[var(--color-violet-deep)] via-[var(--color-violet-primary)] to-[var(--color-violet-light)] hover:brightness-110 text-white text-xs font-black shadow-[0_8px_24px_var(--color-violet-glow)] transition-all cursor-pointer active:scale-95"
                >
                    Finalizar
                </button>
            </div>
        </header>

        <!-- Barra de Progreso Global -->
        <div
            class="w-full bg-[var(--color-obsidian-surface)] border-b border-[var(--color-obsidian-border)] px-4 py-2.5 shrink-0 flex items-center justify-between gap-3 text-xs"
        >
            <span class="text-gray-300 font-semibold whitespace-nowrap">
                Progreso:
                <strong class="text-emerald-300 tabular-nums">{{ store.totalSeriesCompletadas }}</strong>
                /
                <span class="text-gray-400">{{ store.totalSeriesObjetivo }}</span>
                series
            </span>
            <div class="flex-1 h-2 bg-[var(--color-obsidian-elevated)] rounded-full overflow-hidden mx-1 border border-[var(--color-obsidian-border)]">
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
        <main class="flex-1 overflow-y-auto px-3 sm:px-4 py-3 sm:py-4 overscroll-contain">
            <div
                v-if="store.currentEjercicio"
                class="max-w-6xl xl:max-w-7xl mx-auto w-full flex flex-col md:grid md:grid-cols-12 gap-3 lg:gap-5 items-start"
            >
                <!-- COLUMNA IZQUIERDA (Desktop: col-span-5) -->
                <div class="w-full md:col-span-5 lg:col-span-5 flex flex-col gap-3">
                    <!-- Selector de Ejercicios / Carrusel de navegación -->
                    <div class="obs-card-elevated p-3 sm:p-4 space-y-2">
                        <div class="flex items-center justify-between gap-2">
                            <button
                                type="button"
                                @click="store.prevEjercicio"
                                :disabled="store.session.currentEjercicioIndex === 0"
                                class="p-2 sm:p-2.5 rounded-xl bg-[var(--color-obsidian-elevated)] border border-[var(--color-obsidian-border-strong)] text-gray-300 disabled:opacity-30 disabled:cursor-not-allowed cursor-pointer hover:bg-[var(--color-obsidian-overlay)] transition-colors"
                                aria-label="Ejercicio anterior"
                            >
                                <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
                                </svg>
                            </button>

                            <div class="text-center min-w-0 flex-1">
                                <span class="obs-pill obs-pill-violet text-[10px]">
                                    EJERCICIO {{ store.session.currentEjercicioIndex + 1 }} DE
                                    {{ store.session.ejercicios.length }}
                                </span>
                                <h2 class="text-lg sm:text-xl md:text-2xl font-black text-white truncate mt-1">
                                    {{ store.currentEjercicio.nombre }}
                                </h2>
                            </div>

                            <button
                                type="button"
                                @click="store.nextEjercicio"
                                :disabled="
                                    store.session.currentEjercicioIndex >= store.session.ejercicios.length - 1
                                "
                                class="p-2 sm:p-2.5 rounded-xl bg-[var(--color-obsidian-elevated)] border border-[var(--color-obsidian-border-strong)] text-gray-300 disabled:opacity-30 disabled:cursor-not-allowed cursor-pointer hover:bg-[var(--color-obsidian-overlay)] transition-colors"
                                aria-label="Siguiente ejercicio"
                            >
                                <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                        </div>

                        <div class="flex items-center justify-center gap-1.5 sm:gap-2 flex-wrap pt-0.5">
                            <span class="obs-pill obs-pill-violet text-[10px] sm:text-xs">
                                🎯 {{ store.currentEjercicio.reps_min }}–{{
                                    store.currentEjercicio.reps_max
                                }} reps
                            </span>
                            <span class="obs-pill obs-pill-emerald text-[10px] sm:text-xs">
                                💪 {{ store.currentEjercicio.series_objetivo }}
                                {{ store.currentEjercicio.series_objetivo === 1 ? 'serie' : 'series' }}
                            </span>
                            <span class="obs-pill obs-pill-orange text-[10px] sm:text-xs">
                                ⏱ {{ store.currentEjercicio.descanso_min }} min rest
                            </span>
                            <span
                                v-if="store.currentEjercicio.superserie_grupo"
                                class="obs-pill obs-pill-emerald text-[10px] sm:text-xs"
                            >
                                SS {{ store.currentEjercicio.superserie_grupo }}
                            </span>
                        </div>
                    </div>

                    <!-- Demostración Visual / GIF Animado (data-testid="exercise-media") -->
                    <section
                        v-if="ejercicioMedia && (ejercicioMedia.gif_url || ejercicioMedia.image_url)"
                        class="obs-card p-3 relative flex flex-col items-center justify-center overflow-hidden"
                        data-testid="exercise-media"
                    >
                        <div class="w-full flex items-center justify-between gap-2 mb-2">
                            <span class="text-[10px] font-black uppercase tracking-wider text-violet-300 flex items-center gap-1.5">
                                <span class="w-1.5 h-1.5 rounded-full bg-violet-400"></span>
                                Vista del ejercicio
                            </span>
                            <button
                                v-if="ejercicioMedia.gif_url"
                                type="button"
                                @click="gifHovered = !gifHovered"
                                class="text-[10px] font-bold px-2 py-0.5 rounded-lg bg-[var(--color-obsidian-elevated)] border border-[var(--color-obsidian-border)] text-gray-300 hover:text-white transition-colors cursor-pointer"
                            >
                                {{ gifHovered ? '⏸ Pausar animación' : '▶ Ver animación' }}
                            </button>
                        </div>

                        <div
                            class="relative w-full h-40 sm:h-44 md:h-48 lg:h-52 rounded-xl overflow-hidden bg-[var(--color-obsidian-surface)] border border-[var(--color-obsidian-border)] flex items-center justify-center cursor-pointer group"
                            @click="gifHovered = !gifHovered"
                            @mouseenter="gifHovered = true"
                        >
                            <img
                                :src="gifHovered && ejercicioMedia.gif_url ? ejercicioMedia.gif_url : (ejercicioMedia.image_url || ejercicioMedia.gif_url)"
                                :alt="`Demostración de ${store.currentEjercicio.nombre}`"
                                class="h-full w-full object-contain p-1.5 rounded-xl transition-transform duration-200 group-hover:scale-105"
                                loading="lazy"
                            />
                            <div
                                v-if="!gifHovered && ejercicioMedia.gif_url"
                                class="absolute inset-0 bg-black/40 backdrop-blur-[1px] flex items-center justify-center text-white text-xs font-bold gap-1.5 opacity-90 group-hover:opacity-100 transition-opacity"
                            >
                                <span class="p-2 rounded-full bg-violet-600/80 shadow-lg">▶</span>
                                <span>Pasá el mouse o tocá para ver animación</span>
                            </div>
                        </div>
                    </section>

                    <!-- Referencia del ejercicio: última vez + recomendación -->
                    <div
                        v-if="lastExerciseData && lastExerciseData.encontrado"
                        class="rounded-xl border border-violet-500/30 bg-violet-500/10 p-2.5 sm:p-3 space-y-1 text-xs"
                    >
                        <div class="flex items-center justify-between gap-2 flex-wrap">
                            <span class="text-[10px] font-black uppercase tracking-[0.14em] text-violet-200 flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Última vez
                            </span>
                            <span class="text-[10px] text-gray-400 font-semibold">
                                {{ lastExerciseData.fecha }}
                            </span>
                        </div>
                        <p class="text-xs sm:text-sm font-bold text-white tabular-nums">
                            Top:
                            <span class="text-violet-200">{{ formatPeso(lastExerciseData.peso_top) }} kg</span>
                            <span class="text-gray-500 mx-1">×</span>
                            <span class="text-emerald-200">{{ lastExerciseData.reps_en_peso_top }} reps</span>
                            <span
                                v-if="lastExerciseData.ultimo_esfuerzo"
                                class="ml-1.5 obs-pill text-[9px]"
                                :class="
                                    lastExerciseData.ultimo_esfuerzo.tipo === 'rir'
                                        ? 'obs-pill-violet'
                                        : 'obs-pill-orange'
                                "
                            >
                                {{ lastExerciseData.ultimo_esfuerzo.tipo.toUpperCase() }}
                                {{ lastExerciseData.ultimo_esfuerzo.valor }}
                            </span>
                        </p>
                        <p
                            v-if="recomendacion"
                            class="text-[11px] sm:text-xs flex items-start gap-1.5"
                            :class="recomendacion.colorClass"
                        >
                            <span class="font-black">{{ recomendacion.icon }}</span>
                            <span>
                                <span class="font-bold">Sugerencia:</span>
                                {{ recomendacion.mensaje }}
                                <span
                                    v-if="recomendacion.pesoSugerido != null && recomendacion.pesoSugerido !== lastExerciseData.peso_top"
                                    class="font-black tabular-nums"
                                >
                                    ({{ formatPeso(recomendacion.pesoSugerido) }} kg)
                                </span>
                            </span>
                        </p>
                    </div>

                    <!-- Lista de Series ya completadas en este ejercicio -->
                    <section
                        v-if="store.currentEjercicio?.sets?.length"
                        class="obs-card p-3 space-y-2 max-h-48 overflow-y-auto"
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
                <div class="w-full md:col-span-7 lg:col-span-7 flex flex-col gap-3">
                    <section
                        class="obs-card-elevated p-3.5 sm:p-4 md:p-5 space-y-3 sm:space-y-3.5 shadow-[0_12px_40px_rgba(0,0,0,0.5)]"
                    >
                        <div class="flex items-center justify-between border-b border-[var(--color-obsidian-border)] pb-2.5 flex-wrap gap-2">
                            <span class="flex items-center gap-2 text-xs font-black uppercase tracking-wider text-gray-300">
                                <span
                                    class="w-1.5 h-1.5 rounded-full"
                                    :class="
                                        form.tipo_serie === 'calentamiento'
                                            ? 'bg-indigo-400'
                                            : 'bg-emerald-400'
                                    "
                                ></span>
                                <template v-if="form.tipo_serie === 'calentamiento'">
                                    Configurar Calentamiento #{{ store.session.currentCalentamientoNumero || 1 }}
                                </template>
                                <template v-else>
                                    Configurar Serie #{{ store.session.currentSerieNumero }}
                                </template>
                            </span>

                            <!-- Selector de Tipo de Serie (Kinetic Obsidian segmented) -->
                            <div class="inline-flex rounded-xl bg-[var(--color-obsidian-elevated)] p-0.5 sm:p-1 text-[10px] font-bold border border-[var(--color-obsidian-border)]">
                                <button
                                    v-for="tipo in tiposSerie"
                                    :key="tipo.id"
                                    type="button"
                                    @click="form.tipo_serie = tipo.id"
                                    :class="[
                                        'px-2 sm:px-2.5 py-1 rounded-lg transition-all cursor-pointer whitespace-nowrap',
                                        form.tipo_serie === tipo.id
                                            ? tipo.activeClass
                                            : 'text-gray-400 hover:text-white',
                                    ]"
                                >
                                    {{ tipo.label }}
                                </button>
                            </div>
                        </div>

                        <!-- Controles Glove Mode: PESO -->
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between">
                                <label class="text-[10px] font-black uppercase tracking-[0.16em] text-gray-300">
                                    CARGA / PESO
                                </label>
                                <span class="text-[10px] text-violet-300 font-bold">Toques rápidos (kg)</span>
                            </div>

                            <div class="flex items-center gap-1 sm:gap-1.5">
                                <!-- Botones decremento -->
                                <div class="grid grid-cols-3 gap-1 shrink-0">
                                    <button
                                        type="button"
                                        @click="ajustarPeso(-5)"
                                        class="w-9 sm:w-10 h-11 sm:h-12 bg-[var(--color-obsidian-elevated)] hover:bg-rose-500/20 active:scale-95 rounded-xl font-bold text-xs text-rose-300 cursor-pointer border border-[var(--color-obsidian-border)] transition-colors"
                                    >
                                        -5
                                    </button>
                                    <button
                                        type="button"
                                        @click="ajustarPeso(-2.5)"
                                        class="w-9 sm:w-10 h-11 sm:h-12 bg-[var(--color-obsidian-elevated)] hover:bg-rose-500/20 active:scale-95 rounded-xl font-bold text-xs text-rose-300 cursor-pointer border border-[var(--color-obsidian-border)] transition-colors"
                                    >
                                        -2.5
                                    </button>
                                    <button
                                        type="button"
                                        @click="ajustarPeso(-1)"
                                        class="w-9 sm:w-10 h-11 sm:h-12 bg-[var(--color-obsidian-elevated)] hover:bg-rose-500/20 active:scale-95 rounded-xl font-bold text-xs text-rose-300 cursor-pointer border border-[var(--color-obsidian-border)] transition-colors"
                                    >
                                        -1
                                    </button>
                                </div>

                                <!-- Display grande de peso -->
                                <div
                                    class="flex-1 relative flex items-center justify-center bg-gradient-to-br from-[var(--color-obsidian-elevated)] to-[var(--color-obsidian-surface)] border-2 border-violet-500/30 rounded-2xl h-11 sm:h-12 shadow-[0_0_20px_rgba(139,92,246,0.15)]"
                                >
                                    <input
                                        v-model.number="form.peso"
                                        type="number"
                                        inputmode="decimal"
                                        step="0.5"
                                        min="0"
                                        class="w-full bg-transparent text-center text-2xl sm:text-3xl font-black text-white outline-none tabular-nums"
                                        placeholder="0"
                                    />
                                    <span class="absolute right-2.5 sm:right-3 text-[11px] font-black text-violet-300 uppercase tracking-wider"
                                        >kg</span
                                    >
                                </div>

                                <!-- Botones incremento -->
                                <div class="grid grid-cols-3 gap-1 shrink-0">
                                    <button
                                        type="button"
                                        @click="ajustarPeso(1)"
                                        class="w-9 sm:w-10 h-11 sm:h-12 bg-[var(--color-obsidian-elevated)] hover:bg-emerald-500/20 active:scale-95 rounded-xl font-bold text-xs text-emerald-300 cursor-pointer border border-[var(--color-obsidian-border)] transition-colors"
                                    >
                                        +1
                                    </button>
                                    <button
                                        type="button"
                                        @click="ajustarPeso(2.5)"
                                        class="w-9 sm:w-10 h-11 sm:h-12 bg-[var(--color-obsidian-elevated)] hover:bg-emerald-500/20 active:scale-95 rounded-xl font-bold text-xs text-emerald-300 cursor-pointer border border-[var(--color-obsidian-border)] transition-colors"
                                    >
                                        +2.5
                                    </button>
                                    <button
                                        type="button"
                                        @click="ajustarPeso(5)"
                                        class="w-9 sm:w-10 h-11 sm:h-12 bg-[var(--color-obsidian-elevated)] hover:bg-emerald-500/20 active:scale-95 rounded-xl font-bold text-xs text-emerald-300 cursor-pointer border border-[var(--color-obsidian-border)] transition-colors"
                                    >
                                        +5
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Controles Glove Mode: REPETICIONES -->
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between">
                                <label class="text-[10px] font-black uppercase tracking-[0.16em] text-gray-300">
                                    REPETICIONES
                                </label>
                                <span class="text-[10px] text-emerald-300 font-bold tabular-nums">
                                    Objetivo: {{ store.currentEjercicio.reps_min }}–{{
                                        store.currentEjercicio.reps_max
                                    }}
                                </span>
                            </div>

                            <div class="flex items-center gap-1 sm:gap-1.5">
                                <!-- Decremento reps -->
                                <div class="grid grid-cols-2 gap-1 shrink-0">
                                    <button
                                        type="button"
                                        @click="ajustarReps(-2)"
                                        class="w-10 sm:w-12 h-11 sm:h-12 bg-[var(--color-obsidian-elevated)] hover:bg-rose-500/20 active:scale-95 rounded-xl font-bold text-xs sm:text-sm text-rose-300 cursor-pointer border border-[var(--color-obsidian-border)] transition-colors"
                                    >
                                        -2
                                    </button>
                                    <button
                                        type="button"
                                        @click="ajustarReps(-1)"
                                        class="w-10 sm:w-12 h-11 sm:h-12 bg-[var(--color-obsidian-elevated)] hover:bg-rose-500/20 active:scale-95 rounded-xl font-bold text-xs sm:text-sm text-rose-300 cursor-pointer border border-[var(--color-obsidian-border)] transition-colors"
                                    >
                                        -1
                                    </button>
                                </div>

                                <!-- Display grande de reps -->
                                <div
                                    class="flex-1 relative flex items-center justify-center bg-gradient-to-br from-[var(--color-obsidian-elevated)] to-[var(--color-obsidian-surface)] border-2 border-emerald-500/30 rounded-2xl h-11 sm:h-12 shadow-[0_0_20px_rgba(16,185,129,0.15)]"
                                >
                                    <input
                                        v-model.number="form.reps"
                                        type="number"
                                        inputmode="numeric"
                                        min="0"
                                        class="w-full bg-transparent text-center text-2xl sm:text-3xl font-black text-white outline-none tabular-nums"
                                        placeholder="0"
                                    />
                                    <span class="absolute right-2.5 sm:right-3 text-[11px] font-black text-emerald-300 uppercase tracking-wider"
                                        >reps</span
                                    >
                                </div>

                                <!-- Incremento reps -->
                                <div class="grid grid-cols-2 gap-1 shrink-0">
                                    <button
                                        type="button"
                                        @click="ajustarReps(1)"
                                        class="w-10 sm:w-12 h-11 sm:h-12 bg-[var(--color-obsidian-elevated)] hover:bg-emerald-500/20 active:scale-95 rounded-xl font-bold text-xs sm:text-sm text-emerald-300 cursor-pointer border border-[var(--color-obsidian-border)] transition-colors"
                                    >
                                        +1
                                    </button>
                                    <button
                                        type="button"
                                        @click="ajustarReps(2)"
                                        class="w-10 sm:w-12 h-11 sm:h-12 bg-[var(--color-obsidian-elevated)] hover:bg-emerald-500/20 active:scale-95 rounded-xl font-bold text-xs sm:text-sm text-emerald-300 cursor-pointer border border-[var(--color-obsidian-border)] transition-colors"
                                    >
                                        +2
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Esfuerzo (RIR / RPE) -->
                        <div class="bg-[var(--color-obsidian-surface)] rounded-xl p-2.5 sm:p-3 border border-[var(--color-obsidian-border)] space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-gray-300">Esfuerzo percibido:</span>
                                <div class="inline-flex rounded-lg bg-[var(--color-obsidian-elevated)] p-0.5 text-[10px] font-black border border-[var(--color-obsidian-border)]">
                                    <button
                                        type="button"
                                        @click="form.esfuerzo_tipo = 'rir'"
                                        :class="
                                            form.esfuerzo_tipo === 'rir'
                                                ? 'bg-emerald-500 text-white shadow-[0_0_12px_rgba(16,185,129,0.4)]'
                                                : 'text-gray-400'
                                        "
                                        class="px-2.5 py-1 rounded-md transition-colors cursor-pointer"
                                    >
                                        RIR
                                    </button>
                                    <button
                                        type="button"
                                        @click="form.esfuerzo_tipo = 'rpe'"
                                        :class="
                                            form.esfuerzo_tipo === 'rpe'
                                                ? 'bg-amber-500 text-white shadow-[0_0_12px_rgba(245,158,11,0.4)]'
                                                : 'text-gray-400'
                                        "
                                        class="px-2.5 py-1 rounded-md transition-colors cursor-pointer"
                                    >
                                        RPE
                                    </button>
                                </div>
                            </div>

                            <div class="flex items-center justify-between gap-1 overflow-x-auto py-0.5">
                                <button
                                    v-for="val in esfuerzoOptions"
                                    :key="val"
                                    type="button"
                                    @click="form.esfuerzo_valor = form.esfuerzo_valor === val ? null : val"
                                    :class="[
                                        'min-w-8 sm:min-w-9 h-10 px-1.5 sm:px-2 rounded-xl font-black text-xs transition-all cursor-pointer flex flex-col items-center justify-center gap-0.5',
                                        form.esfuerzo_valor === val
                                            ? form.esfuerzo_tipo === 'rir'
                                                ? 'bg-emerald-500 text-white shadow-[0_0_18px_rgba(16,185,129,0.5)] scale-105'
                                                : 'bg-amber-500 text-white shadow-[0_0_18px_rgba(245,158,11,0.5)] scale-105'
                                            : 'bg-[var(--color-obsidian-elevated)] text-gray-300 hover:bg-[var(--color-obsidian-overlay)] hover:text-white border border-[var(--color-obsidian-border)]',
                                    ]"
                                >
                                    <span>{{ val }}</span>
                                    <span class="text-[8px] uppercase opacity-80">
                                        {{ esfuerzoLabel(val) }}
                                    </span>
                                </button>
                            </div>
                        </div>

                        <!-- Botón Gigante: COMPLETAR SERIE / FINALIZAR (Kinetic Obsidian) -->
                        <button
                            v-if="!store.isSessionComplete"
                            type="button"
                            @click="completarSerie"
                            class="w-full py-3.5 sm:py-4 rounded-2xl bg-gradient-to-br from-[var(--color-violet-deep)] via-[var(--color-violet-primary)] to-[var(--color-violet-light)] hover:brightness-110 active:scale-[0.98] text-white text-sm sm:text-base font-black tracking-wider shadow-[0_12px_32px_var(--color-violet-glow)] flex items-center justify-center gap-2.5 transition-all cursor-pointer border border-white/10"
                        >
                            <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="3"
                                    d="M5 13l4 4L19 7"
                                />
                            </svg>
                            <span v-if="form.tipo_serie === 'calentamiento'">
                                COMPLETAR CALENTAMIENTO #{{ store.session.currentCalentamientoNumero || 1 }}
                            </span>
                            <span v-else>
                                COMPLETAR SERIE #{{ store.session.currentSerieNumero }}
                            </span>
                        </button>

                        <button
                            v-else
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

                        <!-- Botón Deshacer -->
                        <div v-if="store.canUndo" class="text-center pt-0.5">
                            <button
                                type="button"
                                @click="deshacer"
                                class="text-xs font-bold text-rose-300 hover:text-rose-200 underline underline-offset-4 cursor-pointer"
                            >
                                ↩ Deshacer última serie completada
                            </button>
                        </div>
                    </section>
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
const mediaCache = new Map();
let mediaFetchSeq = 0;

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

watch(
    () => store.currentEjercicio?.nombre,
    (nombre) => {
        gifHovered.value = true;
        loadEjercicioMedia(nombre);
    },
    { immediate: true },
);

// Tipos de serie disponibles
const tiposSerie = [
    { id: 'efectiva', label: 'Efectiva', activeClass: 'bg-emerald-500 text-white' },
    { id: 'calentamiento', label: 'Calentamiento', activeClass: 'bg-indigo-500 text-white' },
    { id: 'dropset', label: 'Drop Set', activeClass: 'bg-amber-500 text-white' },
    { id: 'al_fallo', label: 'Al Fallo', activeClass: 'bg-rose-500 text-white' },
];

const form = ref({
    peso: 0,
    reps: 0,
    tipo_serie: 'efectiva',
    esfuerzo_tipo: 'rir',
    esfuerzo_valor: null,
    nota_user: '',
});

// Datos de la última vez que el usuario hizo este ejercicio.
// null mientras carga, {} sin encontrado=true si no hay histórico.
const lastExerciseData = ref(null);
let lastExerciseFetchToken = 0;

// Setea true cuando ya pre-rellenamos el form con el último peso top,
// para no pisar ediciones del usuario si vuelve a cambiar el ejercicio.
let prefilledFromLast = false;

// Opciones dinámicas de esfuerzo (RIR 0..5, RPE 6..10)
const esfuerzoOptions = computed(() => {
    return form.value.esfuerzo_tipo === 'rir' ? [0, 1, 2, 3, 4, 5] : [6, 7, 8, 9, 10];
});

// Etiquetas cortas para cada valor de RIR/RPE (debajo del número)
const esfuerzoLabel = (val) => {
    const rir = ['Fallo', 'Máx', 'Óptimo', 'Medio', 'Fácil', 'Calent.'];
    const rpe = ['Fácil', 'Fácil+', 'Medio', 'Medio+', 'Máx'];
    if (form.value.esfuerzo_tipo === 'rir') {
        return rir[Math.min(val, 5)] || '';
    }
    return rpe[Math.min(Math.max(val - 6, 0), 4)] || '';
};

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

const ajustarPeso = (delta) => {
    const nuevo = Math.max(0, (Number(form.value.peso) || 0) + delta);
    form.value.peso = Math.round(nuevo * 2) / 2; // redondear a 0.5
};

const ajustarReps = (delta) => {
    form.value.reps = Math.max(0, (Number(form.value.reps) || 0) + delta);
};

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

// Mantener valores del set previo del ejercicio si no hay cargados.
// Ademas trae el último histórico del ejercicio desde el backend para
// mostrarle al usuario "última vez cargaste X" + sugerencia.
watch(
    () => store.currentEjercicio,
    async (ej) => {
        if (!ej) return;

        if (ej.sets && ej.sets.length > 0) {
            const last = ej.sets[ej.sets.length - 1];
            form.value.peso = last.peso;
            form.value.reps = last.reps;
            prefilledFromLast = true;
        } else {
            // Predeterminado según objetivo
            form.value.reps = Number(ej.reps_min) || 8;
        }

        // Buscar la última sesión en la que el usuario trabajó este ejercicio.
        lastExerciseData.value = null;
        const token = ++lastExerciseFetchToken;
        try {
            const { data } = await window.axios.get('/api/historial/ultimo', {
                params: { ejercicio: ej.nombre },
            });
            // Evitar race conditions si el usuario cambió de ejercicio.
            if (token !== lastExerciseFetchToken) return;
            lastExerciseData.value = data || null;

            // Si todavía no se hizo ningún set en este ejercicio y el backend
            // devolvió un peso top, lo usamos como valor inicial sugerido.
            if (
                data &&
                data.encontrado &&
                !prefilledFromLast &&
                Number(form.value.peso) === 0 &&
                data.peso_top
            ) {
                form.value.peso = Number(data.peso_top) || 0;
                if (!Number(form.value.reps) && data.reps_en_peso_top) {
                    form.value.reps = Number(data.reps_en_peso_top) || 0;
                }
            }
        } catch (err) {
            // Silencioso: si falla la red, el modal sigue funcionando sin la card.
            if (token === lastExerciseFetchToken) {
                lastExerciseData.value = null;
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
// Reglas conservadoras para principiantes / amateur:
//   - RIR >= 3  o  RPE <= 7   → margen → subir peso (+2.5kg)
//   - RIR 1..2  o  RPE 8..9   → zona óptima → mantener
//   - RIR 0     o  RPE 10     → al fallo → bajar (-2.5kg) o repetir
//   - sin esfuerzo registrado → mantener mismo peso
const recomendacion = computed(() => {
    const data = lastExerciseData.value;
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
});

const completarSerie = async () => {
    const ej = store.currentEjercicio;
    if (!ej) return;

    // El numero de serie que va al backend debe coincidir con el que muestra
    // la UI: si es calentamiento usamos su propio contador, si no, el de
    // series de trabajo.
    const isWarmup = form.value.tipo_serie === 'calentamiento';
    const currentSerieNum = isWarmup
        ? (store.session.currentCalentamientoNumero || 1)
        : store.session.currentSerieNumero;
    const pesoNum = Number(form.value.peso) || 0;
    const repsNum = Number(form.value.reps) || 0;

    // 1. Guardar en Pinia store
    store.recordSet({
        peso: pesoNum,
        reps: repsNum,
        tipo_serie: form.value.tipo_serie,
        esfuerzo_tipo: form.value.esfuerzo_tipo,
        esfuerzo_valor: form.value.esfuerzo_valor,
        nota_user: form.value.nota_user,
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
            reps_realizadas: repsNum,
            descanso_min: Number(ej.descanso_min || 1.5),
            peso: pesoNum,
            completado: true,
            tipo_serie: form.value.tipo_serie,
            sesion_uuid: store.session.id,
            esfuerzo_tipo: form.value.esfuerzo_tipo,
            esfuerzo_valor: form.value.esfuerzo_valor,
            nota_user: form.value.nota_user,
        });
    } catch (e) {
        console.warn('Error guardando serie en offline/API:', e);
    }

    // 4. Iniciar temporizador de descanso
    const descansoSegundos = Math.max(15, Math.round((Number(ej.descanso_min) || 1.5) * 60));
    restTimer.start(descansoSegundos, ej.nombre);
};

const deshacer = () => {
    const last = store.undoLastSet();
    if (last) {
        form.value.peso = last.peso;
        form.value.reps = last.reps;
        form.value.tipo_serie = last.tipo_serie;
        form.value.esfuerzo_tipo = last.esfuerzo_tipo || 'rir';
        form.value.esfuerzo_valor = last.esfuerzo_valor;
    }
};

const handleFinalizar = () => {
    emit('finish');
};

onMounted(() => {
    if (props.open) {
        wakeLock.requestWakeLock();
    }
});

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            wakeLock.requestWakeLock();
        } else {
            wakeLock.releaseWakeLock();
        }
    }
);

onUnmounted(() => {
    wakeLock.releaseWakeLock();
});
</script>
