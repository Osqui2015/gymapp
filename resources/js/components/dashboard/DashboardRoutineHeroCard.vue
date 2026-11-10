<script setup>
import { useTrainingSessionStore } from '@/stores/trainingSession';
import DashboardSessionProgressBar from './DashboardSessionProgressBar.vue';

defineProps({
    /** Nombre de la rutina activa. */
    nombreRutina: {
        type: String,
        default: '',
    },
    /** Título del hero del día (ej. "Día 1 (Torso): Torso"). */
    tituloHeroDia: {
        type: String,
        default: '',
    },
    /** Cantidad de ejercicios del día. */
    ejerciciosCount: {
        type: Number,
        default: 0,
    },
    /** Cantidad de series del día. */
    seriesTotales: {
        type: Number,
        default: 0,
    },
    /** Duración estimada de la sesión en minutos. */
    duracionEstimada: {
        type: Number,
        default: 0,
    },
    /** Calorías estimadas de la sesión. */
    caloriasEstimadas: {
        type: Number,
        default: 0,
    },
    /** Texto de estado del último registro (ej. "Último registro completado hoy"). */
    estadoUltimoRegistroHero: {
        type: String,
        default: '',
    },
    /** Series completadas de la sesión. */
    seriesCompletadas: {
        type: Number,
        default: 0,
    },
    /** Porcentaje de avance de la sesión (0-100). */
    progresoDia: {
        type: Number,
        default: 0,
    },
});

const emit = defineEmits(['start-workout']);

const session = useTrainingSessionStore();
</script>

<template>
    <section
        class="relative rounded-xl bg-surface-container-low p-space-lg md:p-space-xl mb-space-xl overflow-hidden shadow-xl"
        data-purpose="active-workout-hero"
        data-tour="rutina-header"
    >
        <div
            class="absolute -right-24 -bottom-24 w-80 h-80 bg-primary-container/10 rounded-full blur-[100px] pointer-events-none"
        ></div>
        <div
            class="relative flex flex-col lg:flex-row lg:items-center justify-between gap-space-xl mb-space-xl"
        >
            <div class="flex flex-col gap-space-sm max-w-2xl">
                <div class="flex items-center gap-space-sm">
                    <span
                        class="px-space-md py-0.5 rounded-full bg-secondary-container/60 text-secondary font-label-sm text-label-sm tracking-wide uppercase font-semibold"
                    >
                        {{ nombreRutina }}
                    </span>
                    <span
                        class="inline-flex items-center gap-1 text-tertiary font-label-sm text-label-sm"
                    >
                        <span class="material-symbols-outlined text-[14px]"
                            >check_circle</span
                        >
                        Plan Óptimo
                    </span>
                </div>
                <h2
                    class="font-headline-xl text-headline-xl text-on-surface font-bold tracking-tight"
                >
                    {{ tituloHeroDia }}
                </h2>
                <div
                    class="flex flex-wrap items-center gap-space-md text-on-surface-variant font-label-md text-label-md"
                >
                    <span class="flex items-center gap-1">
                        <span
                            class="material-symbols-outlined text-[16px] text-secondary"
                            >fitness_center</span
                        >
                        {{ ejerciciosCount }} ejercicios
                    </span>
                    <span>•</span>
                    <span class="flex items-center gap-1">
                        <span
                            class="material-symbols-outlined text-[16px] text-secondary"
                            >repeat</span
                        >
                        {{ seriesTotales }} series
                    </span>
                    <span>•</span>
                    <span class="flex items-center gap-1">
                        <span
                            class="material-symbols-outlined text-[16px] text-secondary"
                            >timer</span
                        >
                        ~{{ duracionEstimada }} min
                    </span>
                    <span>•</span>
                    <span class="flex items-center gap-1 text-tertiary">
                        <span class="material-symbols-outlined text-[16px]"
                            >local_fire_department</span
                        >
                        {{ caloriasEstimadas }} kcal est.
                    </span>
                </div>
            </div>
            <div
                class="flex flex-col sm:flex-row lg:flex-col items-stretch sm:items-center lg:items-end gap-space-md min-w-[240px]"
            >
                <button
                    type="button"
                    @click="$emit('start-workout')"
                    class="w-full sm:w-auto px-space-xl py-space-md rounded-full bg-gradient-to-r from-primary-container to-secondary-container hover:from-primary hover:to-secondary text-on-primary font-label-lg text-label-lg font-bold flex items-center justify-center gap-2 shadow-[0_0_24px_rgba(99,102,241,0.45)] hover:shadow-[0_0_32px_rgba(139,92,246,0.6)] transition-all duration-300"
                >
                    <span
                        class="material-symbols-outlined text-[20px]"
                        :style="{ fontVariationSettings: '\'FILL\' 1' }"
                        >play_arrow</span
                    >
                    <span>{{
                        session.isActive
                            ? 'Continuar entrenamiento'
                            : 'Comenzar entrenamiento'
                    }}</span>
                </button>
                <span
                    class="font-label-sm text-label-sm text-outline text-center lg:text-right"
                >
                    {{ estadoUltimoRegistroHero }}
                </span>
            </div>
        </div>

        <!-- Session Progress Bar -->
        <DashboardSessionProgressBar
            :series-completadas="seriesCompletadas"
            :series-totales="seriesTotales"
            :progreso-dia="progresoDia"
        />
    </section>
</template>
