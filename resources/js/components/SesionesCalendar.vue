<!--
  SesionesCalendar — vista mensual con grid de 7 columnas (L-D).
  Cada celda muestra:
    - Número del día
    - Si hay sesión: cantidad de ejercicios / series
    - Color: intensidad según la actividad
    - Click: detalle del día (emite evento)
-->
<template>
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-5">
        <!-- Header con navegación de mes -->
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white capitalize">
                {{ monthName }} {{ year }}
            </h3>
            <div class="flex items-center gap-1">
                <button
                    @click="prevMonth"
                    class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                    aria-label="Mes anterior"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>
                <button
                    @click="goToday"
                    class="px-3 py-1 text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-950/30 rounded-lg transition-colors"
                >
                    Hoy
                </button>
                <button
                    @click="nextMonth"
                    class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                    aria-label="Mes siguiente"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="flex items-center justify-center py-12 text-sm text-gray-500">
            <svg class="animate-spin w-5 h-5 mr-2 text-indigo-600" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            Cargando sesiones...
        </div>

        <!-- Grid del calendario -->
        <div v-else>
            <!-- Headers de días -->
            <div class="grid grid-cols-7 gap-1 mb-2">
                <div
                    v-for="dia in dayNames"
                    :key="dia"
                    class="text-center text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider py-1"
                >
                    {{ dia }}
                </div>
            </div>

            <!-- Celdas -->
            <div class="grid grid-cols-7 gap-1">
                <div
                    v-for="(cell, idx) in cells"
                    :key="idx"
                    :class="[
                        'relative aspect-square rounded-lg flex flex-col items-center justify-center text-sm transition-all cursor-pointer',
                        cell.empty
                            ? 'cursor-default'
                            : cell.future
                                ? 'text-gray-400 dark:text-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700/30'
                                : cell.hasSession
                                    ? `${intensityClass(cell.count)} hover:scale-105 shadow-sm`
                                    : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/30',
                        cell.today ? 'ring-2 ring-indigo-500 ring-offset-1 dark:ring-offset-gray-800' : '',
                    ]"
                    @click="!cell.empty && selectDay(cell)"
                >
                    <span class="font-semibold">{{ cell.day }}</span>
                    <span
                        v-if="cell.hasSession"
                        class="text-[10px] font-bold opacity-90"
                    >
                        {{ cell.count }} ej
                    </span>
                </div>
            </div>

            <!-- Leyenda -->
            <div class="flex items-center justify-end gap-3 mt-4 text-[10px] text-gray-500 dark:text-gray-400">
                <span class="flex items-center gap-1">
                    <span class="w-2 h-2 rounded-sm bg-gray-200 dark:bg-gray-700"></span>
                    Sin sesión
                </span>
                <span class="flex items-center gap-1">
                    <span class="w-2 h-2 rounded-sm bg-emerald-200"></span>
                    1-2 ej
                </span>
                <span class="flex items-center gap-1">
                    <span class="w-2 h-2 rounded-sm bg-emerald-400"></span>
                    3-5 ej
                </span>
                <span class="flex items-center gap-1">
                    <span class="w-2 h-2 rounded-sm bg-emerald-600"></span>
                    6+ ej
                </span>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
    userId: { type: Number, default: null }, // si es null, usa el user logueado
});
const emit = defineEmits(['select-day']);

const dayNames = ['L', 'M', 'X', 'J', 'V', 'S', 'D'];

const today = new Date();
const year = ref(today.getFullYear());
const month = ref(today.getMonth() + 1); // 1-12
const loading = ref(false);
const counts = ref({}); // { '2026-08-15': 3 }

const monthName = computed(() => {
    const meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
    return meses[month.value - 1];
});

const cells = computed(() => {
    const first = new Date(year.value, month.value - 1, 1);
    const last = new Date(year.value, month.value, 0);
    const daysInMonth = last.getDate();
    // JS: 0=Dom, 1=Lun, ..., 6=Sáb. Convertimos a L=0
    const startOffset = (first.getDay() + 6) % 7;

    const cells = [];

    // Celdas vacías antes del primer día
    for (let i = 0; i < startOffset; i++) {
        cells.push({ empty: true });
    }

    // Celdas de los días del mes
    for (let d = 1; d <= daysInMonth; d++) {
        const dateStr = `${year.value}-${String(month.value).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
        const count = counts.value[dateStr] || 0;
        const isToday = d === today.getDate() &&
                        month.value === (today.getMonth() + 1) &&
                        year.value === today.getFullYear();
        const isFuture = new Date(year.value, month.value - 1, d) > today;

        cells.push({
            day: d,
            date: dateStr,
            count,
            hasSession: count > 0,
            today: isToday,
            future: isFuture,
        });
    }

    return cells;
});

const intensityClass = (count) => {
    if (count <= 0) return 'bg-gray-100 dark:bg-gray-700/50 text-gray-500';
    if (count <= 2) return 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-200';
    if (count <= 5) return 'bg-emerald-300 dark:bg-emerald-700/50 text-emerald-900 dark:text-emerald-100';
    return 'bg-emerald-500 dark:bg-emerald-600 text-white';
};

const loadMonth = async () => {
    loading.value = true;
    try {
        const params = { year: year.value, month: month.value };
        if (props.userId) params.user_id = props.userId;
        const { data } = await axios.get('/api/historial/calendar', { params });
        counts.value = data.counts || {};
    } catch (e) {
        console.error('[calendar] load error:', e);
        counts.value = {};
    } finally {
        loading.value = false;
    }
};

const prevMonth = () => {
    if (month.value === 1) {
        month.value = 12;
        year.value -= 1;
    } else {
        month.value -= 1;
    }
};

const nextMonth = () => {
    if (month.value === 12) {
        month.value = 1;
        year.value += 1;
    } else {
        month.value += 1;
    }
};

const goToday = () => {
    const now = new Date();
    year.value = now.getFullYear();
    month.value = now.getMonth() + 1;
};

const selectDay = (cell) => {
    if (cell.future) return;
    emit('select-day', cell);
};

watch([year, month], () => loadMonth());

onMounted(loadMonth);
</script>
