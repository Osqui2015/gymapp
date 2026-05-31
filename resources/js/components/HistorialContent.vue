<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="rounded-2xl bg-gradient-to-r from-slate-950 via-slate-900 to-indigo-950 border border-slate-800 shadow-2xl p-6 md:p-8 mb-8">
        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
          <div>
            <p class="text-sm uppercase tracking-[0.2em] text-indigo-300 mb-2">Seguimiento de progreso</p>
            <h1 class="text-3xl md:text-4xl font-bold text-white">Historial de entrenamiento</h1>
            <p class="mt-3 text-sm md:text-base text-slate-300 max-w-2xl">
              Revisa la evolución de tus ejercicios, el promedio de peso utilizado y cómo cambia el rendimiento por día.
            </p>
          </div>
          <div class="flex items-center gap-3">
            <a
              href="/dashboard"
              class="inline-flex items-center justify-center rounded-xl bg-white/10 px-5 py-3 text-sm font-semibold text-white transition-colors hover:bg-white/15 border border-white/10"
            >
              Volver al dashboard
            </a>
          </div>
        </div>
      </div>

      <div class="grid gap-4 md:grid-cols-4 mb-8">
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm">
          <p class="text-sm text-gray-500 dark:text-gray-400">Ejercicios rastreados</p>
          <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ resumenEjercicios.length }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm">
          <p class="text-sm text-gray-500 dark:text-gray-400">Series registradas</p>
          <p class="mt-2 text-3xl font-bold text-emerald-600 dark:text-emerald-400">{{ totalSeries }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm">
          <p class="text-sm text-gray-500 dark:text-gray-400">Peso promedio global</p>
          <p class="mt-2 text-3xl font-bold text-indigo-600 dark:text-indigo-400">{{ pesoPromedioGlobal }} kg</p>
        </div>
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm">
          <p class="text-sm text-gray-500 dark:text-gray-400">Reps promedio global</p>
          <p class="mt-2 text-3xl font-bold text-orange-600 dark:text-orange-400">{{ repsPromedioGlobal }}</p>
        </div>
      </div>

      <div v-if="loading" class="text-center py-16 text-gray-500 dark:text-gray-400">
        Cargando historial...
      </div>

      <div v-else-if="!resumenEjercicios.length" class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8 text-center border border-gray-200 dark:border-gray-700">
        <p class="text-gray-500 dark:text-gray-400 text-lg">Aún no hay historial para mostrar.</p>
      </div>

      <div v-else class="space-y-6">
        <div
          v-for="ejercicio in resumenEjercicios"
          :key="ejercicio.nombre"
          class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-lg overflow-hidden"
        >
          <div class="p-4 md:p-6 border-b border-gray-100 dark:border-gray-700">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
              <div>
                <h3 class="text-xl md:text-2xl font-bold text-gray-900 dark:text-white">{{ ejercicio.nombre }}</h3>
                <p class="text-xs md:text-sm text-gray-500 dark:text-gray-400 mt-1">
                  {{ ejercicio.totalSeries }} series registradas · {{ ejercicio.totalSesiones }} sesiones
                </p>
              </div>

              <div class="flex flex-wrap gap-2 text-xs md:text-sm">
                <span class="rounded-full bg-indigo-100 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-200 px-2.5 py-1 font-medium">
                  Peso promedio: {{ ejercicio.pesoPromedio }} kg
                </span>
                <span class="rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-200 px-2.5 py-1 font-medium">
                  Reps promedio: {{ ejercicio.repsPromedio }}
                </span>
                <span class="rounded-full bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-200 px-2.5 py-1 font-medium">
                  Último peso: {{ ejercicio.ultimoPeso }} kg
                </span>
              </div>
            </div>
          </div>

          <div class="p-4 md:p-6 grid gap-6 lg:grid-cols-[1.4fr_0.9fr]">
            <div>
              <div class="flex items-center justify-between mb-3">
                <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">Evolución del peso promedio por fecha</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">kg</p>
              </div>

              <div class="overflow-x-auto">
                <svg
                  :viewBox="`0 0 ${chartWidth} ${chartHeight}`"
                  class="w-full min-w-[520px] h-56 md:h-64 rounded-xl bg-gradient-to-br from-slate-950 to-slate-800 border border-slate-700"
                  preserveAspectRatio="none"
                >
                  <defs>
                    <linearGradient :id="`bar-gradient-${ejercicio.slug}`" x1="0" y1="0" x2="0" y2="1">
                      <stop offset="0%" stop-color="#818cf8" />
                      <stop offset="100%" stop-color="#22d3ee" />
                    </linearGradient>
                  </defs>

                  <g v-for="n in 5" :key="n">
                    <line
                      :x1="marginLeft"
                      :x2="chartWidth - marginRight"
                      :y1="scaleY(globalMaxWeight, n - 1)"
                      :y2="scaleY(globalMaxWeight, n - 1)"
                      stroke="rgba(255,255,255,0.08)"
                    />
                  </g>

                  <g v-for="(point, index) in ejercicio.timeline" :key="`bar-${ejercicio.slug}-${index}`">
                    <rect
                      :x="barX(index, ejercicio.timeline.length) - (barWidth(ejercicio.timeline.length) / 2)"
                      :y="scaleBarY(point.avgPeso)"
                      :width="barWidth(ejercicio.timeline.length)"
                      :height="barHeight(point.avgPeso)"
                      rx="8"
                      :fill="`url(#bar-gradient-${ejercicio.slug})`"
                      opacity="0.95"
                    />
                  </g>

                  <g v-if="ejercicio.timeline.length">
                    <g v-for="(point, index) in ejercicio.timeline" :key="`label-${ejercicio.slug}-${index}`">
                      <text :x="barX(index, ejercicio.timeline.length)" y="20" text-anchor="middle" fill="#cbd5e1" font-size="11">{{ point.fechaLabel }}</text>
                      <text :x="barX(index, ejercicio.timeline.length)" :y="scaleBarY(point.avgPeso) - 10" text-anchor="middle" fill="#e2e8f0" font-size="11" font-weight="700">
                        {{ point.avgPeso.toFixed(1) }}
                      </text>
                    </g>
                  </g>

                  <g>
                    <text
                      v-for="(point, index) in ejercicio.timeline"
                      :key="`axis-${ejercicio.slug}-${index}`"
                      :x="barX(index, ejercicio.timeline.length)"
                      :y="chartHeight - 10"
                      text-anchor="middle"
                      fill="#cbd5e1"
                      font-size="11"
                    >
                      {{ point.diaLabel }}
                    </text>
                  </g>
                </svg>
              </div>
            </div>

            <div class="space-y-4">
              <div class="rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 p-4">
                <p class="text-sm text-gray-500 dark:text-gray-400">Detalles del ejercicio</p>
                <dl class="mt-4 grid grid-cols-2 gap-4 text-sm">
                  <div>
                    <dt class="text-gray-500 dark:text-gray-400">Fecha inicial</dt>
                    <dd class="mt-1 font-semibold text-gray-900 dark:text-white">{{ ejercicio.fechaInicial }}</dd>
                  </div>
                  <div>
                    <dt class="text-gray-500 dark:text-gray-400">Fecha final</dt>
                    <dd class="mt-1 font-semibold text-gray-900 dark:text-white">{{ ejercicio.fechaFinal }}</dd>
                  </div>
                  <div>
                    <dt class="text-gray-500 dark:text-gray-400">Peso mínimo</dt>
                    <dd class="mt-1 font-semibold text-gray-900 dark:text-white">{{ ejercicio.pesoMinimo }} kg</dd>
                  </div>
                  <div>
                    <dt class="text-gray-500 dark:text-gray-400">Peso máximo</dt>
                    <dd class="mt-1 font-semibold text-gray-900 dark:text-white">{{ ejercicio.pesoMaximo }} kg</dd>
                  </div>
                </dl>
              </div>

              <div class="rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 p-4">
                <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Sesiones</p>
                <div class="space-y-3 max-h-72 overflow-auto pr-1">
                  <div
                    v-for="sesion in ejercicio.sesiones"
                    :key="`${ejercicio.slug}-${sesion.fecha}-${sesion.dia}`"
                    class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-3 shadow-sm"
                  >
                    <div class="flex items-center justify-between gap-3">
                      <div>
                        <p class="font-semibold text-gray-900 dark:text-white">{{ sesion.fecha }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ sesion.dia }}</p>
                      </div>
                      <div class="text-right text-sm">
                        <p class="font-semibold text-indigo-600 dark:text-indigo-400">{{ sesion.pesoPromedio }} kg</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ sesion.repsPromedio }} reps promedio</p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import axios from 'axios';

const loading = ref(true);
const historial = ref([]);
const chartWidth = 760;
const chartHeight = 260;
const marginLeft = 50;
const marginRight = 20;
const marginBottom = 36;

const globalMaxWeight = computed(() => {
  const values = historial.value
    .map((row) => Number(row.peso))
    .filter((peso) => Number.isFinite(peso) && peso > 0);

  return values.length ? Math.max(...values, 10) : 10;
});

const totalSeries = computed(() => historial.value.length);

const pesoPromedioGlobal = computed(() => {
  const values = historial.value
    .map((row) => Number(row.peso))
    .filter((peso) => Number.isFinite(peso) && peso > 0);

  if (!values.length) {
    return '0.0';
  }

  return (values.reduce((sum, value) => sum + value, 0) / values.length).toFixed(1);
});

const repsPromedioGlobal = computed(() => {
  const values = historial.value
    .map((row) => Number(row.reps_realizadas))
    .filter((reps) => Number.isFinite(reps) && reps >= 0);

  if (!values.length) {
    return '0.0';
  }

  return (values.reduce((sum, value) => sum + value, 0) / values.length).toFixed(1);
});

const slugify = (value) => value
  .toString()
  .normalize('NFD')
  .replace(/[\u0300-\u036f]/g, '')
  .toLowerCase()
  .replace(/[^a-z0-9]+/g, '-')
  .replace(/^-+|-+$/g, '');

const formatDate = (value) => {
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) {
    return value;
  }

  return new Intl.DateTimeFormat('es-MX', {
    day: '2-digit',
    month: 'short',
  }).format(date);
};

const groupByExercise = computed(() => {
  const grouped = new Map();

  historial.value.forEach((row) => {
    if (!grouped.has(row.ejercicio_nombre)) {
      grouped.set(row.ejercicio_nombre, []);
    }

    grouped.get(row.ejercicio_nombre).push(row);
  });

  return grouped;
});

const resumenEjercicios = computed(() => {
  return [...groupByExercise.value.entries()].map(([nombre, rows]) => {
    const timelineMap = new Map();

    rows.forEach((row) => {
      const key = row.fecha;
      if (!timelineMap.has(key)) {
        timelineMap.set(key, []);
      }
      timelineMap.get(key).push(row);
    });

    const timeline = [...timelineMap.entries()]
      .sort((a, b) => new Date(a[0]) - new Date(b[0]))
      .map(([fecha, dayRows]) => {
        const weights = dayRows
          .map((row) => Number(row.peso))
          .filter((peso) => Number.isFinite(peso) && peso > 0);

        const reps = dayRows
          .map((row) => Number(row.reps_realizadas))
          .filter((value) => Number.isFinite(value) && value >= 0);

        const avgPeso = weights.length
          ? weights.reduce((sum, value) => sum + value, 0) / weights.length
          : 0;

        const avgReps = reps.length
          ? reps.reduce((sum, value) => sum + value, 0) / reps.length
          : 0;

        const sample = dayRows[0];

        return {
          fecha,
          fechaLabel: formatDate(fecha),
          diaLabel: sample?.dia || 'Día',
          avgPeso,
          pesoPromedio: avgPeso.toFixed(1),
          repsPromedio: avgReps.toFixed(1),
        };
      });

    const weights = rows
      .map((row) => Number(row.peso))
      .filter((peso) => Number.isFinite(peso) && peso > 0);

    const reps = rows
      .map((row) => Number(row.reps_realizadas))
      .filter((value) => Number.isFinite(value) && value >= 0);

    const totalPeso = weights.reduce((sum, value) => sum + value, 0);
    const totalReps = reps.reduce((sum, value) => sum + value, 0);

    return {
      nombre,
      slug: slugify(nombre),
      totalSeries: rows.length,
      totalSesiones: timeline.length,
      pesoPromedio: weights.length ? (totalPeso / weights.length).toFixed(1) : '0.0',
      repsPromedio: reps.length ? (totalReps / reps.length).toFixed(1) : '0.0',
      ultimoPeso: weights.length ? weights[weights.length - 1].toFixed(1) : '0.0',
      pesoMinimo: weights.length ? Math.min(...weights).toFixed(1) : '0.0',
      pesoMaximo: weights.length ? Math.max(...weights).toFixed(1) : '0.0',
      fechaInicial: timeline.length ? timeline[0].fechaLabel : '-',
      fechaFinal: timeline.length ? timeline[timeline.length - 1].fechaLabel : '-',
      timeline,
      sesiones: timeline.map((sesion) => ({
        fecha: sesion.fechaLabel,
        dia: sesion.diaLabel,
        pesoPromedio: sesion.pesoPromedio,
        repsPromedio: sesion.repsPromedio,
      })),
    };
  });
});

const barX = (index, total) => {
  const usable = chartWidth - marginLeft - marginRight;
  if (total <= 1) {
    return marginLeft + usable / 2;
  }
  return marginLeft + (usable / (total - 1)) * index;
};

const barWidth = (total) => {
  if (total <= 1) {
    return 22;
  }

  const usable = chartWidth - marginLeft - marginRight;
  return Math.max(16, Math.min(38, usable / (total * 4)));
};

const scaleY = (maxValue, tickIndex) => {
  const usableHeight = chartHeight - marginBottom - 30;
  const top = 30;
  const value = maxValue * (1 - tickIndex / 4);
  return top + usableHeight - (value / maxValue) * usableHeight;
};

const scaleBarY = (value) => {
  const maxValue = globalMaxWeight.value || 10;
  const usableHeight = chartHeight - marginBottom - 50;
  const top = 35;
  return top + usableHeight - (value / maxValue) * usableHeight;
};

const barHeight = (value) => {
  const baseY = chartHeight - marginBottom;
  return baseY - scaleBarY(value);
};

const loadHistorial = async () => {
  loading.value = true;
  try {
    const response = await axios.get('/api/historial');
    historial.value = Array.isArray(response.data) ? response.data : [];
  } catch (error) {
    console.error('Error cargando historial:', error);
    historial.value = [];
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  loadHistorial();
});
</script>
