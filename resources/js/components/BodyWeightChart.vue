<!--
  BodyWeightChart — Chart de evolución de peso con línea punteada del goal.

  Basado en la pantalla "Home" de openGym: peso actual grande, flecha verde/roja
  según dirección, delta al goal, y chart con línea punteada del objetivo.

  Props:
    - data:     [{ fecha, peso }]  histórico ordenado por fecha
    - goal:     number | null      peso objetivo en kg
    - latest:   { peso, fecha } | null
    - delta:    number | null      diferencia peso - goal (positivo = sobre goal)
    - direction: 'down' | 'up' | null  'down' = goal es perder peso, 'up' = goal es ganar

  Eventos:
    - update:goal  (newValue)  cuando el usuario edita el peso objetivo
-->
<template>
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6 shadow-sm">
        <!-- Header con peso actual + edit del goal -->
        <div class="flex flex-wrap items-start justify-between gap-4 mb-5">
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Peso corporal</p>
                <div v-if="latest" class="flex items-baseline gap-2 mt-1">
                    <span class="text-4xl font-black text-gray-900 dark:text-white">{{ latest.peso.toFixed(1) }}</span>
                    <span class="text-lg font-semibold text-gray-500">kg</span>
                    <span
                        v-if="totalChange !== null"
                        :class="['text-sm font-semibold ml-2', totalChange < 0 ? 'text-emerald-600' : totalChange > 0 ? 'text-rose-600' : 'text-gray-500']"
                    >
                        {{ totalChange > 0 ? '+' : '' }}{{ totalChange.toFixed(1) }} kg
                    </span>
                </div>
                <p v-if="!latest" class="text-sm text-gray-500 dark:text-gray-400 mt-1">Sin registros aún</p>
                <p v-if="latest" class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ formatDate(latest.fecha) }}</p>
            </div>

            <div class="flex flex-col items-end gap-1">
                <label class="text-xs text-gray-500 dark:text-gray-400">Peso objetivo (kg)</label>
                <div class="flex items-center gap-1">
                    <input
                        v-model.number="goalLocal"
                        type="number"
                        step="0.1"
                        min="30"
                        max="300"
                        class="w-20 px-2 py-1 text-right text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                        @blur="onGoalBlur"
                        @keyup.enter="onGoalBlur"
                    />
                    <button
                        v-if="goalLocal !== goal"
                        type="button"
                        @click="onGoalBlur"
                        class="text-xs px-2 py-1 bg-indigo-600 text-white rounded hover:bg-indigo-700"
                    >
                        Guardar
                    </button>
                </div>
                <p v-if="delta !== null" class="text-xs font-semibold mt-1" :class="deltaClass">
                    <span v-if="direction === 'down' && delta < 0">↓ {{ Math.abs(delta).toFixed(1) }} kg perdido</span>
                    <span v-else-if="direction === 'down' && delta > 0">{{ delta.toFixed(1) }} kg por perder</span>
                    <span v-else-if="direction === 'up' && delta > 0">↑ {{ delta.toFixed(1) }} kg ganado</span>
                    <span v-else-if="direction === 'up' && delta < 0">{{ Math.abs(delta).toFixed(1) }} kg por ganar</span>
                    <span v-else-if="delta === 0">¡Objetivo logrado!</span>
                </p>
            </div>
        </div>

        <!-- Chart (Chart.js, lazy-loaded) -->
        <div v-if="chartData.length > 0" class="h-64 w-full">
            <canvas ref="chartCanvas"></canvas>
        </div>

        <div v-else class="text-center py-12 text-gray-400 text-sm">
            <p>No hay registros de peso todavía.</p>
            <p class="mt-1 text-xs">Anotá tu primer peso en la sección de Progreso y vas a ver tu evolución acá.</p>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue';

const props = defineProps({
    data: { type: Array, default: () => [] },
    goal: { type: Number, default: null },
    latest: { type: Object, default: null },
    delta: { type: Number, default: null },
    direction: { type: String, default: null },
    totalChange: { type: Number, default: null },
});

const emit = defineEmits(['update:goal']);

const goalLocal = ref(props.goal);
const chartCanvas = ref(null);
let chartInstance = null;
let chartConstructor = null;

watch(() => props.goal, (v) => { goalLocal.value = v; });

const chartData = computed(() => {
    return props.data.map(d => ({
        fecha: d.fecha,
        peso: d.peso,
    }));
});

const yDomain = computed(() => {
    if (chartData.value.length === 0) return [0, 100];
    const pesos = chartData.value.map(d => d.peso);
    if (props.goal) pesos.push(props.goal);
    const min = Math.min(...pesos);
    const max = Math.max(...pesos);
    const range = max - min;
    const padding = range > 0 ? range * 0.1 : 2;
    return [Math.max(0, Math.floor(min - padding)), Math.ceil(max + padding)];
});

const deltaClass = computed(() => {
    if (props.delta === null) return 'text-gray-500';
    if (props.delta === 0) return 'text-emerald-600';
    if (props.direction === 'down' && props.delta < 0) return 'text-emerald-600';
    if (props.direction === 'up' && props.delta > 0) return 'text-emerald-600';
    return 'text-rose-600';
});

function formatDate(iso) {
    if (!iso) return '';
    const d = new Date(iso + 'T00:00:00');
    return new Intl.DateTimeFormat('es-MX', { day: '2-digit', month: 'short', year: 'numeric' }).format(d);
}

function formatTick(iso) {
    if (!iso) return '';
    const d = new Date(iso + 'T00:00:00');
    return new Intl.DateTimeFormat('es-MX', { month: 'short', year: '2-digit' }).format(d);
}

function onGoalBlur() {
    if (goalLocal.value !== props.goal && goalLocal.value > 0) {
        emit('update:goal', goalLocal.value);
    } else if (!goalLocal.value) {
        emit('update:goal', null);
    }
}

function buildChart() {
    if (!chartCanvas.value || chartData.value.length === 0) return;

    const labels = chartData.value.map(d => formatTick(d.fecha));
    const pesos = chartData.value.map(d => d.peso);

    // Línea horizontal del goal: misma cantidad de puntos que el dataset principal
    const goalLine = props.goal
        ? labels.map(() => props.goal)
        : null;

    const datasets = [
        {
            label: 'Peso (kg)',
            data: pesos,
            borderColor: '#4f46e5',
            backgroundColor: '#4f46e5',
            borderWidth: 2.5,
            pointRadius: 3,
            pointHoverRadius: 5,
            tension: 0.3,
            fill: false,
        },
    ];

    if (goalLine) {
        datasets.push({
            label: `Objetivo: ${props.goal} kg`,
            data: goalLine,
            borderColor: '#a3e635',
            backgroundColor: 'transparent',
            borderWidth: 2,
            borderDash: [6, 4],
            pointRadius: 0,
            pointHoverRadius: 0,
            fill: false,
        });
    }

    const config = {
        type: 'line',
        data: { labels, datasets },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: false,
            interaction: { mode: 'index', intersect: false },
            scales: {
                x: {
                    ticks: { color: '#9ca3af', font: { size: 11 } },
                    grid: { display: false },
                },
                y: {
                    min: yDomain.value[0],
                    max: yDomain.value[1],
                    ticks: { color: '#9ca3af', font: { size: 11 } },
                    grid: { color: 'rgba(229, 231, 235, 0.4)' },
                },
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1f2937',
                    titleColor: '#a3e635',
                    bodyColor: '#fff',
                    borderColor: 'transparent',
                    borderWidth: 0,
                    cornerRadius: 8,
                    padding: 8,
                    titleFont: { weight: 'bold' },
                    bodyFont: { size: 12 },
                    callbacks: {
                        label: (ctx) => `${ctx.parsed.y} kg`,
                    },
                },
            },
        },
    };

    if (chartInstance) {
        chartInstance.destroy();
    }
        chartInstance = new chartConstructor(chartCanvas.value, config);
}

async function initChart() {
    // Lazy-load: chart.js vive en vendor-chart (cargado por otros componentes).
    const { Chart, registerables } = await import('chart.js');
    Chart.register(...registerables);
    chartConstructor = Chart;
    await nextTick();
    buildChart();
}

onMounted(() => {
    if (chartData.value.length > 0) {
        initChart();
    }
});

onBeforeUnmount(() => {
    if (chartInstance) {
        chartInstance.destroy();
        chartInstance = null;
    }
});

// Re-render cuando cambian los datos, el goal, o el dominio Y
watch(
    [chartData, () => props.goal, yDomain],
    () => {
        if (chartData.value.length > 0) {
            if (chartInstance) {
                buildChart();
            } else {
                initChart();
            }
        }
    },
    { deep: true }
);
</script>
