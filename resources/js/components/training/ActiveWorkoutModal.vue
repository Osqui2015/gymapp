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
                                <span
                                    class="obs-pill obs-pill-violet text-[10px]"
                                    data-testid="ejercicio-progress-pill"
                                >
                                    EJERCICIO {{ store.unidadProgreso.actual }} DE
                                    {{ store.unidadProgreso.total }}
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

                    <!-- Bloque "Superserie" en la columna izquierda: muestra los
                         DOS ejercicios del par en el ORDEN correcto (primero →
                         segundo), adaptando la etiqueta "Ahora / Después" según
                         si estás en el primero o en el segundo del par. -->
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
                            <!-- PRIMERO del par (el de menor índice) -->
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

                            <!-- SEGUNDO del par (el de mayor índice) -->
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
                                    class="text-xs sm:text-sm font-bold truncate"
                                    :class="
                                        superserieOrder.currentRole === 'second'
                                            ? 'text-white'
                                            : 'text-white'
                                    "
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

                    <!-- Demostración Visual / GIF Animado (data-testid="exercise-media") -->
                    <section
                        v-if="ejercicioMedia && (ejercicioMedia.gif_url || ejercicioMedia.image_url)"
                        class="obs-card p-3 relative flex flex-col items-center justify-center overflow-hidden"
                        data-testid="exercise-media"
                    >
                        <div class="w-full flex items-center justify-between gap-2 mb-2">
                            <span class="text-[10px] font-black uppercase tracking-wider text-violet-300 flex items-center gap-1.5 min-w-0">
                                <span class="w-1.5 h-1.5 rounded-full bg-violet-400 shrink-0"></span>
                                <span class="truncate">
                                    Vista:&nbsp;
                                    <span class="text-white">{{ mediaEjercicio?.nombre }}</span>
                                </span>
                            </span>
                            <div class="flex items-center gap-1.5">
                                <!-- Ojito: ocultar / mostrar toda la vista del ejercicio -->
                                <button
                                    type="button"
                                    @click="vistaVisible = !vistaVisible"
                                    :aria-pressed="!vistaVisible"
                                    :aria-label="vistaVisible ? 'Ocultar vista del ejercicio' : 'Mostrar vista del ejercicio'"
                                    :title="vistaVisible ? 'Ocultar vista' : 'Mostrar vista'"
                                    data-testid="toggle-exercise-view"
                                    class="p-1 rounded-lg bg-[var(--color-obsidian-elevated)] border border-[var(--color-obsidian-border)] text-gray-300 hover:text-white transition-colors cursor-pointer"
                                >
                                    <svg
                                        v-if="vistaVisible"
                                        class="w-3.5 h-3.5"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                        stroke-width="2"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7zM15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                        />
                                    </svg>
                                    <svg
                                        v-else
                                        class="w-3.5 h-3.5"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                        stroke-width="2"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.575-2.706M6.223 6.223A9.956 9.956 0 0112 5c4.478 0 8.268 2.943 9.542 7a9.97 9.97 0 01-1.272 2.61M9.88 9.88a3 3 0 104.243 4.243M3 3l18 18"
                                        />
                                    </svg>
                                </button>
                                <button
                                    v-if="ejercicioMedia.gif_url"
                                    type="button"
                                    @click="gifHovered = !gifHovered"
                                    class="text-[10px] font-bold px-2 py-0.5 rounded-lg bg-[var(--color-obsidian-elevated)] border border-[var(--color-obsidian-border)] text-gray-300 hover:text-white transition-colors cursor-pointer"
                                >
                                    {{ gifHovered ? '⏸ Pausar animación' : '▶ Ver animación' }}
                                </button>
                            </div>
                        </div>

                        <!-- Contenedor con flechas para alternar entre el ejercicio
                             actual y el compañero de superset (si lo hay). -->
                        <div
                            v-if="vistaVisible"
                            class="relative w-full"
                        >
                            <!-- Flecha izquierda (solo si hay superset y NO estamos mirando al current) -->
                            <button
                                v-if="superseriePartner && mediaEjercicioIndex !== store.session.currentEjercicioIndex"
                                type="button"
                                @click="mediaEjercicioIndex = store.session.currentEjercicioIndex"
                                data-testid="media-prev"
                                :title="`Ver ${store.currentEjercicio.nombre}`"
                                aria-label="Ver imagen del ejercicio actual"
                                class="absolute left-1 top-1/2 -translate-y-1/2 z-10 p-1.5 rounded-full bg-violet-600/80 hover:bg-violet-500 text-white shadow-lg transition-all cursor-pointer"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                                </svg>
                            </button>

                            <!-- Flecha derecha (solo si hay superset y estamos mirando al current) -->
                            <button
                                v-if="superseriePartner && mediaEjercicioIndex === store.session.currentEjercicioIndex"
                                type="button"
                                @click="mediaEjercicioIndex = superseriePartner.index"
                                data-testid="media-next"
                                :title="`Ver ${superseriePartner.ejercicio.nombre}`"
                                aria-label="Ver imagen del compañero de superset"
                                class="absolute right-1 top-1/2 -translate-y-1/2 z-10 p-1.5 rounded-full bg-violet-600/80 hover:bg-violet-500 text-white shadow-lg transition-all cursor-pointer"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>

                            <div
                                class="relative w-full h-40 sm:h-44 md:h-48 lg:h-52 rounded-xl overflow-hidden bg-[var(--color-obsidian-surface)] border border-[var(--color-obsidian-border)] flex items-center justify-center cursor-pointer group"
                                @click="gifHovered = !gifHovered"
                                @mouseenter="gifHovered = true"
                            >
                                <img
                                    :key="mediaEjercicio?.nombre"
                                    :src="gifHovered && ejercicioMedia.gif_url ? ejercicioMedia.gif_url : (ejercicioMedia.image_url || ejercicioMedia.gif_url)"
                                    :alt="`Demostración de ${mediaEjercicio?.nombre}`"
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
                                <!-- Si estamos en el primero del par, hay que
                                     decir "hacé X y seguí con Y". Si ya
                                     estamos en el segundo, el orden ya pasó
                                     y hay que reflejarlo. -->
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
                    <SetConfigCard
                        v-if="!superseriePartner"
                        :ejercicio="store.currentEjercicio"
                        :ejercicio-index="store.session.currentEjercicioIndex"
                        @complete="onSetComplete"
                    />

                    <!-- Cuando ES superset: 2 cards lado a lado (desktop) / stacked (mobile).
                         Las cards se renderizan en el ORDEN del par (primero →
                         segundo), no del current. Así el usuario ve siempre
                         la misma disposición: "izquierda = primero, derecha =
                         segundo", sin importar en cuál de los dos esté parado. -->
                    <div
                        v-else-if="superserieOrder"
                        class="grid grid-cols-1 lg:grid-cols-2 gap-3"
                        data-testid="superset-grid"
                    >
                        <SetConfigCard
                            :ejercicio="superserieOrder.first.ejercicio"
                            :ejercicio-index="superserieOrder.first.index"
                            :show-exercise-name="true"
                            @complete="onSetComplete"
                        />
                        <SetConfigCard
                            :ejercicio="superserieOrder.second.ejercicio"
                            :ejercicio-index="superserieOrder.second.index"
                            :show-exercise-name="true"
                            @complete="onSetComplete"
                        />
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
let lastExerciseFetchToken = 0;

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
 * Trae el "última vez" del ejercicio actual para la card de la columna
 * izquierda. El SetConfigCard maneja su propio form / prefill localmente.
 */
watch(
    () => store.currentEjercicio,
    async (ej) => {
        if (!ej) return;
        lastExerciseData.value = null;
        const token = ++lastExerciseFetchToken;
        try {
            const { data } = await window.axios.get('/api/historial/ultimo', {
                params: { ejercicio: ej.nombre },
            });
            if (token !== lastExerciseFetchToken) return;
            lastExerciseData.value = data || null;
        } catch (err) {
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

/**
 * Handler que recibe el evento `complete` del SetConfigCard (sea el del
 * ejercicio principal o el del compañero de superset). Persiste la serie
 * para el ejercicioIndex correcto y dispara el rest timer del ejercicio
 * que se acaba de registrar.
 */
const onSetComplete = async (payload) => {
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

    // 4. Iniciar temporizador de descanso del ejercicio que se acaba de
    // registrar. En superseries, esto es subóptimo (el descanso debería
    // empezar DESPUÉS de los dos ejercicios del round), pero mantiene
    // compatibilidad con el comportamiento previo. Si querés que el timer
    // arranque solo al completar el segundo del par, decime y lo cambiamos.
    const descansoSegundos = Math.max(15, Math.round((Number(ej.descanso_min) || 1.5) * 60));
    restTimer.start(descansoSegundos, ej.nombre);
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
