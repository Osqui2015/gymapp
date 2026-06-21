<template>
  <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-lg overflow-hidden">
    <div class="px-6 py-4 bg-gradient-to-r from-slate-900 to-indigo-900 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
      <h2 class="text-lg font-bold text-white flex items-center gap-2">
        <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
        Calendario de entrenamientos
      </h2>
      <div class="text-xs text-slate-300">
        Últimas {{ weeks }} semanas
      </div>
    </div>

    <div class="p-6">
      <!-- Leyenda -->
      <div class="flex items-center justify-between mb-4 text-xs text-gray-500 dark:text-gray-400">
        <span>{{ formatDate(weeksAgo[0]?.date) }} – {{ formatDate(todayLabel) }}</span>
        <div class="flex items-center gap-2">
          <span>Menos</span>
          <div class="flex gap-1">
            <div class="w-3 h-3 rounded-sm bg-gray-100 dark:bg-gray-700"></div>
            <div class="w-3 h-3 rounded-sm bg-indigo-200 dark:bg-indigo-900"></div>
            <div class="w-3 h-3 rounded-sm bg-indigo-400 dark:bg-indigo-700"></div>
            <div class="w-3 h-3 rounded-sm bg-indigo-600 dark:bg-indigo-500"></div>
            <div class="w-3 h-3 rounded-sm bg-indigo-800 dark:bg-indigo-300"></div>
          </div>
          <span>Más</span>
        </div>
      </div>

      <!-- Heatmap grid: 7 días × N semanas -->
      <div class="overflow-x-auto">
        <div class="inline-flex gap-1.5">
          <!-- Labels de días -->
          <div class="flex flex-col gap-1.5 text-[10px] text-gray-500 dark:text-gray-400 font-medium pt-3.5">
            <div v-for="dia in diasSemana" :key="dia" class="h-3.5 flex items-center">
              {{ dia }}
            </div>
          </div>

          <!-- Columnas por semana -->
          <div
            v-for="(semana, sIdx) in heatmap"
            :key="sIdx"
            class="flex flex-col gap-1.5"
          >
            <div class="text-[10px] text-gray-400 text-center h-3.5 leading-3.5">
              {{ semana[0]?.labelMes || '' }}
            </div>
            <div
              v-for="(dia, dIdx) in semana"
              :key="dIdx"
              class="w-3.5 h-3.5 rounded-sm transition-all"
              :class="dia.color + (dia.futuro ? ' opacity-30' : ' hover:ring-2 hover:ring-indigo-400 cursor-pointer')"
              :title="dia.futuro ? '' : `${formatDate(dia.date)}: ${dia.count} ${dia.count === 1 ? 'serie' : 'series'}`"
            ></div>
          </div>
        </div>
      </div>

      <!-- Resumen -->
      <div class="mt-6 grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="text-center p-3 bg-gray-50 dark:bg-gray-900 rounded-lg">
          <p class="text-2xl font-black text-indigo-600 dark:text-indigo-400">{{ totalSeries }}</p>
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Series totales</p>
        </div>
        <div class="text-center p-3 bg-gray-50 dark:bg-gray-900 rounded-lg">
          <p class="text-2xl font-black text-emerald-600 dark:text-emerald-400">{{ diasEntrenados }}</p>
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Días entrenados</p>
        </div>
        <div class="text-center p-3 bg-gray-50 dark:bg-gray-900 rounded-lg">
          <p class="text-2xl font-black text-amber-600 dark:text-amber-400">{{ rachaActual }}</p>
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Racha actual (días)</p>
        </div>
        <div class="text-center p-3 bg-gray-50 dark:bg-gray-900 rounded-lg">
          <p class="text-2xl font-black text-rose-600 dark:text-rose-400">{{ rachaMax }}</p>
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Mejor racha (días)</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    historial: { type: Array, default: () => [] },
    weeks: { type: Number, default: 12 },
});

const diasSemana = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];

const today = new Date();
today.setHours(0, 0, 0, 0);
const todayLabel = today.toISOString().split('T')[0];

// Encontrar el lunes de la semana actual (hace 0-X semanas)
const startOfCurrentWeek = new Date(today);
const dayOfWeek = (startOfCurrentWeek.getDay() + 6) % 7; // 0 = lunes
startOfCurrentWeek.setDate(startOfCurrentWeek.getDate() - dayOfWeek);

// Construir los conteos por día desde el historial
const countsByDate = computed(() => {
    const map = {};
    props.historial.forEach(reg => {
        const fecha = reg.fecha || (reg.created_at ? reg.created_at.split('T')[0] : null);
        if (!fecha) return;
        map[fecha] = (map[fecha] || 0) + 1;
    });
    return map;
});

const maxCount = computed(() => Math.max(1, ...Object.values(countsByDate.value)));

// weeksAgo es la lista de fechas para cada celda (de más antiguo a más reciente)
const weeksAgo = computed(() => {
    const result = [];
    for (let w = props.weeks - 1; w >= 0; w--) {
        const week = [];
        for (let d = 0; d < 7; d++) {
            const date = new Date(startOfCurrentWeek);
            date.setDate(startOfCurrentWeek.getDate() - w * 7 + d);
            const iso = date.toISOString().split('T')[0];
            const isFuture = date > today;
            const count = countsByDate.value[iso] || 0;
            const ratio = count / maxCount.value;

            let color = 'bg-gray-100 dark:bg-gray-700';
            if (count > 0) {
                if (ratio < 0.25) color = 'bg-indigo-200 dark:bg-indigo-900';
                else if (ratio < 0.5) color = 'bg-indigo-400 dark:bg-indigo-700';
                else if (ratio < 0.75) color = 'bg-indigo-600 dark:bg-indigo-500';
                else color = 'bg-indigo-800 dark:bg-indigo-300';
            }

            week.push({
                date: iso,
                count,
                color,
                futuro: isFuture,
                labelMes: d === 0 ? date.toLocaleDateString('es-ES', { month: 'short' }) : '',
            });
        }
        result.push(week);
    }
    return result;
});

// Reorganizar por columnas (semanas)
const heatmap = computed(() => weeksAgo.value);

const formatDate = (iso) => {
    if (!iso) return '';
    const d = new Date(iso + 'T00:00:00');
    return d.toLocaleDateString('es-ES', { day: '2-digit', month: 'short' });
};

// Stats
const totalSeries = computed(() => Object.values(countsByDate.value).reduce((a, b) => a + b, 0));
const diasEntrenados = computed(() => Object.values(countsByDate.value).filter(c => c > 0).length);

// Calcular racha actual y mejor racha
const rachaActual = computed(() => {
    let racha = 0;
    const checkDate = new Date(today);
    while (true) {
        const iso = checkDate.toISOString().split('T')[0];
        if (countsByDate.value[iso]) {
            racha++;
            checkDate.setDate(checkDate.getDate() - 1);
        } else {
            break;
        }
    }
    return racha;
});

const rachaMax = computed(() => {
    let max = 0;
    let current = 0;
    const sortedDates = Object.keys(countsByDate.value).sort();
    if (sortedDates.length === 0) return 0;

    let prevDate = null;
    for (const iso of sortedDates) {
        const d = new Date(iso + 'T00:00:00');
        if (prevDate && (d - prevDate) / (1000 * 60 * 60 * 24) === 1) {
            current++;
        } else {
            current = 1;
        }
        max = Math.max(max, current);
        prevDate = d;
    }
    return max;
});
</script>
