<template>
    <div class="space-y-6">
        <section
            class="bg-[#131924] border border-[#232D42] rounded-xl overflow-hidden shadow-lg"
        >
            <!-- Header -->
            <div
                class="p-4 bg-[#18202F]/60 border-b border-[#232D42] flex flex-col sm:flex-row sm:items-center justify-between gap-3"
            >
                <div class="flex items-center gap-2.5">
                    <div class="w-2.5 h-2.5 rounded bg-indigo-500"></div>
                    <div>
                        <h3 class="text-sm font-bold text-white tracking-wide uppercase">
                            Comparación de Pesos por Ejercicio
                        </h3>
                        <p class="text-xs text-slate-400 mt-0.5">
                            Cuánto levantabas al principio vs. cuánto levantás ahora en telemetría longitudinal.
                        </p>
                    </div>
                </div>

                <!-- Selector de ejercicio -->
                <div v-if="ejerciciosDisponibles.length > 0" class="w-full sm:w-72">
                    <select
                        v-model="ejercicioSeleccionado"
                        class="w-full bg-[#101521] border border-[#232D42] text-xs font-semibold text-white rounded-lg px-3 py-2 focus:outline-none focus:ring-1 focus:ring-indigo-500 cursor-pointer"
                    >
                        <option v-for="ej in ejerciciosDisponibles" :key="ej" :value="ej">
                            {{ ej }}
                        </option>
                    </select>
                </div>
            </div>

            <div class="p-5">
                <div
                    v-if="!ejercicioSeleccionado"
                    class="text-center py-12 text-slate-400 text-xs"
                >
                    Seleccioná un ejercicio para ver la comparación.
                </div>

                <div v-else class="space-y-6">
                    <!-- Cards "antes vs después" -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="rounded-xl border border-[#232D42] bg-[#101521] p-4">
                            <p class="text-[11px] uppercase tracking-wider text-slate-400 font-semibold">
                                Primer registro
                            </p>
                            <p class="mt-2 text-2xl sm:text-3xl font-extrabold text-white font-mono">
                                {{ comparacion.primero.peso }}
                                <span class="text-xs font-normal text-slate-400">kg</span>
                            </p>
                            <p class="text-xs text-slate-400 font-mono mt-1">
                                {{ comparacion.primero.fecha }}
                            </p>
                        </div>

                        <div class="rounded-xl border border-indigo-500/30 bg-indigo-500/10 p-4">
                            <p class="text-[11px] uppercase tracking-wider text-indigo-300 font-semibold">
                                Último registro
                            </p>
                            <p class="mt-2 text-2xl sm:text-3xl font-extrabold text-white font-mono">
                                {{ comparacion.ultimo.peso }}
                                <span class="text-xs font-normal text-indigo-300">kg</span>
                            </p>
                            <p class="text-xs text-indigo-300/80 font-mono mt-1">
                                {{ comparacion.ultimo.fecha }}
                            </p>
                        </div>

                        <div
                            :class="[
                                'rounded-xl border p-4',
                                comparacion.diferencia > 0
                                    ? 'border-emerald-500/30 bg-emerald-500/10'
                                    : comparacion.diferencia < 0
                                      ? 'border-rose-500/30 bg-rose-500/10'
                                      : 'border-[#232D42] bg-[#101521]',
                            ]"
                        >
                            <p class="text-[11px] uppercase tracking-wider text-slate-300 font-semibold">
                                Cambio total
                            </p>
                            <p
                                :class="[
                                    'mt-2 text-2xl sm:text-3xl font-extrabold font-mono',
                                    comparacion.diferencia > 0
                                        ? 'text-emerald-400'
                                        : comparacion.diferencia < 0
                                          ? 'text-rose-400'
                                          : 'text-slate-200',
                                ]"
                            >
                                {{ comparacion.diferencia > 0 ? '+' : '' }}{{ comparacion.diferencia.toFixed(1) }}
                                <span class="text-xs font-normal">kg</span>
                            </p>
                            <p class="text-xs text-slate-300 font-mono mt-1">
                                {{ comparacion.porcentaje > 0 ? '+' : '' }}{{ comparacion.porcentaje.toFixed(1) }}% en
                                {{ comparacion.diasEntre }} días
                            </p>
                        </div>
                    </div>

                    <!-- Gráfico -->
                    <div class="rounded-xl bg-[#0B0F17]/80 border border-[#232D42] p-4">
                        <div class="relative h-[300px]">
                            <canvas ref="chartCanvas"></canvas>
                        </div>
                    </div>

                    <!-- Records del ejercicio -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="rounded-xl border border-[#232D42] bg-[#101521] p-4 flex items-center justify-between">
                            <div>
                                <p class="text-[11px] uppercase tracking-wider text-slate-400 font-semibold">
                                    Mejor peso (PR)
                                </p>
                                <p class="mt-1 text-lg font-extrabold text-amber-400 font-mono">
                                    ★ {{ stats.pr.peso }} kg × {{ stats.pr.reps }} reps
                                </p>
                            </div>
                            <span class="text-xs font-mono text-slate-400 bg-[#18202F] px-2.5 py-1 rounded border border-[#232D42]">
                                {{ stats.pr.fecha }}
                            </span>
                        </div>
                        <div class="rounded-xl border border-[#232D42] bg-[#101521] p-4 flex items-center justify-between">
                            <div>
                                <p class="text-[11px] uppercase tracking-wider text-slate-400 font-semibold">
                                    Total de series registradas
                                </p>
                                <p class="mt-1 text-lg font-extrabold text-white font-mono">
                                    {{ stats.total }} sets
                                </p>
                            </div>
                            <span class="text-xs font-mono text-emerald-400 bg-emerald-500/10 px-2.5 py-1 rounded border border-emerald-500/30">
                                Activo
                            </span>
                        </div>
                    </div>

                    <!-- Comparación con la comunidad -->
                    <div class="rounded-xl border border-violet-500/30 bg-violet-500/10 p-4">
                        <div class="flex items-center gap-2 mb-3">
                            <span class="material-symbols-outlined text-violet-400 text-[20px]">public</span>
                            <h4 class="text-sm font-bold text-white uppercase tracking-wider">
                                Comparación con la comunidad
                            </h4>
                        </div>
                        <div v-if="comunidad.cargando" class="text-xs text-slate-400 py-3">
                            Cargando datos de la comunidad...
                        </div>
                        <div v-else-if="comunidad.error" class="text-xs text-rose-400 py-2">
                            {{ comunidad.error }}
                        </div>
                        <div v-else-if="comunidad.total_usuarios > 0">
                            <div class="mb-3">
                                <p class="text-xs text-slate-300">
                                    De <strong class="text-white">{{ comunidad.total_usuarios }}</strong> atletas que
                                    entrenan este ejercicio, estás en el
                                    <strong :class="rankingClass">
                                        {{ rankingTexto }}
                                    </strong>
                                    ({{ comunidad.percentiles.p50 }} kg es la mediana de la comunidad).
                                </p>
                            </div>
                            <!-- Barra visual de percentiles -->
                            <div class="relative h-6 bg-[#0B0F17] border border-[#232D42] rounded-full overflow-hidden">
                                <div
                                    class="absolute inset-y-0 left-0 bg-indigo-500/30"
                                    :style="{ width: `${posicionBarra(comunidad.percentiles.p25)}%` }"
                                ></div>
                                <div
                                    class="absolute inset-y-0 left-0 bg-indigo-500/50"
                                    :style="{ width: `${posicionBarra(comunidad.percentiles.p50)}%` }"
                                ></div>
                                <div
                                    class="absolute inset-y-0 left-0 bg-violet-500/70"
                                    :style="{ width: `${posicionBarra(comunidad.percentiles.p75)}%` }"
                                ></div>
                                <!-- Marcador del usuario -->
                                <div
                                    class="absolute top-0 bottom-0 w-1 bg-amber-400 shadow-lg"
                                    :style="{ left: `${posicionBarra(comparacion.ultimo.peso)}%` }"
                                ></div>
                            </div>
                            <div class="flex justify-between text-[11px] font-mono text-slate-400 mt-1.5">
                                <span>p25: {{ comunidad.percentiles.p25 }} kg</span>
                                <span>p50: {{ comunidad.percentiles.p50 }} kg</span>
                                <span>p75: {{ comunidad.percentiles.p75 }} kg</span>
                                <span>p90: {{ comunidad.percentiles.p90 }} kg</span>
                            </div>
                        </div>
                        <div v-else class="text-xs text-slate-400 py-2">
                            Aún no hay suficientes datos de la comunidad para este ejercicio.
                        </div>
                    </div>
                </div>
            </div>
        </section>
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

const comunidad = ref({
    cargando: false,
    error: null,
    total_usuarios: 0,
    percentiles: {},
    max: 0,
    promedio: 0,
});

const cargarComunidad = async () => {
    if (!ejercicioSeleccionado.value) return;
    comunidad.value = { ...comunidad.value, cargando: true, error: null };
    try {
        const axios = (await import('axios')).default;
        const response = await axios.get('/api/comunidad/stats', {
            params: { ejercicio: ejercicioSeleccionado.value },
        });
        comunidad.value = { ...response.data, cargando: false, error: null };
    } catch {
        comunidad.value = {
            ...comunidad.value,
            cargando: false,
            error: 'No se pudo cargar la comparación.',
        };
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
    if (!p.p50) return 'text-slate-300';
    if (miPeso >= p.p75) return 'text-emerald-400';
    if (miPeso >= p.p50) return 'text-indigo-400';
    return 'text-slate-300';
});

const registrosPorEjercicio = computed(() => {
    const grouped = new Map();
    props.historial.forEach((row) => {
        if (!row.ejercicio_nombre) return;
        if (!grouped.has(row.ejercicio_nombre)) grouped.set(row.ejercicio_nombre, []);
        grouped.get(row.ejercicio_nombre).push(row);
    });
    grouped.forEach((rows) => rows.sort((a, b) => (a.fecha || '').localeCompare(b.fecha || '')));
    return grouped;
});

const ejerciciosDisponibles = computed(() => [...registrosPorEjercicio.value.keys()].sort());

const registrosActuales = computed(() => {
    if (!ejercicioSeleccionado.value) return [];
    return registrosPorEjercicio.value.get(ejercicioSeleccionado.value) || [];
});

const inicializarSeleccion = () => {
    if (!ejercicioSeleccionado.value && ejerciciosDisponibles.value.length > 0) {
        const conHistoria = ejerciciosDisponibles.value.find(
            (ej) => registrosPorEjercicio.value.get(ej).length >= 2
        );
        ejercicioSeleccionado.value = conHistoria || ejerciciosDisponibles.value[0];
    }
};

const comparacion = computed(() => {
    const rows = registrosActuales.value;
    if (!rows.length)
        return {
            primero: { peso: 0, fecha: '' },
            ultimo: { peso: 0, fecha: '' },
            diferencia: 0,
            porcentaje: 0,
            diasEntre: 0,
        };
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
    const pr = rows.reduce((best, r) => (Number(r.peso) > Number(best.peso) ? r : best), rows[0]);
    return {
        pr: { peso: pr.peso, reps: pr.reps_realizadas ?? pr.reps_max, fecha: pr.fecha },
        total: rows.length,
    };
});

const renderChart = () => {
    if (!chartCanvas.value) return;
    const rows = registrosActuales.value.filter((r) => Number(r.peso) > 0);
    if (!rows.length) {
        if (chartInstance) {
            chartInstance.destroy();
            chartInstance = null;
        }
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
                    borderColor: '#818cf8',
                    backgroundColor: 'rgba(99, 102, 241, 0.2)',
                    borderWidth: 3,
                    tension: 0.35,
                    fill: true,
                    pointBackgroundColor: '#6366f1',
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    yAxisID: 'y',
                },
                {
                    label: 'Reps',
                    data: reps,
                    borderColor: '#34d399',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    borderWidth: 2,
                    borderDash: [5, 5],
                    tension: 0.35,
                    pointBackgroundColor: '#10b981',
                    pointRadius: 3,
                    yAxisID: 'y1',
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: {
                    position: 'top',
                    labels: { color: '#94a3b8' },
                },
                tooltip: {
                    callbacks: {
                        label: (ctx) => `${ctx.dataset.label}: ${ctx.parsed.y}`,
                    },
                },
            },
            scales: {
                x: {
                    ticks: { color: '#94a3b8', maxRotation: 45, autoSkip: true, maxTicksLimit: 10 },
                    grid: { color: 'rgba(35, 45, 66, 0.6)' },
                },
                y: {
                    type: 'linear',
                    position: 'left',
                    ticks: { color: '#94a3b8' },
                    title: { display: true, text: 'Peso (kg)', color: '#94a3b8' },
                    grid: { color: 'rgba(35, 45, 66, 0.6)' },
                },
                y1: {
                    type: 'linear',
                    position: 'right',
                    ticks: { color: '#94a3b8' },
                    title: { display: true, text: 'Reps', color: '#94a3b8' },
                    grid: { drawOnChartArea: false },
                },
            },
        },
    });
};

watch(
    () => ejercicioSeleccionado.value,
    () => {
        nextTick(renderChart);
        cargarComunidad();
    }
);
watch(
    () => props.historial,
    () => {
        inicializarSeleccion();
        nextTick(renderChart);
    },
    { deep: true }
);

onMounted(() => {
    inicializarSeleccion();
    nextTick(renderChart);
});

onBeforeUnmount(() => {
    if (chartInstance) {
        chartInstance.destroy();
        chartInstance = null;
    }
});
</script>
