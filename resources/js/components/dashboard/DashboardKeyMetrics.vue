<script setup>
defineProps({
    /** Métricas agregadas de la sesión: streak, longestStreak, totalSets30d, trend30d. */
    stats: {
        type: Object,
        default: () => ({}),
    },
    /** Etiqueta del último entreno (ej. "Hoy", "Hace 3d"). */
    ultimoEntrenoLabel: {
        type: String,
        default: '—',
    },
    /** Subtítulo del último entreno. */
    ultimoEntrenoSub: {
        type: String,
        default: '',
    },
    /** Si el último entreno está en alerta (>= 4 días sin entrenar). */
    ultimoEntrenoAlerta: {
        type: Boolean,
        default: false,
    },
    /** Series completadas de la sesión. */
    seriesCompletadas: {
        type: Number,
        default: 0,
    },
    /** Series totales de la sesión. */
    seriesTotales: {
        type: Number,
        default: 0,
    },
    /** Series pendientes de la sesión. */
    seriesPendientes: {
        type: Number,
        default: 0,
    },
    /** Peso total registrado en la sesión. */
    pesoRegistrado: {
        type: String,
        default: '0.0',
    },
    /** Peso promedio por serie. */
    pesoPromedio: {
        type: String,
        default: '0.0',
    },
    /** Repeticiones registradas en la sesión. */
    repsRegistradas: {
        type: Number,
        default: 0,
    },
});
</script>

<template>
    <section
        class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-space-md mb-space-xl"
        data-tour="stats"
    >
        <!-- Sets 30 Días -->
        <div
            class="col-span-1 md:col-span-2 lg:col-span-2 flex flex-col justify-between p-space-md rounded-xl bg-surface-container-low border border-outline-variant/60 shadow-xs"
        >
            <div
                class="flex items-center justify-between gap-space-xs mb-space-sm"
            >
                <span
                    class="font-label-sm text-label-sm uppercase tracking-wider text-outline"
                    >Sets 30 Días</span
                >
                <span
                    v-if="stats.trend30d !== 0"
                    class="inline-flex items-center px-1.5 py-0.5 rounded-full font-label-sm text-label-sm"
                    :class="
                        stats.trend30d >= 0
                            ? 'bg-tertiary/10 text-tertiary'
                            : 'bg-error/10 text-error'
                    "
                >
                    {{ stats.trend30d > 0 ? '+' : '' }}{{ stats.trend30d }}%
                </span>
            </div>
            <div>
                <div
                    class="font-headline-lg text-headline-lg font-bold text-on-surface"
                >
                    {{ stats.totalSets30d }}
                </div>
                <div class="font-body-sm text-body-sm text-tertiary">
                    Volumen alto sostenido
                </div>
            </div>
        </div>

        <!-- Último Entreno -->
        <div
            class="col-span-1 md:col-span-2 lg:col-span-2 flex flex-col justify-between p-space-md rounded-xl bg-surface-container-low border border-outline-variant/60 shadow-xs"
        >
            <div
                class="flex items-center justify-between gap-space-xs mb-space-sm"
            >
                <span
                    class="font-label-sm text-label-sm uppercase tracking-wider text-outline"
                    >Último Entreno</span
                >
                <span
                    class="material-symbols-outlined text-[18px]"
                    :class="ultimoEntrenoAlerta ? 'text-error' : 'text-tertiary'"
                >
                    {{ ultimoEntrenoAlerta ? 'warning' : 'check_circle' }}
                </span>
            </div>
            <div>
                <div
                    class="font-headline-lg text-headline-lg font-bold text-on-surface"
                >
                    {{ ultimoEntrenoLabel }}
                </div>
                <div
                    class="font-body-sm text-body-sm"
                    :class="ultimoEntrenoAlerta ? 'text-error' : 'text-tertiary'"
                >
                    {{ ultimoEntrenoSub }}
                </div>
            </div>
        </div>

        <!-- Racha Actual -->
        <div
            class="col-span-1 md:col-span-2 lg:col-span-3 flex flex-col justify-between p-space-md rounded-xl bg-surface-container-low border border-outline-variant/60 shadow-xs"
        >
            <div
                class="flex items-center justify-between gap-space-xs mb-space-sm"
            >
                <span
                    class="font-label-sm text-label-sm uppercase tracking-wider text-outline"
                    >Racha Actual</span
                >
                <span
                    class="material-symbols-outlined text-secondary text-[18px]"
                    >local_fire_department</span
                >
            </div>
            <div class="flex items-baseline justify-between">
                <div>
                    <div
                        class="font-headline-lg text-headline-lg font-bold text-on-surface"
                    >
                        {{ stats.streak }}
                        <span
                            class="font-body-md text-body-md text-outline font-normal"
                            >días</span
                        >
                    </div>
                    <div
                        class="font-body-sm text-body-sm text-on-surface-variant"
                    >
                        Récord previo: {{ stats.longestStreak }} días
                    </div>
                </div>
                <div
                    class="hidden sm:flex items-center gap-1 text-outline font-label-sm text-label-sm"
                >
                    <span>Objetivo: 4/sem</span>
                </div>
            </div>
        </div>

        <!-- Series del Día -->
        <div
            class="col-span-1 md:col-span-1 lg:col-span-2 flex flex-col justify-between p-space-md rounded-xl bg-surface-container-low border border-outline-variant/60 shadow-xs"
        >
            <span
                class="font-label-sm text-label-sm uppercase tracking-wider text-outline mb-space-xs"
                >Series del Día</span
            >
            <div>
                <div
                    class="font-headline-lg text-headline-lg font-bold text-tertiary"
                >
                    {{ seriesCompletadas }}/{{ seriesTotales }}
                </div>
                <div class="font-body-sm text-body-sm text-outline">
                    {{ seriesPendientes }} pendientes
                </div>
            </div>
        </div>

        <!-- Peso Registrado -->
        <div
            class="col-span-1 md:col-span-1 lg:col-span-2 flex flex-col justify-between p-space-md rounded-xl bg-surface-container-low border border-outline-variant/60 shadow-xs"
        >
            <span
                class="font-label-sm text-label-sm uppercase tracking-wider text-outline mb-space-xs"
                >Peso Registrado</span
            >
            <div>
                <div
                    class="font-headline-lg text-headline-lg font-bold text-on-surface"
                >
                    {{ pesoRegistrado }}
                    <span class="font-body-sm text-body-sm text-outline"
                        >kg</span
                    >
                </div>
                <div class="font-body-sm text-body-sm text-outline">
                    Volumen de la sesión
                </div>
            </div>
        </div>

        <!-- Peso Promedio -->
        <div
            class="col-span-1 md:col-span-1 lg:col-span-1 flex flex-col justify-between p-space-md rounded-xl bg-surface-container-low border border-outline-variant/60 shadow-xs"
        >
            <span
                class="font-label-sm text-label-sm uppercase tracking-wider text-outline mb-space-xs"
                >Peso Prom.</span
            >
            <div>
                <div
                    class="font-headline-md text-headline-md font-bold text-on-surface"
                >
                    {{ pesoPromedio }}
                    <span class="font-body-sm text-body-sm text-outline"
                        >kg</span
                    >
                </div>
                <div class="font-body-sm text-body-sm text-outline">
                    Por serie
                </div>
            </div>
        </div>

        <!-- Reps Realizadas -->
        <div
            class="col-span-1 md:col-span-1 lg:col-span-2 flex flex-col justify-between p-space-md rounded-xl bg-surface-container-low border border-outline-variant/60 shadow-xs"
        >
            <span
                class="font-label-sm text-label-sm uppercase tracking-wider text-outline mb-space-xs"
                >Reps Realizadas</span
            >
            <div>
                <div
                    class="font-headline-lg text-headline-lg font-bold text-secondary"
                >
                    {{ repsRegistradas }}
                </div>
                <div class="font-body-sm text-body-sm text-outline">
                    Repeticiones del día
                </div>
            </div>
        </div>
    </section>
</template>
