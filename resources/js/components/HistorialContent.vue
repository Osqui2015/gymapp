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
            
            <!-- Selector de alumno para entrenadores/admins -->
            <div v-if="isTrainerOrAdmin && alumnos.length" class="mt-6 flex flex-col sm:flex-row sm:items-center gap-3 bg-white/5 border border-white/10 rounded-xl p-4 max-w-xl">
              <div class="flex items-center gap-2 text-indigo-300">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                <span class="text-sm font-semibold">Ver historial de:</span>
              </div>
              <select
                id="alumno-select"
                v-model="selectedAlumnoId"
                @change="onAlumnoChange"
                class="rounded-lg border border-slate-700 bg-slate-900 px-3 py-1.5 text-sm text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 w-full sm:w-auto min-w-[200px]"
              >
                <option v-for="alumno in alumnos" :key="alumno.id" :value="alumno.id">
                  {{ alumno.name }} ({{ alumno.nick }})
                </option>
              </select>
            </div>
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
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600 mx-auto mb-4"></div>
        Cargando historial...
      </div>

      <div v-else-if="isTrainerOrAdmin && alumnos.length === 0" class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8 text-center border border-gray-200 dark:border-gray-700">
        <p class="text-gray-500 dark:text-gray-400 text-lg">No tienes alumnos asignados a tu cargo.</p>
      </div>

      <div v-else-if="isTrainerOrAdmin && !selectedAlumnoId" class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8 text-center border border-gray-200 dark:border-gray-700">
        <p class="text-gray-500 dark:text-gray-400 text-lg">Por favor, selecciona un alumno para ver su historial.</p>
      </div>

      <div v-else-if="!resumenEjercicios.length" class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8 text-center border border-gray-200 dark:border-gray-700">
        <p class="text-gray-500 dark:text-gray-400 text-lg">Aún no hay historial registrado para mostrar.</p>
      </div>

      <div v-else class="space-y-6">
        <!-- Pestañas de Navegación -->
        <div class="flex border-b border-gray-200 dark:border-gray-700 mb-6 gap-6">
          <button
            @click="activeTab = 'matrix'"
            :class="activeTab === 'matrix' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 dark:border-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
            class="pb-4 text-sm font-semibold border-b-2 transition-all flex items-center gap-2"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
            </svg>
            Matriz de Progreso
          </button>
          <button
            @click="activeTab = 'evolution'"
            :class="activeTab === 'evolution' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 dark:border-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
            class="pb-4 text-sm font-semibold border-b-2 transition-all flex items-center gap-2"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            Evolución Detallada
          </button>
        </div>

        <!-- Vista: Matriz de Progreso -->
        <div v-if="activeTab === 'matrix'" class="space-y-6">
          <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-lg overflow-hidden">
            <div class="px-6 py-4 bg-gradient-to-r from-slate-900 to-indigo-900 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
              <h2 class="text-lg font-bold text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                </svg>
                Matriz de Cargas por Fecha
              </h2>
              <button 
                @click="toggleDateSort" 
                class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white/10 hover:bg-white/15 border border-white/10 text-xs font-semibold text-white transition-colors"
              >
                <svg class="w-4 h-4 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                </svg>
                Orden: {{ dateSortAsc ? 'Cronológico' : 'Últimos primero' }}
              </button>
            </div>
            
            <div class="p-6">
              <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
                <table class="w-full text-sm text-left border-collapse">
                  <thead class="bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-300 uppercase text-xs">
                    <tr>
                      <th class="sticky left-0 z-20 bg-gray-100 dark:bg-gray-600 px-4 py-3.5 font-bold border-r border-gray-200 dark:border-gray-700 min-w-[200px]">
                        Ejercicio
                      </th>
                      <th v-for="date in pivotData.dates" :key="date.raw" class="px-4 py-3.5 font-bold text-center border-r border-gray-200 dark:border-gray-700 min-w-[100px]">
                        {{ date.formatted }}
                      </th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <tr v-for="row in pivotData.rows" :key="row.name" class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors bg-white dark:bg-gray-800">
                      <td class="sticky left-0 z-10 bg-white dark:bg-gray-800 px-4 py-3.5 font-semibold text-gray-900 dark:text-white border-r border-gray-200 dark:border-gray-700 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)]">
                        {{ row.name }}
                      </td>
                      <td v-for="date in pivotData.dates" :key="date.raw" class="px-4 py-3.5 text-center border-r border-gray-200 dark:border-gray-700 font-medium">
                        <span v-if="row.weights[date.raw] !== '-'" class="inline-flex items-center justify-center rounded-md bg-indigo-50 dark:bg-indigo-950/40 px-2 py-1 text-sm font-bold text-indigo-600 dark:text-indigo-400">
                          {{ row.weights[date.raw] }}
                        </span>
                        <span v-else class="text-gray-400 dark:text-gray-600 font-normal">
                          -
                        </span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- Vista: Evolución Detallada -->
        <div v-if="activeTab === 'evolution'" class="space-y-6">
          <!-- Tabla de Progreso General con Pesos Máximos -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-lg overflow-hidden">
          <div class="px-6 py-4 bg-gradient-to-r from-slate-900 to-indigo-900 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-bold text-white flex items-center gap-2">
              <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
              </svg>
              Resumen de Progreso y Pesos Máximos
            </h2>
          </div>
          <div class="p-6">
            <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
              <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-300 uppercase text-xs">
                  <tr>
                    <th class="px-4 py-3 font-semibold">Fecha / Día</th>
                    <th class="px-4 py-3 font-semibold">Ejercicio</th>
                    <th class="px-4 py-3 font-semibold text-center">Series</th>
                    <th class="px-4 py-3 font-semibold text-right">Peso Promedio</th>
                    <th class="px-4 py-3 font-semibold text-right text-indigo-600 dark:text-indigo-400 font-bold">Peso Máximo Cargado</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                  <tr v-for="(fila, idx) in tablaProgreso" :key="idx" class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors bg-white dark:bg-gray-800">
                    <td class="px-4 py-3">
                      <div class="font-medium text-gray-900 dark:text-white">{{ fila.fecha }}</div>
                      <div class="text-xs text-gray-500 dark:text-gray-400">{{ fila.dia }}</div>
                    </td>
                    <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white">
                      {{ fila.nombre }}
                    </td>
                    <td class="px-4 py-3 text-center text-gray-700 dark:text-gray-300">
                      {{ fila.seriesCount }} series
                    </td>
                    <td class="px-4 py-3 text-right text-gray-500 dark:text-gray-400">
                      {{ fila.avgWeight }} kg
                    </td>
                    <td class="px-4 py-3 text-right font-black text-indigo-600 dark:text-indigo-400 text-base">
                      {{ fila.maxWeight }} kg
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

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

const isTrainerOrAdmin = ref(false);
const alumnos = ref([]);
const selectedAlumnoId = ref(null);
const userRole = ref('');
const activeTab = ref('matrix');
const dateSortAsc = ref(true);

const toggleDateSort = () => {
  dateSortAsc.value = !dateSortAsc.value;
};

const fetchUserInfo = async () => {
  try {
    const res = await axios.get('/api/user-info');
    userRole.value = res.data.role;
    isTrainerOrAdmin.value = ['trainer', 'administrador'].includes(res.data.role);
    if (isTrainerOrAdmin.value) {
      await fetchAlumnos();
    }
  } catch (err) {
    console.error('Error fetching user info:', err);
  }
};

const fetchAlumnos = async () => {
  try {
    const res = await axios.get('/api/trainer/alumnos');
    alumnos.value = res.data;
    if (alumnos.value.length > 0) {
      selectedAlumnoId.value = alumnos.value[0].id;
    }
  } catch (err) {
    console.error('Error fetching alumnos:', err);
  }
};

const onAlumnoChange = async () => {
  await loadHistorial();
};

const pivotData = computed(() => {
  if (!historial.value || !historial.value.length) return { dates: [], rows: [] };

  const dateMap = new Map();
  historial.value.forEach(row => {
    if (!dateMap.has(row.fecha)) {
      dateMap.set(row.fecha, formatDate(row.fecha));
    }
  });

  const sortedDates = [...dateMap.keys()].sort((a, b) => {
    const dateA = new Date(a);
    const dateB = new Date(b);
    return dateSortAsc.value ? dateA - dateB : dateB - dateA;
  });

  const exercises = [...new Set(historial.value.map(row => row.ejercicio_nombre))].sort();

  const rows = exercises.map(exerciseName => {
    const exerciseRows = historial.value.filter(row => row.ejercicio_nombre === exerciseName);

    const dateWeights = {};
    sortedDates.forEach(date => {
      const dayRows = exerciseRows.filter(row => row.fecha === date && row.peso !== null);
      if (dayRows.length > 0) {
        const weights = dayRows.map(row => Number(row.peso));
        const maxWeight = Math.max(...weights);
        dateWeights[date] = maxWeight > 0 ? `${maxWeight} kg` : '-';
      } else {
        dateWeights[date] = '-';
      }
    });

    return {
      name: exerciseName,
      weights: dateWeights
    };
  });

  return {
    dates: sortedDates.map(d => ({ raw: d, formatted: dateMap.get(d) })),
    rows
  };
});

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

const tablaProgreso = computed(() => {
  const result = [];
  resumenEjercicios.value.forEach(ejercicio => {
    ejercicio.timeline.forEach(sesion => {
      const dayRows = historial.value.filter(row => 
        row.ejercicio_nombre === ejercicio.nombre && row.fecha === sesion.fecha
      );
      
      const weights = dayRows
        .map(row => Number(row.peso))
        .filter(peso => Number.isFinite(peso) && peso > 0);
        
      const maxWeight = weights.length ? Math.max(...weights) : 0;
      
      result.push({
        nombre: ejercicio.nombre,
        fecha: sesion.fechaLabel,
        dia: sesion.diaLabel,
        rawFecha: new Date(sesion.fecha),
        maxWeight: maxWeight.toFixed(1),
        avgWeight: sesion.pesoPromedio,
        seriesCount: dayRows.length
      });
    });
  });
  
  return result.sort((a, b) => b.rawFecha - a.rawFecha);
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
    const params = {};
    if (isTrainerOrAdmin.value && selectedAlumnoId.value) {
      params.user_id = selectedAlumnoId.value;
    }
    const response = await axios.get('/api/historial', { params });
    historial.value = Array.isArray(response.data) ? response.data : [];
  } catch (error) {
    console.error('Error cargando historial:', error);
    historial.value = [];
  } finally {
    loading.value = false;
  }
};

onMounted(async () => {
  await fetchUserInfo();
  if (!isTrainerOrAdmin.value) {
    await loadHistorial();
  } else if (selectedAlumnoId.value) {
    await loadHistorial();
  } else {
    loading.value = false;
  }
});
</script>
