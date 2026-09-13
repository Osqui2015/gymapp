<template>
    <div>
        <!-- Selector de alumno para entrenadores/admins (solo desktop, mobile-first lo oculta) -->
        <div
            v-if="isTrainerOrAdmin && alumnos.length"
            class="hidden md:flex obs-card p-3 md:p-4 mb-5 md:mb-6 flex-col sm:flex-row sm:items-center gap-3"
        >
            <div class="flex items-center gap-2 text-violet-700 dark:text-violet-300 shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                <span class="text-sm font-bold">Ver historial de:</span>
            </div>
            <select
                v-model="localAlumnoId"
                @change="$emit('alumno-change', localAlumnoId)"
                class="obs-input w-full sm:w-auto sm:min-w-[240px]"
            >
                <option v-for="alumno in alumnos" :key="alumno.id" :value="alumno.id">
                    {{ alumno.name }} ({{ alumno.nick }})
                </option>
            </select>
        </div>

        <!-- Mobile header (mockup exacto) -->
        <header class="md:hidden mb-4 px-1">
            <p class="text-[10px] font-black uppercase tracking-[0.18em] text-violet-600 dark:text-violet-400 mb-1.5">
                SEGUIMIENTO DE PROGRESO
            </p>
            <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight leading-[1.05]">
                Historial de<br />
                Sesiones
            </h1>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400 leading-snug">
                Revisá tu registro de sesiones, volumen acumulado y
                <br class="hidden sm:block" />
                métricas de fatiga RIR en tiempo real.
            </p>
            <!-- CSV/PDF buttons mobile (top-right) -->
            <div v-if="canExport" class="absolute top-4 right-4 flex flex-col gap-2">
                <button
                    @click="$emit('export-csv')"
                    class="w-11 h-11 rounded-2xl flex items-center justify-center bg-violet-100 text-violet-700 border border-violet-200 hover:bg-violet-200 dark:bg-violet-500/15 dark:text-violet-300 dark:border-violet-500/30 dark:hover:bg-violet-500/25 transition-colors"
                    aria-label="Exportar CSV"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m-6-8h2M5 5h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z" />
                    </svg>
                </button>
                <button
                    @click="$emit('export-pdf')"
                    class="w-11 h-11 rounded-2xl flex items-center justify-center bg-violet-100 text-violet-700 border border-violet-200 hover:bg-violet-200 dark:bg-violet-500/15 dark:text-violet-300 dark:border-violet-500/30 dark:hover:bg-violet-500/25 transition-colors"
                    aria-label="Exportar PDF"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m-6-8h2" />
                    </svg>
                </button>
            </div>
        </header>

        <!-- Desktop hero (full Kinetic Obsidian) -->
        <div class="hidden md:block">
            <ObsidianHero
                eyebrow="SEGUIMIENTO DE PROGRESO"
                title="Historial de Sesiones"
                subtitle="Revisá tu registro de sesiones, volumen acumulado y métricas de fatiga RIR en tiempo real."
                class="mb-5 md:mb-7"
            >
                <template #actions>
                    <button
                        v-if="canExport"
                        @click="$emit('export-csv')"
                        class="obs-cta-secondary !bg-white/15 !text-white !border-white/20 hover:!bg-white/25"
                        aria-label="Exportar historial a CSV"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        CSV
                    </button>
                    <button
                        v-if="canExport"
                        @click="$emit('export-pdf')"
                        class="obs-cta-secondary !bg-white/15 !text-white !border-white/20 hover:!bg-white/25"
                        aria-label="Exportar historial a PDF"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m-6-8h2" />
                        </svg>
                        PDF
                    </button>
                </template>
            </ObsidianHero>
        </div>

        <!-- Stats grid mobile-first (mockup exacto: 2 cols mobile, 4 desktop) -->
        <ObsidianStatGrid :stats="formattedStats" class="mb-5 md:mb-7" />

        <!-- Tabs (mobile: 3 tabs del mockup · desktop: todas) -->
        <div class="md:hidden mb-4">
            <ObsidianSegmentedTabs
                :model-value="activeTab"
                :tabs="mobileTabs"
                size="sm"
                @update:model-value="$emit('tab-change', $event)"
            />
        </div>
        <div class="hidden md:block mb-6">
            <ObsidianSegmentedTabs
                :model-value="activeTab"
                :tabs="desktopTabs"
                @update:model-value="$emit('tab-change', $event)"
            />
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import ObsidianHero from '../common/obsidian/ObsidianHero.vue';
import ObsidianStatGrid from '../common/obsidian/ObsidianStatGrid.vue';
import ObsidianSegmentedTabs from '../common/obsidian/ObsidianSegmentedTabs.vue';

const props = defineProps({
    isTrainerOrAdmin: { type: Boolean, required: true },
    alumnos: { type: Array, required: true },
    selectedAlumnoId: { type: [Number, null], default: null },
    activeTab: { type: String, required: true },
    showKeyExercisesTab: { type: Boolean, default: false },
    canExport: { type: Boolean, default: false },
    stats: {
        type: Object,
        required: true,
        // { ejercicios, totalSeries, pesoPromedio, repsPromedio, tonelajeTotal }
    },
});

defineEmits(['alumno-change', 'tab-change', 'export-csv', 'export-pdf']);

const localAlumnoId = ref(props.selectedAlumnoId);

watch(
    () => props.selectedAlumnoId,
    (val) => {
        localAlumnoId.value = val;
    }
);

// Mobile: solo 3 tabs (mockup exacto)
const mobileTabs = computed(() => [
    { id: 'matrix', label: 'Sesiones', icon: '📊' },
    { id: 'evolution', label: 'Matriz Cargas', icon: '📈' },
    { id: 'rir', label: 'Esfuerzo RIR', icon: '💪' },
]);

// Desktop: todas las tabs disponibles
const desktopTabs = computed(() => {
    const base = [
        { id: 'matrix', label: 'Sesiones', icon: '📊' },
        { id: 'evolution', label: 'Matriz Cargas', icon: '📈' },
        { id: 'rir', label: 'Esfuerzo RIR', icon: '💪' },
        { id: 'comparison', label: 'Comparación', icon: '⚖️' },
        { id: 'body_map', label: 'Mapa Corporal', icon: '🧍' },
        { id: 'calendar', label: 'Calendario', icon: '📅' },
        { id: 'rm_calculator', label: 'Estimador 1RM', icon: '🧮' },
    ];
    if (props.showKeyExercisesTab) {
        base.push({ id: 'key_exercises', label: 'Ejercicios Clave', icon: '⭐' });
    }
    return base;
});

const formattedStats = computed(() => [
    {
        label: 'Ejercicios',
        value: props.stats.ejercicios,
        accent: 'violet',
    },
    {
        label: 'Series',
        value: props.stats.totalSeries,
        accent: 'emerald',
        sub: `${props.stats.repsPromedio ?? '0'} reps prom.`,
        trend: '100%',
    },
    {
        label: 'Volumen',
        value: props.stats.tonelajeTotal || '0',
        unit: 'ton',
        accent: 'orange',
    },
    {
        label: 'Peso prom.',
        value: props.stats.pesoPromedio,
        unit: 'kg',
        accent: 'gray',
    },
]);
</script>
