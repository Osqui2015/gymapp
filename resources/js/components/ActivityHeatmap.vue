<!--
  ActivityHeatmap — Heatmap estilo GitHub contribution graph.

  Muestra 53 semanas × 7 días (un año). Cada celda es un día, con color
  según la cantidad de sets completados.

  Props:
    - data: { from, to, days: [{ fecha, sets, volumen }] }
-->
<template>
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm">
        <div class="flex items-center justify-between mb-3">
            <div>
                <h3 class="text-sm font-bold text-gray-900 dark:text-white">Actividad</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                    Sets completados por día en el último año
                </p>
            </div>
            <div class="flex items-center gap-1.5 text-[10px] text-gray-500 dark:text-gray-400">
                <span>Menos</span>
                <div v-for="i in 5" :key="i" :class="['w-2.5 h-2.5 rounded-sm', colorFor(i - 1)]"></div>
                <span>Más</span>
            </div>
        </div>

        <div class="overflow-x-auto pb-2">
            <div class="inline-grid grid-flow-col gap-1" :style="{ gridTemplateRows: 'repeat(7, 1fr)' }">
                <template v-for="(week, wi) in weeks" :key="wi">
                    <div
                        v-for="day in week"
                        :key="day.fecha || `empty-${wi}-${day.col}`"
                        :class="[
                            'w-3 h-3 rounded-sm transition-all cursor-default',
                            day.fecha ? colorFor(day.level) : 'bg-transparent',
                            hover && 'hover:ring-1 hover:ring-indigo-500'
                        ]"
                        :title="day.fecha ? `${day.fecha}: ${day.sets} sets, ${day.volumen?.toFixed(0) ?? 0} kg` : ''"
                    ></div>
                </template>
            </div>
        </div>

        <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
            <span>{{ formatDate(data?.from) }} → {{ formatDate(data?.to) }}</span>
            <span v-if="data?.total_sets">{{ data.total_sets.toLocaleString() }} sets en el año</span>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    data: { type: Object, default: () => ({ days: [] }) },
    hover: { type: Boolean, default: true },
});

// Armar la grilla de 53 semanas x 7 días
const weeks = computed(() => {
    if (!props.data?.days?.length) return [];

    // Map fecha -> { sets, volumen }
    const byDate = {};
    for (const d of props.data.days) {
        byDate[d.fecha] = { sets: d.sets, volumen: d.volumen };
    }

    // Calcular el primer domingo y la cantidad de semanas
    const from = new Date(props.data.from + 'T00:00:00');
    const to = new Date(props.data.to + 'T00:00:00');
    const firstSunday = new Date(from);
    firstSunday.setDate(firstSunday.getDate() - firstSunday.getDay());  // 0=Dom, 1=Lun, ...

    // Encontrar max de sets para escalar color
    const maxSets = Math.max(1, ...Object.values(byDate).map(d => d.sets));

    const result = [];
    const cursor = new Date(firstSunday);
    while (cursor <= to) {
        const week = [];
        for (let dow = 0; dow < 7; dow++) {
            const isoDate = cursor.toISOString().slice(0, 10);
            const inRange = cursor >= from && cursor <= to;
            const entry = inRange ? byDate[isoDate] : null;
            const sets = entry?.sets ?? 0;
            const level = sets === 0 ? 0 : Math.min(4, Math.ceil((sets / maxSets) * 4));
            week.push({
                col: dow,
                fecha: inRange ? isoDate : null,
                sets,
                volumen: entry?.volumen ?? 0,
                level,
            });
            cursor.setDate(cursor.getDate() + 1);
        }
        result.push(week);
    }
    return result;
});

function colorFor(level) {
    if (level === 0) return 'bg-gray-100 dark:bg-gray-700';
    if (level === 1) return 'bg-emerald-200 dark:bg-emerald-900/60';
    if (level === 2) return 'bg-emerald-400 dark:bg-emerald-700';
    if (level === 3) return 'bg-emerald-500 dark:bg-emerald-500';
    if (level === 4) return 'bg-emerald-700 dark:bg-emerald-400';
    return 'bg-gray-100';
}

function formatDate(iso) {
    if (!iso) return '';
    const d = new Date(iso + 'T00:00:00');
    return new Intl.DateTimeFormat('es-MX', { month: 'short', year: 'numeric' }).format(d);
}
</script>
