<template>
  <div class="space-y-6">
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6 shadow-sm">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-4">
        <div>
          <h3 class="text-xl font-bold text-gray-900 dark:text-white">⚖️ Comparación de pesos por ejercicio</h3>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            Cuánto levantabas al principio vs. cuánto levantás ahora. ¡Mirá tu evolución!
          </p>
        </div>
      </div>

      <!-- Selector de ejercicio -->
      <div v-if="ejerciciosDisponibles.length > 1" class="mb-4">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Ejercicio a comparar</label>
        <select
          v-model="ejercicioSeleccionado"
          class="w-full sm:w-96 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500"
        >
          <option v-for="ej in ejerciciosDisponibles" :key="ej" :value="ej">{{ ej }}</option>
        </select>
      </div>

      <div v-if="!ejercicioSeleccionado" class="text-center py-12 text-gray-500 dark:text-gray-400">
        Seleccioná un ejercicio para ver la comparación.
      </div>

      <div v-else>
        <!-- Cards "antes vs después" -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
          <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 p-5">
            <p class="text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400 font-semibold">Primer registro</p>
            <p class="mt-2 text-3xl font-bold text-gray-700 dark:text-gray-200">{{ comparacion.primero.peso }}<span class="text-base font-normal text-gray-500"> kg</span></p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ comparacion.primero.fecha }}</p>
          </div>

          <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-gradient-to-br from-indigo-50 to-purple-50 dark:from-indigo-950/30 dark:to-purple-950/30 p-5">
            <p class="text-xs uppercase tracking-wider text-indigo-600 dark:text-indigo-400 font-semibold">Último registro</p>
            <p class="mt-2 text-3xl font-bold text-indigo-700 dark:text-indigo-300">{{ comparacion.ultimo.peso }}<span class="text-base font-normal"> kg</span></p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ comparacion.ultimo.fecha }}</p>
          </div>

          <div :class="[
            'rounded-xl border p-5',
            comparacion.diferencia > 0
              ? 'border-green-300 dark:border-green-700 bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-950/30 dark:to-emerald-950/30'
              : comparacion.diferencia < 0
                ? 'border-red-300 dark:border-red-700 bg-gradient-to-br from-red-50 to-rose-50 dark:from-red-950/30 dark:to-rose-950/30'
                : 'border-gray-200 dark:border-gray-700 bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800'
          ]">
            <p class="text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400 font-semibold">Cambio total</p>
            <p :class="[
              'mt-2 text-3xl font-bold',
              comparacion.diferencia > 0 ? 'text-green-600 dark:text-green-400'
                : comparacion.diferencia < 0 ? 'text-red-600 dark:text-red-400'
                : 'text-gray-700 dark:text-gray-300'
            ]">
              {{ comparacion.diferencia > 0 ? '+' : '' }}{{ comparacion.diferencia.toFixed(1) }}<span class="text-base font-normal"> kg</span>
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
              {{ comparacion.porcentaje > 0 ? '+' : '' }}{{ comparacion.porcentaje.toFixed(1) }}% en {{ comparacion.diasEntre }} días
            </p>
          </div>
        </div>

        <!-- Gráfico -->
        <div class="relative" style="height: 320px;">
          <canvas ref="chartCanvas"></canvas>
        </div>

        <!-- Records del ejercicio -->
        <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-3">
            <p class="text-xs text-gray-500 dark:text-gray-400">Mejor peso (PR)</p>
            <p class="mt-1 text-lg font-bold text-emerald-600 dark:text-emerald-400">{{ stats.pr.peso }} kg × {{ stats.pr.reps }} reps</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ stats.pr.fecha }}</p>
          </div>
          <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-3">
            <p class="text-xs text-gray-500 dark:text-gray-400">Total de series registradas</p>
            <p class="mt-1 text-lg font-bold text-gray-900 dark:text-white">{{ stats.total }}</p>
          </div>
        </div>

        <!-- Comparación con la comunidad -->
        <div class="mt-6 rounded-lg border border-purple-200 dark:border-purple-800 bg-gradient-to-br from-purple-50 to-indigo-50 dark:from-purple-950/30 dark:to-indigo-950/30 p-4">
          <div class="flex items-center gap-2 mb-3">
            <span class="text-2xl">🌎</span>
            <h4 class="font-bold text-gray-900 dark:text-white">Comparación con la comunidad</h4>
          </div>
          <div v-if="comunidad.cargando" class="text-sm text-gray-500 dark:text-gray-400 py-3">
            <svg class="animate-spin w-4 h-4 inline-block mr-2" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            Cargando datos de la comunidad...
          </div>
          <div v-else-if="comunidad.error" class="text-sm text-red-600 dark:text-red-400 py-2">
            {{ comunidad.error }}
          </div>
          <div v-else-if="comunidad.total_usuarios > 0">
            <div class="mb-3">
              <p class="text-sm text-gray-700 dark:text-gray-300">
                De <strong>{{ comunidad.total_usuarios }}</strong> usuarios que entrenan este ejercicio, estás en el
                <strong :class="rankingClass">
                  {{ rankingTexto }}
                </strong>
                ({{ comunidad.percentiles.p50 }} kg es la mediana de la comunidad).
              </p>
            </div>
            <!-- Barra visual de percentiles -->
            <div class="relative h-8 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
              <div class="absolute inset-y-0 left-0 bg-gradient-to-r from-purple-400 to-indigo-500" :style="{ width: `${posicionBarra(comunidad.percentiles.p25)}%` }"></div>
              <div class="absolute inset-y-0 left-0 bg-gradient-to-r from-purple-500 to-indigo-600 opacity-90" :style="{ width: `${posicionBarra(comunidad.percentiles.p50)}%` }"></div>
              <div class="absolute inset-y-0 left-0 bg-gradient-to-r from-purple-600 to-indigo-700" :style="{ width: `${posicionBarra(comunidad.percentiles.p75)}%` }"></div>
              <!-- Marcador del usuario -->
              <div class="absolute top-0 bottom-0 w-1 bg-yellow-400 shadow-lg" :style="{ left: `${posicionBarra(comparacion.ultimo.peso)}%` }">
                <div class="absolute -top-1 left-1/2 -translate-x-1/2 w-2 h-2 bg-yellow-400 rounded-full"></div>
              </div>
            </div>
            <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mt-1">
              <span>p25: {{ comunidad.percentiles.p25 }} kg</span>
              <span>p50: {{ comunidad.percentiles.p50 }} kg</span>
              <span>p75: {{ comunidad.percentiles.p75 }} kg</span>
              <span>p90: {{ comunidad.percentiles.p90 }} kg</span>
            </div>
          </div>
          <div v-else class="text-sm text-gray-500 dark:text-gray-400 py-2">
            Aún no hay suficientes datos de la comunidad para este ejercicio.
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, nextTick, onBeforeUnmount } from 'vue';
import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

const props = defineProps({
    historial: { type: Array, required: true },
});

const ejercicioSeleccionado = ref('');
const chartCanvas = ref(null);
let chartInstance = null;

// Datos de la comunidad para el ejercicio seleccionado
const comunidad = ref({ cargando: false, error: null, total_usuarios: 0, percentiles: {}, max: 0, promedio: 0 });

const cargarComunidad = async () => {
    if (!ejercicioSeleccionado.value) return;
    comunidad.value = { ...comunidad.value, cargando: true, error: null };
    try {
        const axios = (await import('axios')).default;
        const response = await axios.get('/api/comunidad/stats', { params: { ejercicio: ejercicioSeleccionado.value } });
        comunidad.value = { ...response.data, cargando: false, error: null };
    } catch (err) {
        comunidad.value = { ...comunidad.value, cargando: false, error: 'No se pudo cargar la comparación.' };
    }
};

const posicionBarra = (valor) => {
    if (!valor || !comunidad.value.max) return 0;
    return Math.min(100, (valor / comunidad.value.max) * 100);
};

const rankingTexto = computed(() => {
    const miPeso = comparacion.value.ultimo.peso;
    const p = comunidad.value.percentiles;
    if (!p.p50) return 'sin datos';
    if (miPeso >= p.p90) return 'top 10% 🏆';
    if (miPeso >= p.p75) return 'top 25% 🔥';
    if (miPeso >= p.p50) return 'mitad superior 💪';
    if (miPeso >= p.p25) return 'mitad inferior';
    return 'bottom 25%';
});

const rankingClass = computed(() => {
    const miPeso = comparacion.value.ultimo.peso;
    const p = comunidad.value.percentiles;
    if (!p.p50) return 'text-gray-700 dark:text-gray-300';
    if (miPeso >= p.p75) return 'text-emerald-600 dark:text-emerald-400';
    if (miPeso >= p.p50) return 'text-indigo-600 dark:text-indigo-400';
    return 'text-gray-700 dark:text-gray-300';
});

const registrosPorEjercicio = computed(() => {
    const grouped = new Map();
    props.historial.forEach((row) => {
        if (!row.ejercicio_nombre) return;
        if (!grouped.has(row.ejercicio_nombre)) grouped.set(row.ejercicio_nombre, []);
        grouped.get(row.ejercicio_nombre).push(row);
    });
    // Ordenar cada grupo por fecha ascendente
    grouped.forEach((rows) => rows.sort((a, b) => (a.fecha || '').localeCompare(b.fecha || '')));
    return grouped;
});

const ejerciciosDisponibles = computed(() => [...registrosPorEjercicio.value.keys()].sort());

const registrosActuales = computed(() => {
    if (!ejercicioSeleccionado.value) return [];
    return registrosPorEjercicio.value.get(ejercicioSeleccionado.value) || [];
});

// Auto-seleccionar el primer ejercicio que tenga >= 2 registros
const inicializarSeleccion = () => {
    if (!ejercicioSeleccionado.value && ejerciciosDisponibles.value.length > 0) {
        // Buscar el primero con al menos 2 registros
        const conHistoria = ejerciciosDisponibles.value.find((ej) => registrosPorEjercicio.value.get(ej).length >= 2);
        ejercicioSeleccionado.value = conHistoria || ejerciciosDisponibles.value[0];
    }
};

const comparacion = computed(() => {
    const rows = registrosActuales.value;
    if (!rows.length) return { primero: { peso: 0, fecha: '' }, ultimo: { peso: 0, fecha: '' }, diferencia: 0, porcentaje: 0, diasEntre: 0 };
    const primero = rows[0];
    const ultimo = rows[rows.length - 1];
    const pesoPrimero = Number(primero.peso) || 0;
    const pesoUltimo = Number(ultimo.peso) || 0;
    const diferencia = pesoUltimo - pesoPrimero;
    const porcentaje = pesoPrimero > 0 ? (diferencia / pesoPrimero) * 100 : 0;
    const fecha1 = new Date(primero.fecha);
    const fecha2 = new Date(ultimo.fecha);
    const diasEntre = Math.max(0, Math.round((fecha2 - fecha1) / (1000 * 60 * 60 * 24)));
    return {
        primero: { peso: pesoPrimero, fecha: primero.fecha },
        ultimo: { peso: pesoUltimo, fecha: ultimo.fecha },
        diferencia,
        porcentaje,
        diasEntre,
    };
});

const stats = computed(() => {
    const rows = registrosActuales.value.filter((r) => Number(r.peso) > 0);
    if (!rows.length) return { pr: { peso: 0, reps: 0, fecha: '' }, total: 0 };
    const pr = rows.reduce((best, r) => Number(r.peso) > Number(best.peso) ? r : best, rows[0]);
    return { pr: { peso: pr.peso, reps: pr.reps_realizadas ?? pr.reps_max, fecha: pr.fecha }, total: rows.length };
});

const renderChart = () => {
    if (!chartCanvas.value) return;
    const rows = registrosActuales.value.filter((r) => Number(r.peso) > 0);
    if (!rows.length) {
        if (chartInstance) { chartInstance.destroy(); chartInstance = null; }
        return;
    }

    const labels = rows.map((r) => r.fecha);
    const pesos = rows.map((r) => Number(r.peso));
    const reps = rows.map((r) => Number(r.reps_realizadas) || Number(r.reps_max) || 0);

    if (chartInstance) chartInstance.destroy();

    chartInstance = new Chart(chartCanvas.value, {
        type: 'line',
        data: {
            labels,
            datasets: [
                {
                    label: 'Peso (kg)',
                    data: pesos,
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99, 102, 241, 0.15)',
                    borderWidth: 3,
                    tension: 0.3,
                    fill: true,
                    pointBackgroundColor: '#6366f1',
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    yAxisID: 'y',
                },
                {
                    label: 'Reps',
                    data: reps,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    borderWidth: 2,
                    borderDash: [5, 5],
                    tension: 0.3,
                    pointBackgroundColor: '#10b981',
                    pointRadius: 4,
                    yAxisID: 'y1',
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'top' },
                tooltip: {
                    callbacks: {
                        label: (ctx) => `${ctx.dataset.label}: ${ctx.parsed.y}`,
                    },
                },
            },
            scales: {
                x: {
                    ticks: { maxRotation: 45, autoSkip: true, maxTicksLimit: 10 },
                    grid: { color: 'rgba(107, 114, 128, 0.1)' },
                },
                y: {
                    type: 'linear',
                    position: 'left',
                    title: { display: true, text: 'Peso (kg)' },
                    grid: { color: 'rgba(107, 114, 128, 0.1)' },
                },
                y1: {
                    type: 'linear',
                    position: 'right',
                    title: { display: true, text: 'Reps' },
                    grid: { drawOnChartArea: false },
                },
            },
        },
    });
};

watch(() => ejercicioSeleccionado.value, () => {
    nextTick(renderChart);
    cargarComunidad();
});
watch(() => props.historial, () => {
    inicializarSeleccion();
    nextTick(renderChart);
}, { deep: true });

onMounted(() => {
    inicializarSeleccion();
    nextTick(renderChart);
});

onBeforeUnmount(() => {
    if (chartInstance) { chartInstance.destroy(); chartInstance = null; }
});
</script>