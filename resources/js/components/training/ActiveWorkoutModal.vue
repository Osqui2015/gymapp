<template>
    <div
        v-if="open"
        class="fixed inset-0 z-[70] flex flex-col bg-bg text-text select-none overflow-hidden"
        role="dialog"
        aria-modal="true"
        aria-label="Modo Entrenamiento Activo"
    >
        <!-- ============================ CHROME ============================ -->
        <header class="shrink-0">
            <div class="mx-auto w-full max-w-lg px-4 pt-3 pb-1.5 flex items-center gap-2">
                <!-- Volver / minimizar -->
                <button
                    type="button"
                    @click="$emit('minimize')"
                    class="w-10 h-10 shrink-0 rounded-full bg-surface border border-border text-text-2 hover:text-text transition-colors cursor-pointer flex items-center justify-center"
                    aria-label="Minimizar sesión"
                    title="Volver / Minimizar (continúa en segundo plano)"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>

                <!-- Cronómetro + estado -->
                <div class="flex-1 flex items-center justify-center gap-2 min-w-0">
                    <span
                        class="w-1.5 h-1.5 rounded-full shrink-0"
                        :class="store.isPaused ? 'bg-warning' : 'bg-danger animate-pulse'"
                    ></span>
                    <span class="text-[17px] font-black tabular-nums tracking-tight text-text">
                        {{ formattedTime }}
                    </span>
                    <span
                        v-if="store.isPaused"
                        class="text-[9px] font-black uppercase tracking-wider text-warning"
                    >pausa</span>
                </div>

                <!-- Pausa / reanudar -->
                <button
                    type="button"
                    @click="togglePause"
                    class="w-10 h-10 shrink-0 rounded-full bg-surface border border-border text-text-2 hover:text-text transition-colors cursor-pointer flex items-center justify-center"
                    :title="store.isPaused ? 'Reanudar cronómetro' : 'Pausar cronómetro'"
                    :aria-label="store.isPaused ? 'Reanudar' : 'Pausar'"
                >
                    <svg v-if="!store.isPaused" class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
                        <rect x="6" y="5" width="4" height="14" rx="1.2" />
                        <rect x="14" y="5" width="4" height="14" rx="1.2" />
                    </svg>
                    <svg v-else class="w-4 h-4 text-success" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M8 5v14l11-7z" />
                    </svg>
                </button>

                <!-- Menú de acciones secundarias -->
                <div class="relative shrink-0" ref="headerMenuRef">
                    <button
                        type="button"
                        @click="headerMenuOpen = !headerMenuOpen"
                        class="w-10 h-10 rounded-full bg-surface border border-border text-text hover:bg-surface-2 transition-colors cursor-pointer flex items-center justify-center"
                        aria-label="Más opciones"
                    >
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                            <circle cx="12" cy="5" r="1.8" />
                            <circle cx="12" cy="12" r="1.8" />
                            <circle cx="12" cy="19" r="1.8" />
                        </svg>
                    </button>

                    <div
                        v-show="headerMenuOpen"
                        class="absolute right-0 mt-2 w-48 rounded-2xl bg-surface border border-border shadow-2xl py-1.5 z-50 text-xs overflow-hidden"
                    >
                        <button
                            type="button"
                            @click="handleFinalizar(); headerMenuOpen = false;"
                            class="w-full px-3.5 py-2.5 text-left text-text hover:bg-surface-2 font-semibold transition-colors cursor-pointer"
                        >Finalizar sesión</button>
                        <button
                            type="button"
                            @click="descartarSesion"
                            class="w-full px-3.5 py-2.5 text-left text-danger hover:bg-danger/10 font-semibold transition-colors cursor-pointer"
                        >Descartar sesión</button>
                    </div>
                </div>
            </div>

            <!-- Progreso global: una línea fina, sin caja -->
            <div class="mx-auto w-full max-w-lg px-4 pb-2">
                <div class="flex items-center gap-2">
                    <div class="flex-1 h-1 rounded-full bg-surface-3 overflow-hidden">
                        <div
                            class="h-full rounded-full bg-brand transition-all duration-300"
                            :style="{ width: `${store.progresoPorcentaje}%` }"
                        ></div>
                    </div>
                    <span class="text-[10px] font-black text-text-3 tabular-nums shrink-0">
                        {{ store.totalSeriesCompletadas }}/{{ store.totalSeriesObjetivo }}
                    </span>
                </div>
            </div>
        </header>

        <!-- =========================== CONTENIDO =========================== -->
        <main class="flex-1 overflow-y-auto overscroll-contain">
            <div
                v-if="store.currentEjercicio"
                class="mx-auto w-full max-w-lg px-4 pt-1 pb-6 space-y-4"
            >
                <!-- Eyebrow: rutina + día, y navegación entre ejercicios -->
                <div class="flex items-center justify-between gap-2">
                    <div class="min-w-0">
                        <p class="text-[10px] font-black uppercase tracking-[0.16em] text-brand truncate leading-tight flex items-center gap-1.5" data-testid="ejercicio-progress-pill">
                            EJERCICIO {{ store.unidadProgreso.actual }} DE {{ store.unidadProgreso.total }}
                            <span class="text-text-3 font-medium">·</span>
                            <span class="text-text-3">{{ (store.currentEjercicio?.grupo_muscular || 'Fuerza').toUpperCase() }}</span>
                        </p>
                        <p
                            v-if="store.session.rutina_nombre"
                            class="text-[11px] font-bold text-text-2 truncate leading-tight mt-0.5"
                        >
                            {{ store.session.rutina_nombre }} · {{ (store.session.dia || '').toUpperCase() }}
                        </p>
                    </div>

                    <div class="flex items-center gap-0.5 shrink-0">
                        <button
                            type="button"
                            @click="vistaVisible = !vistaVisible"
                            :aria-pressed="!vistaVisible"
                            :aria-label="vistaVisible ? 'Ocultar vista del ejercicio' : 'Mostrar vista del ejercicio'"
                            :title="vistaVisible ? 'Ocultar imagen' : 'Mostrar imagen'"
                            data-testid="toggle-exercise-view"
                            class="w-8 h-8 rounded-[11px] text-text-3 hover:text-text hover:bg-surface-2 transition-colors cursor-pointer flex items-center justify-center"
                        >
                            <svg v-if="vistaVisible" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7zM15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.575-2.706M6.223 6.223A9.956 9.956 0 0112 5c4.478 0 8.268 2.943 9.542 7a9.97 9.97 0 01-1.272 2.61M9.88 9.88a3 3 0 104.243 4.243M3 3l18 18" />
                            </svg>
                        </button>

                        <button
                            type="button"
                            @click="store.prevEjercicio"
                            :disabled="store.session.currentEjercicioIndex === 0"
                            class="w-8 h-8 rounded-[11px] text-text-2 hover:text-text hover:bg-surface-2 disabled:opacity-30 disabled:cursor-not-allowed transition-colors cursor-pointer flex items-center justify-center"
                            title="Ejercicio anterior"
                            aria-label="Ejercicio anterior"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>

                        <button
                            type="button"
                            @click="store.nextEjercicio"
                            :disabled="store.session.currentEjercicioIndex >= store.session.ejercicios.length - 1"
                            class="w-8 h-8 rounded-[11px] text-text-2 hover:text-text hover:bg-surface-2 disabled:opacity-30 disabled:cursor-not-allowed transition-colors cursor-pointer flex items-center justify-center"
                            title="Siguiente ejercicio"
                            aria-label="Siguiente ejercicio"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                    </div>
                </div>


                <!--
                    BLOQUE 1 · NOMBRE DEL EJERCICIO
                    El texto más grande de la pantalla. A la derecha, el total
                    de series programadas (dato de contexto, no una caja).
                -->
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h2
                            v-if="!superserieOrder"
                            class="text-[26px] leading-[1.12] font-black text-text tracking-[-0.025em] line-clamp-2"
                            :title="mediaEjercicio?.nombre || store.currentEjercicio.nombre"
                        >
                            {{ mediaEjercicio?.nombre || store.currentEjercicio.nombre }}
                        </h2>
                        <p
                            v-if="!superserieOrder"
                            class="mt-1 text-[12px] font-semibold text-text-3 truncate"
                        >
                            {{ resumenEjercicio }}
                        </p>
                        <template v-else>
                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md bg-warning/15 text-warning text-[9px] font-black uppercase tracking-wider border border-warning/30">
                                ⚡ Superserie
                            </span>
                            <h2
                                class="mt-1 text-[17px] leading-[1.2] font-black text-text tracking-[-0.02em] line-clamp-2"
                                :title="`${superserieOrder.first.ejercicio.nombre} + ${superserieOrder.second.ejercicio.nombre}`"
                            >
                                {{ superserieOrder.first.ejercicio.nombre }} + {{ superserieOrder.second.ejercicio.nombre }}
                            </h2>
                        </template>
                    </div>

                    <span
                        v-if="!superserieOrder"
                        class="shrink-0 pt-1.5 text-[10px] font-black uppercase tracking-[0.14em] text-text-3"
                    >
                        {{ store.currentEjercicio.series_objetivo }} series
                    </span>
                </div>

                <!--
                    BLOQUES 2 + 4 · IMAGEN DE REFERENCIA + HISTORIAL
                    Imagen a la izquierda; a la derecha, la última vez (mín → máx)
                    y el esfuerzo de esa sesión. Sin cajas: solo tipografía.
                -->
                <div class="flex items-center gap-3.5" data-testid="exercise-media">
                    <div class="relative shrink-0">
                        <div
                            class="w-[88px] h-[88px] sm:w-24 sm:h-24 rounded-2xl bg-surface-2 border border-border overflow-hidden cursor-pointer group flex items-center justify-center"
                            @click="openMediaZoomOrToggleGif"
                            :title="gifHovered ? 'Animación activa (tocá para pausar / zoom)' : 'Tocá para animar / zoom'"
                        >
                            <img
                                v-if="vistaVisible && (ejercicioMedia?.gif_url || ejercicioMedia?.image_url)"
                                :key="mediaEjercicio?.nombre"
                                :src="gifHovered && ejercicioMedia.gif_url ? ejercicioMedia.gif_url : (ejercicioMedia.image_url || ejercicioMedia.gif_url)"
                                :alt="`Demostración de ${mediaEjercicio?.nombre}`"
                                class="h-full w-full object-contain bg-white transition-transform duration-200 group-hover:scale-105 select-none"
                                loading="lazy"
                            />
                            <div v-else-if="!vistaVisible" class="text-text-3 flex flex-col items-center">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.575-2.706M6.223 6.223A9.956 9.956 0 0112 5c4.478 0 8.268 2.943 9.542 7a9.97 9.97 0 01-1.272 2.61M9.88 9.88a3 3 0 104.243 4.243M3 3l18 18" />
                                </svg>
                            </div>
                            <div v-else class="text-text-3 flex flex-col items-center gap-0.5">
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.5 6.5v11M3.5 9v5M17.5 6.5v11M20.5 9v5M6.5 12h11" />
                                </svg>
                            </div>
                        </div>

                        <!-- Badge de zoom -->
                        <span
                            v-if="vistaVisible && (ejercicioMedia?.gif_url || ejercicioMedia?.image_url)"
                            class="absolute bottom-1 right-1 px-1.5 py-0.5 rounded-md bg-black/55 text-white text-[8px] font-black uppercase tracking-wider pointer-events-none"
                        >
                            GIF
                        </span>

                        <!-- Flechas para alternar media en superset -->
                        <button
                            v-if="superseriePartner && mediaEjercicioIndex !== store.session.currentEjercicioIndex"
                            type="button"
                            @click.stop="mediaEjercicioIndex = store.session.currentEjercicioIndex"
                            data-testid="media-prev"
                            :title="`Ver ${store.currentEjercicio.nombre}`"
                            aria-label="Ver imagen del ejercicio actual"
                            class="absolute left-0.5 top-1/2 -translate-y-1/2 z-10 w-5 h-5 rounded-full bg-brand text-white shadow flex items-center justify-center cursor-pointer"
                        >
                            <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
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
                            class="absolute right-0.5 top-1/2 -translate-y-1/2 z-10 w-5 h-5 rounded-full bg-brand text-white shadow flex items-center justify-center cursor-pointer"
                        >
                            <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                    </div>

                    <div class="flex-1 min-w-0">
                        <div v-if="!superserieOrder" data-testid="last-exercise-card">
                            <template v-if="tieneHistorial">
                                <p class="text-[10px] font-black uppercase tracking-[0.14em] text-text-3">
                                    Última vez
                                </p>
                                <div class="mt-1 flex items-baseline gap-2 text-[15px] font-black tabular-nums tracking-[-0.01em]">
                                    <span data-testid="last-exercise-min" class="text-text-2 font-bold">
                                        {{ formatPeso(lastExerciseData?.peso_min ?? lastExerciseData?.peso_top ?? 0) }} kg<span class="text-[11px] font-bold text-text-3"> × {{ (lastExerciseData?.reps_en_peso_min ?? lastExerciseData?.reps_en_peso_top) ?? 0 }}</span>
                                    </span>
                                    <span class="text-text-3 text-[12px]">→</span>
                                    <span data-testid="last-exercise-max" class="text-text">
                                        {{ formatPeso(lastExerciseData?.peso_max ?? lastExerciseData?.peso_top ?? 0) }} kg<span class="text-[11px] font-bold text-text-3"> × {{ (lastExerciseData?.reps_en_peso_max ?? lastExerciseData?.reps_en_peso_top) ?? 0 }}</span>
                                    </span>
                                </div>
                                <p class="mt-1 text-[10px] font-bold text-text-3 tabular-nums">
                                    Mínimo {{ formatPeso(lastExerciseData?.peso_min ?? lastExerciseData?.peso_top ?? 0) }}
                                    · Máximo {{ formatPeso(lastExerciseData?.peso_max ?? lastExerciseData?.peso_top ?? 0) }}
                                    <template v-if="lastExerciseData?.ultimo_esfuerzo">
                                        · {{ lastExerciseData.ultimo_esfuerzo.tipo }} {{ lastExerciseData.ultimo_esfuerzo.valor }}
                                    </template>
                                </p>
                            </template>
                            <p v-else class="text-[13px] font-semibold text-text-3 italic leading-snug">
                                Primera vez con este ejercicio.
                                <span class="not-italic block text-[11px] font-bold mt-0.5">
                                    Arrancá liviano y registrá cómo te sentís.
                                </span>
                            </p>
                        </div>

                        <!-- Historial de superserie: una fila por ejercicio -->
                        <div v-else class="space-y-1" data-testid="superset-dual-history">
                            <div class="flex items-baseline justify-between gap-2 text-[12px] tabular-nums" data-testid="superset-history-card-1">
                                <span class="text-[9px] font-black uppercase tracking-wider text-brand truncate max-w-[120px]">
                                    1. {{ superserieOrder.first.ejercicio.nombre }}
                                </span>
                                <span class="text-text-2 shrink-0">
                                    {{ formatPeso(lastExerciseDataFirst?.peso_min ?? lastExerciseDataFirst?.peso_top ?? 0) }} kg
                                    <span class="text-text-3">→</span>
                                    <strong class="text-text">{{ formatPeso(lastExerciseDataFirst?.peso_max ?? lastExerciseDataFirst?.peso_top ?? 0) }} kg</strong>
                                </span>
                            </div>
                            <div class="flex items-baseline justify-between gap-2 text-[12px] tabular-nums" data-testid="superset-history-card-2">
                                <span class="text-[9px] font-black uppercase tracking-wider text-brand truncate max-w-[120px]">
                                    2. {{ superserieOrder.second.ejercicio.nombre }}
                                </span>
                                <span class="text-text-2 shrink-0">
                                    {{ formatPeso(lastExerciseDataSecond?.peso_min ?? lastExerciseDataSecond?.peso_top ?? 0) }} kg
                                    <span class="text-text-3">→</span>
                                    <strong class="text-text">{{ formatPeso(lastExerciseDataSecond?.peso_max ?? lastExerciseDataSecond?.peso_top ?? 0) }} kg</strong>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Zoom fullscreen de la demostración -->
                <div
                    v-if="showMediaZoom"
                    class="fixed inset-0 z-[60] bg-black/90 flex flex-col items-center justify-center p-4 backdrop-blur-md"
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
                            {{ mediaEjercicio?.nombre || store.currentEjercicio?.nombre }}
                        </p>
                    </div>
                </div>

                <!-- Banner de superserie -->
                <div
                    v-if="superseriePartner && superserieOrder"
                    class="rounded-2xl border border-warning/30 bg-warning/[0.06] px-3.5 py-2.5"
                    data-testid="superset-banner"
                >
                    <p class="text-[10px] font-black uppercase tracking-[0.14em] text-warning">
                        Superserie {{ superseriePartner.ejercicio.superserie_grupo }}
                    </p>
                    <p class="mt-1 text-[12px] leading-snug text-text-2">
                        <template v-if="superserieOrder.currentRole === 'first'">
                            Hacé <strong class="text-text">{{ superserieOrder.first.ejercicio.nombre }}</strong>
                            y seguí directo con
                            <strong class="text-text">{{ superserieOrder.second.ejercicio.nombre }}</strong>
                            sin descansar.
                        </template>
                        <template v-else>
                            Hacé <strong class="text-text">{{ superserieOrder.second.ejercicio.nombre }}</strong>
                            — ya hiciste
                            <strong class="text-text">{{ superserieOrder.first.ejercicio.nombre }}</strong> antes,
                            seguí sin descansar.
                        </template>
                    </p>
                </div>

                <!-- Card(s) de la serie -->
                <template v-if="!superseriePartner">
                    <SetConfigCard
                        ref="cardSingleRef"
                        :ejercicio="store.currentEjercicio"
                        :ejercicio-index="store.session.currentEjercicioIndex"
                        :last-data="lastExerciseData"
                        @complete="onSetComplete"
                    />
                </template>

                <div v-else-if="superserieOrder" class="space-y-4" data-testid="superset-grid">
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

                <!-- Ejercicios de hoy (contexto, colapsado) -->
                <details
                    v-if="store.session.ejercicios?.length"
                    class="rounded-2xl border border-border bg-surface overflow-hidden"
                >
                    <summary class="flex items-center justify-between gap-2 px-3.5 py-2.5 cursor-pointer list-none select-none">
                        <span class="text-[10px] font-black uppercase tracking-[0.14em] text-text-3">
                            Ejercicios de hoy
                        </span>
                        <span class="flex items-center gap-2">
                            <span class="text-[11px] font-bold text-text-2 tabular-nums">
                                {{ ejerciciosCompletados }}/{{ store.session.ejercicios.length }}
                            </span>
                            <svg class="w-3.5 h-3.5 text-text-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </span>
                    </summary>
                    <ul class="border-t border-border divide-y divide-border">
                        <li
                            v-for="(ej, i) in store.session.ejercicios"
                            :key="i"
                            class="flex items-center gap-2.5 px-3.5 py-2"
                        >
                            <span
                                class="w-5 h-5 rounded-full grid place-items-center text-[9px] font-black shrink-0"
                                :class="
                                    ej.completed
                                        ? 'bg-success/15 text-success'
                                        : i === store.session.currentEjercicioIndex
                                            ? 'bg-brand/15 text-brand'
                                            : 'bg-surface-2 text-text-3'
                                "
                            >
                                <svg v-if="ej.completed" class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                                <template v-else>{{ i + 1 }}</template>
                            </span>
                            <span
                                class="text-[12px] font-semibold truncate"
                                :class="
                                    i === store.session.currentEjercicioIndex
                                        ? 'text-text font-black'
                                        : 'text-text-2'
                                "
                            >
                                {{ ej.nombre }}
                            </span>
                        </li>
                    </ul>
                </details>
            </div>

            <!-- Estado de recuperación: sesión sin ejercicios -->
            <div
                v-else
                class="mx-auto max-w-sm my-12 text-center px-6"
            >
                <div class="text-3xl mb-3">🏋️</div>
                <h3 class="text-base font-black text-text mb-2">No hay ejercicios activos en esta sesión</h3>
                <p class="text-xs text-text-3 mb-5 leading-relaxed">
                    La sesión se inició antes de sincronizar los ejercicios del día.
                </p>
                <div class="flex items-center justify-center gap-2">
                    <button
                        type="button"
                        @click="store.refreshNotasActuales"
                        class="px-4 py-2.5 rounded-xl bg-brand hover:brightness-110 text-white text-xs font-bold transition-colors cursor-pointer"
                    >
                        Sincronizar ejercicios
                    </button>
                    <button
                        type="button"
                        @click="store.discard(); $emit('minimize')"
                        class="px-4 py-2.5 rounded-xl bg-surface-2 hover:bg-surface-3 border border-border text-text-2 text-xs font-bold transition-colors cursor-pointer"
                    >
                        Reiniciar sesión
                    </button>
                </div>
            </div>
        </main>

        <!--
            ======================= ACCIÓN PRIMARIA =======================
            Una sola CTA, fija abajo, en la zona del pulgar. Es un ESPEJO de la
            card activa: toma su payload real (peso/reps/tipo/esfuerzo ya
            editados) para que el estado no se duplique.
        -->
        <div
            v-if="store.currentEjercicio"
            class="shrink-0 bg-gradient-to-t from-bg via-bg to-transparent px-4 pt-3 pb-[max(1rem,env(safe-area-inset-bottom))]"
        >
            <div class="mx-auto w-full max-w-lg space-y-1.5">
                <!-- Todo completo -->
                <button
                    v-if="store.isSessionComplete"
                    type="button"
                    @click="handleFinalizar"
                    data-testid="btn-finalizar-sesion"
                    class="w-full h-[54px] rounded-2xl bg-success hover:brightness-110 active:scale-[0.99] text-white text-[15px] font-black tracking-[0.02em] shadow-[0_12px_28px_-10px_rgba(4,120,87,0.65)] flex items-center justify-center gap-2 transition-all cursor-pointer"
                >
                    FINALIZAR SESIÓN
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                </button>

                <!-- Superserie: ronda -->
                <template v-else-if="superseriePartner">
                    <button
                        v-if="!isSupersetComplete"
                        type="button"
                        @click="completarRondaSuperserie"
                        data-testid="btn-completar-superset"
                        class="w-full h-[54px] rounded-2xl bg-gradient-to-br from-[var(--color-violet-primary)] to-[var(--color-violet-secondary)] active:scale-[0.99] text-white text-[15px] font-black shadow-[0_12px_28px_-10px_var(--color-violet-glow-strong)] flex items-center justify-center gap-2 transition-all cursor-pointer"
                    >
                        ⚡ Completar ronda {{ supersetRondaNumero }}
                    </button>
                    <div
                        v-else
                        class="w-full h-[54px] rounded-2xl bg-success/15 border border-success/40 text-success text-[15px] font-black flex items-center justify-center gap-2"
                    >
                        ✓ Superserie completa
                    </div>
                </template>

                <!-- Serie normal -->
                <button
                    v-else-if="!ejercicioActualCompleto"
                    type="button"
                    data-testid="sticky-cta-completar"
                    @click="completarDesdeSticky"
                    class="w-full h-[54px] rounded-2xl bg-gradient-to-br from-[var(--color-violet-primary)] to-[var(--color-violet-secondary)] active:scale-[0.99] text-white text-[15px] font-black tracking-[0.02em] shadow-[0_12px_28px_-10px_var(--color-violet-glow-strong)] flex items-center justify-center gap-2 transition-all cursor-pointer"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    Registrar serie
                </button>
                <div
                    v-else
                    class="w-full h-[54px] rounded-2xl bg-success/15 border border-success/40 text-success text-[15px] font-black flex items-center justify-center gap-2"
                >
                    ✓ Ejercicio completo
                </div>

                <!-- Deshacer -->
                <div v-if="store.canUndo && !store.isSessionComplete" class="text-center pt-0.5">
                    <button
                        type="button"
                        @click="deshacer"
                        class="text-[11px] font-bold text-text-3 hover:text-danger transition-colors cursor-pointer"
                    >
                        ↩ Deshacer última serie
                    </button>
                </div>
            </div>
        </div>
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
import { numeroCalentamientoPersisted } from '@/utils/seriesNumero';
import { hoyLocal } from '@/utils/fecha';
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
const cardSingleRef = ref(null);

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

const activeSetsEfectivos = computed(() => {
    return (store.currentEjercicio?.sets || []).filter((s) => s.tipo_serie !== 'calentamiento').length;
});

// Bloque sin series efectivas (ej. movilidad / calentamiento del día).
const currentEsBloque = computed(() => {
    return Number(store.currentEjercicio?.series_objetivo || 0) === 0;
});

/**
 * El ejercicio activo ya tiene todas sus series efectivas registradas.
 * Se usa para el estado del CTA fijo (verde "Ejercicio completo").
 */
const ejercicioActualCompleto = computed(() => {
    if (store.currentEjercicio?.completed) return true;
    if (currentEsBloque.value) return false;
    return (
        activeSetsEfectivos.value >= Number(store.currentEjercicio?.series_objetivo || 0)
    );
});

/**
 * Ejercicios de la sesión ya completados (para el resumen del acordeón).
 */
const ejerciciosCompletados = computed(() => {
    return (store.session.ejercicios || []).filter((ej) => ej.completed).length;
});

/**
 * Resumen de una línea debajo del nombre del ejercicio:
 * "4 series · 6–8 reps · descanso 2 min".
 */
const resumenEjercicio = computed(() => {
    const ej = store.currentEjercicio;
    if (!ej) return '';

    const partes = [];
    const series = Number(ej.series_objetivo || 0);
    if (series > 0) {
        partes.push(`${series} ${series === 1 ? 'serie' : 'series'}`);
    } else {
        partes.push('bloque sin series');
    }

    const min = ej.reps_min;
    const max = ej.reps_max;
    if (min != null && min !== '' && max != null && max !== '') {
        partes.push(String(min) === String(max) ? `${min} reps` : `${min}–${max} reps`);
    } else if (min != null && min !== '') {
        partes.push(`${min} reps`);
    }

    const descanso = Number(ej.descanso_min || 0);
    if (descanso > 0) {
        partes.push(`descanso ${formatPeso(descanso)} min`);
    }

    return partes.join(' · ');
});

/**
 * Registra la serie en curso desde la barra fija usando el payload real de la
 * card (peso/reps/tipo/esfuerzo tal como los editó el usuario). La card sigue
 * siendo la única fuente de verdad: acá sólo la puenteamos al store.
 */
const completarDesdeSticky = async () => {
    if (superseriePartner.value) {
        await completarRondaSuperserie();
        return;
    }
    const payload = cardSingleRef.value?.getPayload();
    if (!payload) return;
    await onSetComplete(payload);
};

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
            if (typeof document !== 'undefined') {
                document.body.style.overflow = 'hidden';
            }
        } else {
            wakeLock.releaseWakeLock();
            if (typeof document !== 'undefined') {
                document.body.style.overflow = '';
            }
        }
    },
    { immediate: true }
);

onUnmounted(() => {
    wakeLock.releaseWakeLock();
    if (typeof document !== 'undefined') {
        document.body.style.overflow = '';
    }
});
</script>
