<template>
  <div v-if="hasData" class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm">
    <div class="flex items-center justify-between mb-3">
      <div>
        <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">📈 Peso máximo por día (últimos 30 días)</p>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Tu PR diario a lo largo del tiempo</p>
      </div>
      <div class="flex items-center gap-3 text-xs">
        <div class="flex items-center gap-1.5">
          <div class="w-3 h-3 rounded-sm bg-gradient-to-br from-indigo-500 to-purple-500"></div>
          <span class="text-gray-600 dark:text-gray-400">Peso (kg)</span>
        </div>
      </div>
    </div>
    <div class="relative" style="height: 220px;">
      <canvas ref="chartCanvas"></canvas>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, nextTick, onBeforeUnmount } from 'vue';
import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

const props = defineProps({
    historial: { type: Array, default: () => [] },
});

const chartCanvas = ref(null);
let chartInstance = null;

// Agrupa por día y obtiene el peso máximo registrado ese día
const datosAgrupados = computed(() => {
    const porDia = new Map();
    props.historial.forEach((row) => {
        const fecha = row.fecha || (row.created_at ? row.created_at.split('T')[0] : null);
        if (!fecha) return;
        const peso = Number(row.peso) || 0;
        if (peso <= 0) return;
        if (!porDia.has(fecha) || peso > porDia.get(fecha)) {
            porDia.set(fecha, peso);
        }
    });

    // Ordenar por fecha ascendente y tomar últimos 30 días
    const sorted = [...porDia.entries()].sort((a, b) => a[0].localeCompare(b[0]));
    const ultimos = sorted.slice(-30);

    return {
        // Formateamos el label a dd/mm/yyyy acá para que el chart SIEMPRE
        // muestre fecha legible, sin importar si el backend mandó ISO o
        // string 'yyyy-mm-dd'. Si la fecha viene en formato ISO con
        // timestamp (ej: '2026-09-03T00:00:00.000000Z'), el split
        // normaliza a 'yyyy-mm-dd' antes de pasarlo a Date.
        labels: ultimos.map(([fecha]) => {
            const iso = typeof fecha === 'string' && fecha.includes('T') ? fecha.split('T')[0] : fecha;
            const [y, m, d] = iso.split('-').map(Number);
            return `${String(d).padStart(2, '0')}/${String(m).padStart(2, '0')}/${y}`;
        }),
        pesos: ultimos.map(([, peso]) => peso),
    };
});

const hasData = computed(() => datosAgrupados.value.pesos.length > 0);

const renderChart = () => {
    if (!chartCanvas.value || !hasData.value) return;
    const { labels, pesos } = datosAgrupados.value;

    if (chartInstance) {
        chartInstance.data.labels = labels;
        chartInstance.data.datasets[0].data = pesos;
        chartInstance.update();
        return;
    }

    const ctx = chartCanvas.value.getContext('2d');
    const gradient = ctx.createLinearGradient(0, 0, 0, 220);
    gradient.addColorStop(0, 'rgba(99, 102, 241, 0.4)');
    gradient.addColorStop(1, 'rgba(99, 102, 241, 0.0)');

    chartInstance = new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: 'Peso (kg)',
                data: pesos,
                borderColor: '#6366f1',
                backgroundColor: gradient,
                borderWidth: 2.5,
                tension: 0.35,
                fill: true,
                pointBackgroundColor: '#6366f1',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(17, 24, 39, 0.95)',
                    padding: 10,
                    cornerRadius: 8,
                    callbacks: {
                        label: (ctx) => ` ${ctx.parsed.y} kg`,
                    },
                },
            },
            scales: {
                x: {
                    ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 7 },
                    grid: { display: false },
                },
                y: {
                    beginAtZero: false,
                    ticks: { callback: (val) => `${val} kg` },
                    grid: { color: 'rgba(107, 114, 128, 0.1)' },
                },
            },
        },
    });
};

watch(() => props.historial, () => nextTick(renderChart), { deep: true });

onMounted(() => nextTick(renderChart));

onBeforeUnmount(() => {
    if (chartInstance) { chartInstance.destroy(); chartInstance = null; }
});
</script>