<template>
    <div
        v-if="open"
        class="fixed inset-0 z-50 flex flex-col bg-bg text-text select-none overflow-hidden"
        role="dialog"
        aria-modal="true"
        aria-label="Modo Entrenamiento Activo"
    >
        <!-- Top Bar: Cronómetro, Rutina y Controles (Tokens Semánticos) -->
        <header
            class="px-3 py-2 sm:px-4 sm:py-2.5 bg-surface/95 border-b border-border flex items-center justify-between gap-2.5 backdrop-blur-xl shrink-0"
        >
            <div class="flex items-center gap-2 sm:gap-3 min-w-0">
                <button
                    type="button"
                    @click="$emit('minimize')"
                    class="w-8 h-8 sm:w-9 sm:h-9 rounded-full bg-surface-2 hover:bg-surface-3 text-text-2 hover:text-text transition-colors cursor-pointer border border-border flex items-center justify-center shrink-0"
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
                        class="text-[10px] font-black uppercase tracking-[0.16em] text-brand truncate flex items-center gap-1.5"
                    >
                        <span class="w-1.5 h-1.5 rounded-full bg-success animate-pulse"></span>
                        {{ store.session.dia }} · {{ store.session.rutina_nombre }}
                    </p>
                    <div class="flex items-center gap-2 mt-0.5">
                        <span class="text-xl sm:text-2xl font-mono font-black tracking-tight tabular-nums text-text">
                            {{ formattedTime }}
                        </span>
                        <span v-if="store.isPaused" class="px-2 py-0.5 rounded-full bg-warning/15 text-warning text-[10px] font-bold border border-warning/30">
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
                    class="p-2 sm:p-2.5 rounded-xl border border-border bg-surface-2 hover:bg-surface-3 text-text-2 transition-all cursor-pointer"
                    :title="store.isPaused ? 'Reanudar cronómetro' : 'Pausar cronómetro'"
                    :aria-label="store.isPaused ? 'Reanudar' : 'Pausar'"
                >
                    <svg
                        v-if="!store.isPaused"
                        class="w-4 h-4 sm:w-5 sm:h-5 text-warning"
                        fill="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z" />
                    </svg>
                    <svg
                        v-else
                        class="w-4 h-4 sm:w-5 sm:h-5 text-success"
                        fill="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path d="M8 5v14l11-7z" />
                    </svg>
                </button>

                <!-- Menú ⋮ de Acciones secundarias -->
                <div class="relative" ref="headerMenuRef">
                    <button
                        type="button"
                        @click="headerMenuOpen = !headerMenuOpen"
                        class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl border border-border bg-surface-2 hover:bg-surface-3 text-text flex items-center justify-center cursor-pointer font-bold text-lg"
                        aria-label="Más opciones"
                    >
                        <span>⋮</span>
                    </button>

                    <div
                        v-show="headerMenuOpen"
                        class="absolute right-0 mt-1.5 w-44 rounded-xl bg-surface border border-border shadow-2xl py-1.5 z-50 text-xs"
                    >
                        <button
                            type="button"
                            @click="handleFinalizar(); headerMenuOpen = false;"
                            class="w-full px-3 py-2 text-left text-text hover:bg-surface-2 font-semibold transition-colors"
                        >Finalizar</button>
                        <button
                            type="button"
                            @click="descartarSesion"
                            class="w-full px-3 py-2 text-left text-danger hover:bg-danger/10 flex items-center gap-2 font-semibold transition-colors"
                        >
                            <span>🗑</span>
                            <span>Descartar sesión</span>
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <!-- Barra de Progreso Global -->
        <div
            class="w-full bg-surface-2 border-b border-border px-3 py-1.5 sm:px-4 sm:py-2 shrink-0 flex items-center justify-between gap-3 text-xs"
        >
            <span class="text-text-2 font-semibold whitespace-nowrap">
                Progreso:
                <strong class="text-success tabular-nums">{{ store.totalSeriesCompletadas }}</strong>
                /
                <span class="text-text-3">{{ store.totalSeriesObjetivo }}</span>
                series
            </span>
            <div class="flex-1 h-1.5 sm:h-2 bg-surface-3 rounded-full overflow-hidden mx-1 border border-border">
                <div
                    class="h-full bg-gradient-to-r from-brand to-success transition-all duration-300"
                    :style="{ width: `${store.progresoPorcentaje}%` }"
                ></div>
            </div>
            <span class="font-black text-brand tabular-nums whitespace-nowrap">
                {{ store.progresoPorcentaje }}%
            </span>
        </div>

        <!-- Contenedor Principal Adaptable (Mobile + Web Responsive 2 Columnas) -->
        <main class="flex-1 overflow-y-auto px-2.5 sm:px-4 py-2.5 sm:py-3.5 pb-28 md:pb-6 overscroll-contain">
            <div
                v-if="store.currentEjercicio"
                class="max-w-6xl xl:max-w-7xl mx-auto w-full flex flex-col md:grid md:grid-cols-12 gap-2.5 sm:gap-3 lg:gap-4 items-start"
            >
                <!-- COLUMNA IZQUIERDA (Desktop: col-span-5) -->
                <div class="w-full md:col-span-5 lg:col-span-5 flex flex-col gap-2.5 sm:gap-3">
                    <!-- CARD HERO UNIFICADA DEL EJERCICIO (Frente B2 & B3: Focus Mode) -->
                    <section
                        class="rounded-2xl border border-border bg-surface p-3 sm:p-4 shadow-lg relative space-y-2.5"
                        data-testid="exercise-media"
                    >
                        <!-- Metadata invisible para accesibilidad y tests -->
                        <span class="sr-only" data-testid="ejercicio-progress-pill">
                            EJERCICIO {{ store.unidadProgreso.actual }} DE {{ store.unidadProgreso.total }}
                        </span>
                        <span class="sr-only">
                            {{ store.currentEjercicio.series_objetivo }} series
                        </span>

                        <!-- Fila Superior: Eyebrow + Badge Serie + Navegación entre ejercicios -->
                        <div class="flex items-center justify-between gap-2 flex-wrap">
                            <div class="flex items-center gap-2 min-w-0">
                                <p class="text-[10px] font-black uppercase tracking-wider text-text-3 truncate">
                                    {{ (store.currentEjercicio?.grupo_muscular || 'FUERZA').toUpperCase() }} · {{ store.session.dia.toUpperCase() }}
                                </p>
                                <span
                                    class="px-2 py-0.5 rounded-full bg-brand/15 border border-brand/30 text-brand text-[10px] font-bold uppercase tracking-wider shrink-0"
                                >
                                    <template v-if="currentEsBloque">
                                        BLOQUE DE CALENTAMIENTO
                                    </template>
                                    <template v-else>
                                        SERIE #{{ activeSerieNumero }} DE {{ store.currentEjercicio?.series_objetivo || 1 }}
                                    </template>
                                </span>
                            </div>

                            <!-- Controles de Navegación de Ejercicio (Anterior / Contador / Siguiente / Ojo) -->
                            <div class="flex items-center gap-1 bg-surface-2 border border-border rounded-xl p-0.5 shadow-sm shrink-0">
                                <button
                                    type="button"
                                    @click="store.prevEjercicio"
                                    :disabled="store.session.currentEjercicioIndex === 0"
                                    class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-surface-3 hover:bg-border text-text-2 hover:text-text disabled:opacity-20 disabled:cursor-not-allowed flex items-center justify-center transition-all cursor-pointer"
                                    title="Ejercicio anterior"
                                    aria-label="Ejercicio anterior"
                                >
                                    <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                                    </svg>
                                </button>

                                <span class="px-1.5 text-[11px] sm:text-xs font-black text-text tabular-nums tracking-wide">
                                    {{ store.unidadProgreso.actual }}/{{ store.unidadProgreso.total }}
                                </span>

                                <button
                                    type="button"
                                    @click="store.nextEjercicio"
                                    :disabled="store.session.currentEjercicioIndex >= store.session.ejercicios.length - 1"
                                    class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-brand/20 hover:bg-brand/30 border border-brand/30 text-brand disabled:opacity-20 disabled:cursor-not-allowed flex items-center justify-center transition-all cursor-pointer"
                                    title="Siguiente ejercicio"
                                    aria-label="Siguiente ejercicio"
                                >
                                    <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                    </svg>
                                </button>

                                <button
                                    type="button"
                                    @click="vistaVisible = !vistaVisible"
                                    :aria-pressed="!vistaVisible"
                                    :aria-label="vistaVisible ? 'Ocultar vista del ejercicio' : 'Mostrar vista del ejercicio'"
                                    :title="vistaVisible ? 'Ocultar vista' : 'Mostrar vista'"
                                    data-testid="toggle-exercise-view"
                                    class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg flex items-center justify-center text-text-3 hover:text-text hover:bg-surface-3 transition-colors cursor-pointer"
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

                        <!-- Fila del Nombre (Bloque 1: 21px, line-clamp-2, jerarquizado) -->
                        <div v-if="!superserieOrder">
                            <h2
                                class="text-xl sm:text-2xl font-black text-text leading-tight line-clamp-2"
                                :title="mediaEjercicio?.nombre || store.currentEjercicio.nombre"
                            >
                                {{ mediaEjercicio?.nombre || store.currentEjercicio.nombre }}
                            </h2>
                        </div>
                        <div v-else class="min-w-0">
                            <div class="flex items-center gap-1.5 min-w-0">
                                <span class="px-1.5 py-0.5 rounded-md bg-warning/20 text-warning text-[9px] font-black uppercase tracking-wider shrink-0 border border-warning/40">
                                    ⚡ SUPERSERIE
                                </span>
                                <h2
                                    class="text-base sm:text-lg font-black text-text leading-tight line-clamp-2"
                                    :title="`${superserieOrder.first.ejercicio.nombre} + ${superserieOrder.second.ejercicio.nombre}`"
                                >
                                    {{ superserieOrder.first.ejercicio.nombre }} + {{ superserieOrder.second.ejercicio.nombre }}
                                </h2>
                            </div>
                        </div>

                        <!-- Fila Media (Thumbnail 64px) + Historial (1 línea compacta ~28px) -->
                        <div class="flex items-center gap-3">
                            <!-- Thumbnail (Bloque 4: 64px, placeholder intencional, zoom tap) -->
                            <div
                                class="relative w-16 h-16 sm:w-20 sm:h-20 rounded-xl bg-surface-2 border border-border p-1 flex items-center justify-center shrink-0 overflow-hidden cursor-pointer group shadow-sm"
                                @click="openMediaZoomOrToggleGif"
                                :title="gifHovered ? 'Animación activa (tocá para pausar / zoom)' : 'Tocá para animar / zoom'"
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
                                    class="flex flex-col items-center justify-center text-text-3 text-[10px] text-center p-1"
                                >
                                    <span class="text-sm">👁️</span>
                                    <span class="text-[9px]">Oculto</span>
                                </div>
                                <!-- Placeholder temático intencional cuando no hay imagen cargada -->
                                <div
                                    v-else
                                    class="flex flex-col items-center justify-center text-text-3 p-1"
                                >
                                    <span class="text-lg">🏋️</span>
                                    <span class="text-[8px] font-black text-brand uppercase tracking-tighter truncate max-w-[50px]">
                                        {{ (mediaEjercicio?.nombre || store.currentEjercicio?.nombre || '').slice(0, 4) }}
                                    </span>
                                </div>

                                <!-- Badge zoom ⤢ -->
                                <span
                                    v-if="vistaVisible && (ejercicioMedia?.gif_url || ejercicioMedia?.image_url)"
                                    class="absolute bottom-1 right-1 px-1 py-0.2 rounded bg-black/60 text-white text-[8px] font-bold pointer-events-none"
                                >⤢</span>

                                <!-- Flechas para alternar media en superset -->
                                <button
                                    v-if="superseriePartner && mediaEjercicioIndex !== store.session.currentEjercicioIndex"
                                    type="button"
                                    @click.stop="mediaEjercicioIndex = store.session.currentEjercicioIndex"
                                    data-testid="media-prev"
                                    :title="`Ver ${store.currentEjercicio.nombre}`"
                                    aria-label="Ver imagen del ejercicio actual"
                                    class="absolute left-1 top-1/2 -translate-y-1/2 z-10 p-1 rounded-full bg-brand text-white shadow transition-all cursor-pointer"
                                >
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
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
                                    class="absolute right-1 top-1/2 -translate-y-1/2 z-10 p-1 rounded-full bg-brand text-white shadow transition-all cursor-pointer"
                                >
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Bloque 2: Historial (1 línea compacta ~28px en vez de múltiples cajas) -->
                            <div class="flex-1 min-w-0 flex flex-col justify-center">
                                <div v-if="!superserieOrder">
                                    <div
                                        class="h-8 px-2.5 rounded-xl bg-surface-2 border border-border flex items-center justify-between text-xs tabular-nums"
                                        data-testid="last-exercise-card"
                                    >
                                        <template v-if="tieneHistorial">
                                            <span class="text-[9px] font-black uppercase text-text-3 tracking-wider mr-2 shrink-0">Historial</span>
                                            <div class="flex items-center gap-1.5 overflow-hidden text-[11px] sm:text-xs">
                                                <span data-testid="last-exercise-min" class="flex items-center gap-0.5 text-text-2 shrink-0">
                                                    <span class="font-bold">{{ formatPeso(lastExerciseData?.peso_min ?? lastExerciseData?.peso_top ?? 0) }} kg</span>
                                                    <span class="text-[9px] text-text-3">({{ (lastExerciseData?.reps_en_peso_min ?? lastExerciseData?.reps_en_peso_top) ?? 0 }} reps)</span>
                                                </span>
                                                <span
                                                    v-if="(lastExerciseData?.peso_max ?? lastExerciseData?.peso_top) !== (lastExerciseData?.peso_min ?? lastExerciseData?.peso_top)"
                                                    class="text-text-3 text-[10px]"
                                                >→</span>
                                                <span data-testid="last-exercise-max" class="flex items-center gap-1 font-black text-text shrink-0">
                                                    <span>{{ formatPeso(lastExerciseData?.peso_max ?? lastExerciseData?.peso_top ?? 0) }} kg</span>
                                                    <span class="text-[9px] text-text-3 font-normal">({{ (lastExerciseData?.reps_en_peso_max ?? lastExerciseData?.reps_en_peso_top) ?? 0 }} reps)</span>
                                                    <span v-if="lastExerciseData?.ultimo_esfuerzo" class="px-1 py-0.2 rounded bg-surface-3 text-text text-[8px] font-black border border-border">
                                                        {{ lastExerciseData.ultimo_esfuerzo.tipo.toUpperCase() }} {{ lastExerciseData.ultimo_esfuerzo.valor }}
                                                    </span>
                                                </span>
                                            </div>
                                        </template>
                                        <template v-else>
                                            <span class="text-xs text-text-3 italic">Primera vez con este ejercicio</span>
                                        </template>
                                    </div>
                                </div>
                                <div v-else>
                                    <!-- Superserie compacta: una fila por ejercicio -->
                                    <div
                                        class="space-y-1"
                                        data-testid="superset-dual-history"
                                    >
                                        <div
                                            class="h-7 px-2 rounded-lg bg-surface-2 border border-border flex items-center justify-between text-[11px] tabular-nums"
                                            data-testid="superset-history-card-1"
                                        >
                                            <span class="text-[9px] font-black text-brand uppercase truncate max-w-[120px]">
                                                1. {{ superserieOrder.first.ejercicio.nombre }}
                                            </span>
                                            <div class="flex items-center gap-1 text-[10px] text-text-2">
                                                <span>{{ formatPeso(lastExerciseDataFirst?.peso_min ?? lastExerciseDataFirst?.peso_top ?? 0) }} kg ({{ (lastExerciseDataFirst?.reps_en_peso_min ?? lastExerciseDataFirst?.reps_en_peso_top) ?? 0 }} reps)</span>
                                                <span>→</span>
                                                <span class="font-bold text-text">{{ formatPeso(lastExerciseDataFirst?.peso_max ?? lastExerciseDataFirst?.peso_top ?? 0) }} kg ({{ (lastExerciseDataFirst?.reps_en_peso_max ?? lastExerciseDataFirst?.reps_en_peso_top) ?? 0 }} reps)</span>
                                            </div>
                                        </div>
                                        <div
                                            class="h-7 px-2 rounded-lg bg-surface-2 border border-border flex items-center justify-between text-[11px] tabular-nums"
                                            data-testid="superset-history-card-2"
                                        >
                                            <span class="text-[9px] font-black text-brand uppercase truncate max-w-[120px]">
                                                2. {{ superserieOrder.second.ejercicio.nombre }}
                                            </span>
                                            <div class="flex items-center gap-1 text-[10px] text-text-2">
                                                <span>{{ formatPeso(lastExerciseDataSecond?.peso_min ?? lastExerciseDataSecond?.peso_top ?? 0) }} kg ({{ (lastExerciseDataSecond?.reps_en_peso_min ?? lastExerciseDataSecond?.reps_en_peso_top) ?? 0 }} reps)</span>
                                                <span>→</span>
                                                <span class="font-bold text-text">{{ formatPeso(lastExerciseDataSecond?.peso_max ?? lastExerciseDataSecond?.peso_top ?? 0) }} kg ({{ (lastExerciseDataSecond?.reps_en_peso_max ?? lastExerciseDataSecond?.reps_en_peso_top) ?? 0 }} reps)</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Zoom Fullscreen de Demostración Visual -->
                        <div
                            v-if="showMediaZoom"
                            class="fixed inset-0 z-50 bg-black/90 flex flex-col items-center justify-center p-4 backdrop-blur-md"
                            @click="showMediaZoom = false"
                        >
                            <div class="relative max-w-lg w-full max-h-[85vh] flex flex-col items-center" @click.stop>
                                <button
                                    type="button"
                                    @click="showMediaZoom = false"
                                    class="absolute top-2 right-2 p-2 rounded-full bg-surface-2 text-text border border-border cursor-pointer shadow-lg z-10"
                                    aria-label="Cerrar vista ampliada"
                                >
                                    ✕
                                </button>
                                <img
                                    v-if="ejercicioMedia?.gif_url || ejercicioMedia?.image_url"
                                    :src="ejercicioMedia.gif_url || ejercicioMedia.image_url"
                                    :alt="`Demostración ampliada de ${mediaEjercicio?.nombre}`"
                                    class="max-h-[75vh] w-auto object-contain rounded-2xl select-none shadow-2xl"
                                />
                                <p class="text-sm font-bold text-text mt-3 text-center">
                                    {{ mediaEjercicio?.nombre || store.currentEjercicio.nombre }}
                                </p>
                            </div>
                        </div>

                        <!-- Sugerencia -->
                        <div
                            v-if="!superserieOrder && recomendacion"
                            class="flex items-start gap-1.5 text-[10px] sm:text-[11px] text-success leading-tight font-medium"
                        >
                            <svg class="w-3.5 h-3.5 text-success shrink-0 mt-0.5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1.414 1.414 0 001.414 0l4-4z" clip-rule="evenodd" />
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
                            class="flex flex-col gap-1 text-[10px] sm:text-[11px] text-success leading-tight font-medium"
                        >
                            <div v-if="recomendacionFirst" class="flex items-start gap-1.5">
                                <svg class="w-3.5 h-3.5 text-success shrink-0 mt-0.5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1.414 1.414 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                                <span>
                                    <strong class="font-bold">Sugerencia {{ superserieOrder.first.ejercicio.nombre }}:</strong>
                                    {{ recomendacionFirst.mensaje }}
                                </span>
                            </div>
                            <div v-if="recomendacionSecond" class="flex items-start gap-1.5">
                                <svg class="w-3.5 h-3.5 text-success shrink-0 mt-0.5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1.414 1.414 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                                <span>
                                    <strong class="font-bold">Sugerencia {{ superserieOrder.second.ejercicio.nombre }}:</strong>
                                    {{ recomendacionSecond.mensaje }}
                                </span>
                            </div>
                        </div>
                    </section>

                    <!-- Panel destacado: Plan de series y esfuerzo / Prescripción (visible en pantallas grandes o complementario) -->
                    <div
                        v-if="store.currentEjercicio.notas || store.currentEjercicio.prescripcion"
                        class="hidden md:block p-2.5 sm:p-3 rounded-xl bg-gradient-to-br from-violet-950/40 via-[var(--color-obsidian-elevated)] to-[var(--color-obsidian-surface)] border border-violet-500/30 text-left space-y-2 shadow-inner"
                        data-testid="ejercicio-prescripcion-box"
                    >
                        <div class="flex items-center justify-between gap-2 border-b border-violet-500/20 pb-1.5">
                            <span class="flex items-center gap-1.5 text-[11px] font-black uppercase tracking-wider text-violet-300">
                                <span>📋</span>
                                <span>Plan de series y esfuerzo</span>
                            </span>
                        </div>

                        <!-- Prescripción estructurada: bloques, rest-pause, progresión y consejo -->
                        <PrescripcionCard
                            :ejercicio="store.currentEjercicio"
                            :serie-actual="activeSerieNumero"
                        />
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
                                            Calentamiento {{ numeroSerieParaDisplay(s.series_numero) }}
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
                                                    Calentamiento {{ numeroSerieParaDisplay(s.series_numero) }}
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

                    <!-- CTA Bottom Container (Sticky en mobile con safe-area) -->
                    <div
                        v-if="store.isSessionComplete || (store.canUndo && !store.isSessionComplete)"
                        class="sticky bottom-0 z-30 -mx-2.5 sm:-mx-4 px-2.5 sm:px-4 py-2.5 bg-gradient-to-t from-bg via-bg/95 to-transparent pb-[max(0.75rem,env(safe-area-inset-bottom,0px))]"
                    >
                        <!-- CTA Finalizar Sesión (solo cuando todo está completo) -->
                        <button
                            v-if="store.isSessionComplete"
                            type="button"
                            @click="handleFinalizar"
                            data-testid="btn-finalizar-sesion"
                            class="w-full h-14 sm:h-16 rounded-2xl bg-gradient-to-r from-success to-emerald-500 hover:brightness-110 active:scale-[0.98] text-white text-base sm:text-lg font-black tracking-wider shadow-[0_8px_30px_rgba(4,120,87,0.35)] flex items-center justify-center gap-3 transition-all cursor-pointer border border-white/20"
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
                        <div v-if="store.canUndo && !store.isSessionComplete" class="text-center pt-1">
                            <button
                                type="button"
                                @click="deshacer"
                                class="text-xs font-bold text-danger hover:underline cursor-pointer"
                            >
                                ↩ Deshacer última serie completada
                            </button>
                        </div>
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
import { useToast } from '@/composables/useToast';
import { useWakeLock } from '@/composables/useWakeLock';
import { useClickOutside } from '@/composables/useClickOutside';
import { numeroCalentamientoPersisted, numeroSerieParaDisplay } from '@/utils/seriesNumero';
import { hoyLocal } from '@/utils/fecha';
import SetConfigCard from './SetConfigCard.vue';
import PrescripcionCard from './PrescripcionCard.vue';
import {
    prescripcionDe,
    targetParaSerie,
    resumenPlan,
} from '@/utils/rutinaPrescripcion';

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
const toast = useToast();

// Menú secundario kebab ⋮ en header
const headerMenuOpen = ref(false);
const headerMenuRef = ref(null);
useClickOutside(headerMenuRef, () => {
    headerMenuOpen.value = false;
}, headerMenuOpen);

const descartarSesion = () => {
    headerMenuOpen.value = false;
    if (confirm('¿Estás seguro de que querés descartar esta sesión? Se perderán las series no guardadas.')) {
        store.discard();
        emit('discard');
        emit('minimize');
    }
};

// === Media del ejercicio actual (image_url + gif_url) ===
// Se carga via /api/ejercicios/media cuando cambia el ejercicio activo.
// Cache simple en `mediaCache` para no re-peir el mismo nombre.
const ejercicioMedia = ref(null);
const gifHovered = ref(true);
const showMediaZoom = ref(false);

const openMediaZoomOrToggleGif = () => {
    if (ejercicioMedia.value?.gif_url || ejercicioMedia.value?.image_url) {
        showMediaZoom.value = true;
    } else {
        gifHovered.value = !gifHovered.value;
    }
};

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
    } catch {
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

const tieneHistorial = computed(() => {
    const d = lastExerciseData.value;
    return Boolean(d && d.encontrado && (d.peso_min != null || d.peso_max != null || d.peso_top != null));
});

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
            } catch {
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
            } catch {
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
            ? numeroCalentamientoPersisted(store.session.currentCalentamientoNumero || 1)
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
        const resultado = await offline.recordSet({
            fecha: hoyLocal(),
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

        // El server la rechazó de forma permanente (validación/auth): no quedó
        // encolada en ningún lado, así que hay que avisarle al usuario.
        if (resultado?.status === 'rejected') {
            toast.error(`No se pudo guardar la serie: ${resultado.error}`);
        }
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

// Prescripción del ejercicio activo: prefiero el JSON estructurado que
// devuelve la API (`prescripcion`) y caigo al parseo de `notas` para las
// rutinas viejas. Ver utils/rutinaPrescripcion.js.
const prescripcionActiva = computed(() => prescripcionDe(store.currentEjercicio));

const activeSetsEfectivos = computed(() => {
    return (store.currentEjercicio?.sets || []).filter((s) => s.tipo_serie !== 'calentamiento').length;
});

const activeSerieNumero = computed(() => {
    return activeSetsEfectivos.value + 1;
});

// Bloque sin series efectivas (ej. movilidad / calentamiento del día).
const currentEsBloque = computed(() => {
    return Number(store.currentEjercicio?.series_objetivo || 0) === 0;
});

const rirBlocksHero = computed(() => prescripcionActiva.value.bloques);

const currentTargetEsfuerzoHero = computed(() => {
    return targetParaSerie(rirBlocksHero.value, activeSerieNumero.value);
});

const planSummaryHero = computed(() => {
    if (!rirBlocksHero.value || rirBlocksHero.value.length === 0) return null;
    return resumenPlan(rirBlocksHero.value);
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
