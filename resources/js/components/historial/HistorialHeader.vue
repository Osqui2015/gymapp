<template>
    <div class="space-y-4">
        <!-- Selector de alumno para entrenadores/admins -->
        <div
            v-if="isTrainerOrAdmin && alumnos.length"
            class="bg-[#171b26] p-3.5 rounded-xl border border-slate-800/80 flex flex-col sm:flex-row sm:items-center justify-between gap-3"
        >
            <div class="flex items-center gap-2 text-[#c0c1ff] shrink-0">
                <span class="material-symbols-outlined text-[18px]">group</span>
                <span class="text-xs font-bold">Ver historial de alumno:</span>
            </div>
            <select
                v-model="localAlumnoId"
                @change="$emit('alumno-change', localAlumnoId)"
                class="bg-[#262a35] text-white border border-slate-700/60 rounded-lg px-3 py-1.5 text-xs font-medium focus:ring-1 focus:ring-[#8083ff] focus:border-[#8083ff] outline-none w-full sm:w-auto"
            >
                <option v-for="alumno in alumnos" :key="alumno.id" :value="alumno.id">
                    {{ alumno.name }} ({{ alumno.nick }})
                </option>
            </select>
        </div>

        <!-- Top Header & Export Actions -->
        <div class="flex flex-col gap-2">
            <div class="flex items-start justify-between gap-3">
                <div class="flex-1 min-w-0">
                    <span class="text-[10px] uppercase tracking-widest text-[#c0c1ff] font-bold block mb-1">
                        SEGUIMIENTO DE PROGRESO
                    </span>
                    <h1 class="text-2xl sm:text-3xl text-white font-display font-bold tracking-tight leading-tight">
                        Historial de Sesiones
                    </h1>
                </div>
                <div v-if="canExport" class="flex items-center gap-1.5 shrink-0 pt-1">
                    <button
                        @click="$emit('export-csv')"
                        class="flex items-center gap-1.5 bg-[#262a35] hover:bg-[#313540] px-3 py-1.5 rounded-lg text-white transition-colors active:scale-95 shadow-sm"
                        id="btn-export-csv"
                        type="button"
                        aria-label="Exportar historial a CSV"
                    >
                        <span class="material-symbols-outlined text-[16px] text-[#4edea3]">table_chart</span>
                        <span class="text-xs font-bold uppercase tracking-wider">CSV</span>
                    </button>
                    <button
                        @click="$emit('export-pdf')"
                        class="flex items-center gap-1.5 bg-[#262a35] hover:bg-[#313540] px-3 py-1.5 rounded-lg text-white transition-colors active:scale-95 shadow-sm"
                        id="btn-export-pdf"
                        type="button"
                        aria-label="Exportar historial a PDF"
                    >
                        <span class="material-symbols-outlined text-[16px] text-[#ffb4ab]">picture_as_pdf</span>
                        <span class="text-xs font-bold uppercase tracking-wider">PDF</span>
                    </button>
                </div>
            </div>
            <p class="text-xs text-[#c7c4d7] leading-relaxed">
                Revisa tu registro de sesiones, volumen acumulado y métricas de fatiga RIR en tiempo real.
            </p>
        </div>

        <!-- Global Telemetry KPIs (5 cards exactas del mockup) -->
        <div class="grid grid-cols-2 gap-2 sm:grid-cols-2">
            <!-- 1. Ejercicios -->
            <div class="bg-[#171b26] p-3 sm:p-3.5 rounded-xl flex flex-col justify-between shadow-sm">
                <div class="flex items-center justify-between text-[#c7c4d7]">
                    <span class="text-[10px] font-bold uppercase tracking-wider">EJERCICIOS</span>
                    <span class="material-symbols-outlined text-[16px] text-[#c0c1ff]">fitness_center</span>
                </div>
                <div class="mt-2 flex items-baseline gap-1">
                    <span class="text-2xl font-display font-bold text-white">{{ stats.ejercicios }}</span>
                    <span class="text-xs text-[#c7c4d7]">totales</span>
                </div>
            </div>

            <!-- 2. Series (100% badge) -->
            <div class="bg-[#171b26] p-3 sm:p-3.5 rounded-xl flex flex-col justify-between shadow-sm">
                <div class="flex items-center justify-between text-[#c7c4d7]">
                    <span class="text-[10px] font-bold uppercase tracking-wider">SERIES</span>
                    <span class="inline-flex items-center gap-0.5 text-[#4edea3] text-[11px] font-bold">
                        <span class="material-symbols-outlined text-[13px]">check_circle</span> 100%
                    </span>
                </div>
                <div class="mt-2 flex items-baseline gap-1">
                    <span class="text-2xl font-display font-bold text-[#4edea3]">{{ stats.totalSeries }}</span>
                    <span class="text-xs text-[#c7c4d7]">sets</span>
                </div>
            </div>

            <!-- 3. Volumen -->
            <div class="bg-[#171b26] p-3 sm:p-3.5 rounded-xl flex flex-col justify-between shadow-sm">
                <div class="flex items-center justify-between text-[#c7c4d7]">
                    <span class="text-[10px] font-bold uppercase tracking-wider">Volumen</span>
                    <span class="material-symbols-outlined text-[16px] text-[#d0bcff]">layers</span>
                </div>
                <div class="mt-2 flex items-baseline gap-1">
                    <span class="text-2xl font-display font-bold text-white">{{ displayVolumen }}</span>
                    <span class="text-xs text-[#c7c4d7]">{{ volumenUnit }}</span>
                </div>
            </div>

            <!-- 4. Peso Promedio -->
            <div class="bg-[#171b26] p-3 sm:p-3.5 rounded-xl flex flex-col justify-between shadow-sm">
                <div class="flex items-center justify-between text-[#c7c4d7]">
                    <span class="text-[10px] font-bold uppercase tracking-wider">PESO PROM.</span>
                    <span class="material-symbols-outlined text-[16px] text-[#c7c4d7]">scale</span>
                </div>
                <div class="mt-2 flex items-baseline gap-1">
                    <span class="text-2xl font-display font-bold text-white">{{ stats.pesoPromedio }}</span>
                    <span class="text-xs text-[#c7c4d7]">kg</span>
                </div>
            </div>

            <!-- 5. Repeticiones Promedio (col-span-2) -->
            <div class="col-span-2 bg-[#171b26] p-3 sm:p-3.5 rounded-xl flex items-center justify-between shadow-sm">
                <div class="flex flex-col">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-[#c7c4d7]">
                        REPETICIONES PROMEDIO
                    </span>
                    <span class="text-xs text-[#c7c4d7]">
                        Por serie completada <span class="text-[11px] text-[#8083ff] font-medium">({{ stats.repsPromedio }} reps prom.)</span>
                    </span>
                </div>
                <div class="flex items-baseline gap-1.5">
                    <span class="text-2xl font-display font-bold text-[#c0c1ff]">{{ stats.repsPromedio }}</span>
                    <span class="text-xs text-[#c7c4d7]">reps/set</span>
                </div>
            </div>
        </div>

        <!-- Segmented View Controller (exacto del mockup) -->
        <div class="bg-[#171b26] p-1 rounded-xl flex items-center justify-between text-center gap-1">
            <button
                v-for="t in controllerTabs"
                :key="t.id"
                type="button"
                @click="$emit('tab-change', t.id)"
                :class="[
                    'flex-1 py-2 rounded-lg font-bold text-xs transition-all',
                    activeTab === t.id
                        ? 'bg-[#c0c1ff] text-[#1000a9] shadow-sm'
                        : 'text-[#c7c4d7] hover:text-white'
                ]"
            >
                {{ t.label }}
            </button>
        </div>

        <!-- Pestañas extendidas opcionales para Desktop si no es el trío básico -->
        <div v-if="showDesktopExtendedTabs" class="hidden md:flex flex-wrap gap-1.5 pt-1">
            <button
                v-for="t in extendedTabs"
                :key="t.id"
                type="button"
                @click="$emit('tab-change', t.id)"
                :class="[
                    'px-3 py-1 rounded-lg text-xs font-medium transition-colors border',
                    activeTab === t.id
                        ? 'bg-[#262a35] text-white border-[#8083ff]'
                        : 'bg-[#171b26] text-[#c7c4d7] border-transparent hover:text-white'
                ]"
            >
                {{ t.icon }} {{ t.label }}
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';

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

// 3 tabs principales del mockup: Sesiones (matrix), Matriz Cargas (evolution), Esfuerzo RIR (rir)
const controllerTabs = [
    { id: 'matrix', label: 'Sesiones' },
    { id: 'evolution', label: 'Matriz Cargas' },
    { id: 'rir', label: 'Esfuerzo RIR' },
];

// Tabs complementarias de análisis profundo para vista desktop
const showDesktopExtendedTabs = computed(() => {
    return !['matrix', 'evolution', 'rir'].includes(props.activeTab);
});

const extendedTabs = computed(() => {
    const list = [
        { id: 'comparison', label: 'Comparación', icon: '⚖️' },
        { id: 'body_map', label: 'Mapa Corporal', icon: '🧍' },
        { id: 'calendar', label: 'Calendario', icon: '📅' },
        { id: 'rm_calculator', label: 'Estimador 1RM', icon: '🧮' },
    ];
    if (props.showKeyExercisesTab) {
        list.push({ id: 'key_exercises', label: 'Ejercicios Clave', icon: '⭐' });
    }
    return list;
});

// Limpieza de unidad de tonelaje/volumen para display estético
const displayVolumen = computed(() => {
    const raw = String(props.stats.tonelajeTotal ?? '0');
    // Si contiene valor numérico con formato (ej "1,450 kg" o "14.6 t")
    const match = raw.match(/([\d.,]+)/);
    return match ? match[1] : raw;
});

const volumenUnit = computed(() => {
    const raw = String(props.stats.tonelajeTotal ?? '').toLowerCase();
    if (raw.includes('kg')) return 'kg';
    return 'ton';
});
</script>
