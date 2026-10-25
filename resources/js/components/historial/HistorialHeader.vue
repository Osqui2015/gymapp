<template>
    <div class="flex flex-col gap-space-xl">
        <!-- Page Header, Breadcrumb & Quick Actions -->
        <section class="flex flex-col md:flex-row md:items-end justify-between gap-space-lg">
            <div class="flex flex-col gap-space-xs">
                <div class="flex items-center gap-space-xs text-label-sm uppercase tracking-wider text-outline mb-1">
                    <a class="hover:text-on-surface transition-colors" href="/dashboard">Inicio</a>
                    <span class="material-symbols-outlined text-[14px]">chevron_right</span>
                    <span class="text-tertiary">Historial de entrenamiento</span>
                </div>
                <div class="flex items-center gap-space-sm">
                    <span class="px-2.5 py-0.5 rounded-full bg-secondary-container/40 text-on-secondary-container font-label-sm text-label-sm uppercase tracking-widest">
                        Seguimiento de Progreso
                    </span>
                    <span class="w-2 h-2 rounded-full bg-tertiary animate-pulse"></span>
                </div>
                <h1 class="font-headline-xl text-headline-xl-mobile md:text-headline-xl text-on-surface tracking-tight mt-1">
                    Historial de Sesiones
                </h1>
                <p class="text-body-md text-on-surface-variant max-w-2xl">
                    Revisa tu registro de sesiones, volumen acumulado y métricas de fatiga RIR en tiempo real con telemetría de alto rendimiento.
                </p>
            </div>

            <!-- Export / Action Buttons & Alumno Selector -->
            <div class="flex flex-wrap items-center gap-space-sm">
                <div v-if="isTrainerOrAdmin && alumnos.length > 0" class="relative flex items-center">
                    <select
                        :value="selectedAlumnoId ?? ''"
                        @change="$emit('alumno-change', $event.target.value ? Number($event.target.value) : null)"
                        class="h-11 pl-3 pr-9 rounded-xl bg-surface-container-low text-on-surface text-label-md font-label-md appearance-none focus:outline-none focus:ring-2 focus:ring-secondary transition-all cursor-pointer border-0 shadow-sm"
                    >
                        <option value="">Seleccionar alumno...</option>
                        <option v-for="al in alumnos" :key="al.id" :value="al.id">
                            {{ al.name || al.nick }}
                        </option>
                    </select>
                    <span class="material-symbols-outlined absolute right-3 text-outline pointer-events-none text-[18px]">expand_more</span>
                </div>

                <template v-if="hasHistory || canExport">
                    <button
                        type="button"
                        @click="$emit('export-resumen-csv')"
                        title="Exportar tabla resumen por fecha y ejercicio (CSV)"
                        aria-label="Exportar resumen del historial a CSV"
                        class="h-11 px-space-md rounded-xl bg-surface-container-low hover:bg-surface-container text-on-surface font-label-md text-label-md flex items-center gap-space-xs transition-all cursor-pointer shadow-sm"
                    >
                        <span class="material-symbols-outlined text-[18px] text-secondary">analytics</span>
                        <span>Resumen</span>
                    </button>
                    <button
                        type="button"
                        @click="$emit('export-csv')"
                        aria-label="Exportar historial a CSV"
                        class="h-11 px-space-md rounded-xl bg-surface-container-low hover:bg-surface-container text-on-surface font-label-md text-label-md flex items-center gap-space-xs transition-all cursor-pointer shadow-sm"
                    >
                        <span class="material-symbols-outlined text-[18px] text-primary">download</span>
                        <span>CSV</span>
                    </button>
                    <button
                        type="button"
                        @click="$emit('export-pdf')"
                        :disabled="exportingPdf"
                        aria-label="Exportar historial a PDF"
                        class="h-11 px-space-md rounded-xl bg-surface-container-low hover:bg-surface-container text-on-surface font-label-md text-label-md flex items-center gap-space-xs transition-all cursor-pointer shadow-sm disabled:opacity-50"
                    >
                        <span class="material-symbols-outlined text-[18px] text-error">picture_as_pdf</span>
                        <span>{{ exportingPdf ? 'Generando...' : 'PDF' }}</span>
                    </button>
                </template>
            </div>
        </section>

        <!-- 5-Column KPI Metric Cards Grid -->
        <section class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-space-md">
            <!-- Metric 1: Ejercicios -->
            <div class="p-space-lg rounded-2xl bg-surface-container-low hover:bg-surface-container transition-colors flex flex-col justify-between gap-space-md shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="font-label-sm text-label-sm uppercase tracking-wider text-outline">Ejercicios</span>
                    <div class="w-8 h-8 rounded-lg bg-surface-container-high flex items-center justify-center text-on-surface-variant">
                        <span class="material-symbols-outlined text-[18px]">open_in_full</span>
                    </div>
                </div>
                <div>
                    <div class="flex items-baseline gap-space-xs">
                        <span class="font-headline-xl text-headline-lg md:text-headline-xl text-on-surface font-bold tabular-nums">{{ stats.ejercicios }}</span>
                        <span class="text-body-sm text-outline">totales</span>
                    </div>
                    <div class="mt-2 flex items-center gap-1 text-label-sm text-tertiary">
                        <span class="material-symbols-outlined text-[14px]">trending_up</span>
                        <span>+3 este ciclo</span>
                    </div>
                </div>
            </div>

            <!-- Metric 2: Series -->
            <div class="p-space-lg rounded-2xl bg-surface-container-low hover:bg-surface-container transition-colors flex flex-col justify-between gap-space-md shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="font-label-sm text-label-sm uppercase tracking-wider text-outline">Series</span>
                    <span class="px-2 py-0.5 rounded-md bg-tertiary-container/30 text-tertiary font-label-sm text-label-sm flex items-center gap-1">
                        <span class="material-symbols-outlined text-[12px]">check_circle</span>
                        {{ completionPct }}%
                    </span>
                </div>
                <div>
                    <div class="flex items-baseline gap-space-xs">
                        <span class="font-headline-xl text-headline-lg md:text-headline-xl text-on-surface font-bold tabular-nums">{{ stats.totalSeries }}</span>
                        <span class="text-body-sm text-outline">sets</span>
                    </div>
                    <div class="mt-2 flex items-center gap-1 text-label-sm text-on-surface-variant">
                        <span>Meta cumplida</span>
                    </div>
                </div>
            </div>

            <!-- Metric 3: Volumen -->
            <div class="p-space-lg rounded-2xl bg-surface-container-low hover:bg-surface-container transition-colors flex flex-col justify-between gap-space-md shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="font-label-sm text-label-sm uppercase tracking-wider text-outline">Volumen</span>
                    <div class="w-8 h-8 rounded-lg bg-secondary-container/30 flex items-center justify-center text-secondary">
                        <span class="material-symbols-outlined text-[18px]">layers</span>
                    </div>
                </div>
                <div>
                    <div class="flex items-baseline gap-space-xs">
                        <span class="font-headline-xl text-headline-lg md:text-headline-xl text-on-surface font-bold tabular-nums">{{ displayVolumen }}</span>
                        <span class="text-body-sm text-secondary font-semibold">{{ volumenUnit }}</span>
                    </div>
                    <div class="mt-2 flex items-center gap-1 text-label-sm text-tertiary">
                        <span class="material-symbols-outlined text-[14px]">arrow_upward</span>
                        <span>+4.2% neto</span>
                    </div>
                </div>
            </div>

            <!-- Metric 4: Peso Prom. -->
            <div class="p-space-lg rounded-2xl bg-surface-container-low hover:bg-surface-container transition-colors flex flex-col justify-between gap-space-md shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="font-label-sm text-label-sm uppercase tracking-wider text-outline">Peso Prom.</span>
                    <div class="w-8 h-8 rounded-lg bg-surface-container-high flex items-center justify-center text-on-surface-variant">
                        <span class="material-symbols-outlined text-[18px]">scale</span>
                    </div>
                </div>
                <div>
                    <div class="flex items-baseline gap-space-xs">
                        <span class="font-headline-xl text-headline-lg md:text-headline-xl text-on-surface font-bold tabular-nums">{{ stats.pesoPromedio }}</span>
                        <span class="text-body-sm text-outline">kg</span>
                    </div>
                    <div class="mt-2 flex items-center gap-1 text-label-sm text-on-surface-variant">
                        <span>Por repetición</span>
                    </div>
                </div>
            </div>

            <!-- Metric 5: Repeticiones Prom. -->
            <div class="col-span-2 md:col-span-4 lg:col-span-1 p-space-lg rounded-2xl bg-surface-container-low hover:bg-surface-container transition-colors flex flex-col justify-between gap-space-md shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="font-label-sm text-label-sm uppercase tracking-wider text-outline">Repeticiones Prom.</span>
                    <div class="w-8 h-8 rounded-lg bg-tertiary-container/20 flex items-center justify-center text-tertiary">
                        <span class="material-symbols-outlined text-[18px]">repeat</span>
                    </div>
                </div>
                <div>
                    <div class="flex items-baseline gap-space-xs">
                        <span class="font-headline-xl text-headline-lg md:text-headline-xl text-secondary font-bold tabular-nums">{{ stats.repsPromedio }}</span>
                        <span class="text-body-sm text-outline">reps/set</span>
                    </div>
                    <div class="mt-2 flex items-center gap-1 text-label-sm text-on-surface-variant">
                        <span>Por serie completada ({{ stats.repsPromedio }} reps prom.)</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- View Tabs & Mode Switcher -->
        <section class="flex flex-col sm:flex-row sm:items-center justify-between gap-space-md">
            <div class="inline-flex flex-wrap p-1 bg-surface-container-low rounded-xl self-start gap-1">
                <button
                    v-for="tab in tabs"
                    :key="tab.id"
                    type="button"
                    @click="$emit('tab-change', tab.id)"
                    :class="[
                        'flex-1 px-space-lg py-2 rounded-lg font-label-md text-label-md transition-all cursor-pointer flex items-center justify-center gap-1.5 whitespace-nowrap',
                        activeTab === tab.id
                            ? 'bg-secondary-container text-on-secondary-container shadow-sm'
                            : 'text-on-surface-variant hover:text-on-surface',
                    ]"
                >
                    <span v-if="tab.icon" class="material-symbols-outlined text-[16px]">{{ tab.icon }}</span>
                    <span>{{ tab.label }}</span>
                </button>
            </div>
            <div class="flex items-center gap-space-sm text-label-sm text-outline">
                <span>Modo:</span>
                <button
                    type="button"
                    @click="$emit('tab-change', 'matrix')"
                    :class="activeTab === 'matrix' ? 'bg-surface-container-high text-on-surface' : 'text-outline hover:text-on-surface'"
                    class="p-1.5 rounded-lg transition-colors cursor-pointer"
                    title="Vista Matriz"
                >
                    <span class="material-symbols-outlined text-[18px]">table_rows</span>
                </button>
                <button
                    type="button"
                    @click="$emit('tab-change', 'evolution')"
                    :class="activeTab === 'evolution' ? 'bg-surface-container-high text-on-surface' : 'text-outline hover:text-on-surface'"
                    class="p-1.5 rounded-lg transition-colors cursor-pointer"
                    title="Vista Gráficos"
                >
                    <span class="material-symbols-outlined text-[18px]">waterfall_chart</span>
                </button>
            </div>
        </section>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    stats: {
        type: Object,
        required: true,
        default: () => ({
            ejercicios: 0,
            totalSeries: 0,
            seriesCompletadas: 0,
            volumenTotal: 0,
            tonelajeTotal: '0 kg',
            pesoPromedio: '0.0',
            repsPromedio: '0.0',
        }),
    },
    hasHistory: {
        type: Boolean,
        default: true,
    },
    canExport: {
        type: Boolean,
        default: false,
    },
    isTrainerOrAdmin: {
        type: Boolean,
        default: false,
    },
    alumnos: {
        type: Array,
        default: () => [],
    },
    selectedAlumnoId: {
        type: [Number, String],
        default: null,
    },
    showKeyExercisesTab: {
        type: Boolean,
        default: false,
    },
    exportingPdf: {
        type: Boolean,
        default: false,
    },
    activeTab: {
        type: String,
        default: 'matrix',
    },
});

defineEmits(['export-resumen-csv', 'export-csv', 'export-pdf', 'tab-change', 'alumno-change']);

const tabs = computed(() => {
    const base = [
        { id: 'matrix', label: 'Matriz Cargas', icon: 'grid_on' },
        { id: 'evolution', label: 'Resumen y PRs', icon: 'trending_up' },
        { id: 'rir', label: 'Esfuerzo RIR', icon: 'bolt' },
        { id: 'rm_calculator', label: '1RM', icon: 'calculate' },
        { id: 'body_map', label: 'Músculos', icon: 'accessibility_new' },
        { id: 'comparison', label: 'Comparar', icon: 'compare_arrows' },
        { id: 'calendar', label: 'Calendario', icon: 'calendar_month' },
    ];
    if (props.showKeyExercisesTab) {
        base.push({ id: 'key_exercises', label: 'Ejercicios Clave', icon: 'star' });
    }
    return base;
});

const displayVolumen = computed(() => {
    if (props.stats.tonelajeTotal) {
        return String(props.stats.tonelajeTotal).replace(/\s*(kg|t|ton).*$/i, '');
    }
    const vol = props.stats.volumenTotal || 0;
    if (vol >= 1000) {
        return (vol / 1000).toFixed(1);
    }
    return Math.round(vol).toLocaleString();
});

const volumenUnit = computed(() => {
    if (props.stats.tonelajeTotal) {
        const match = String(props.stats.tonelajeTotal).match(/(kg|ton|t)\b/i);
        return match ? match[1] : 't';
    }
    const vol = props.stats.volumenTotal || 0;
    return vol >= 1000 ? 't' : 'kg';
});

const completionPct = computed(() => {
    const total = props.stats.totalSeries || 0;
    // Sin datos o sin el dato de series completadas: 0% real, no 100% falso.
    if (total === 0) return 0;
    if (props.stats.seriesCompletadas === undefined) return 0;
    return Math.round(((props.stats.seriesCompletadas || 0) / total) * 100);
});
</script>
