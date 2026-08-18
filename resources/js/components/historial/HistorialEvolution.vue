<template>
  <div class="space-y-6">
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
        <ResponsiveTable
          :rows="tablaProgreso"
          :columns="[
            { key: 'fecha', label: 'Fecha / Día', thClass: '', tdClass: '', sortable: true },
            { key: 'nombre', label: 'Ejercicio', thClass: '', tdClass: '', sortable: true, searchable: true },
            { key: 'seriesCount', label: 'Series', thClass: 'text-center', tdClass: '', sortable: true },
            { key: 'avgWeight', label: 'Peso Promedio', thClass: 'text-right', tdClass: '', sortable: true },
            { key: 'maxWeight', label: 'Peso Máximo Cargado', thClass: 'text-right text-indigo-600 dark:text-indigo-400 font-bold', tdClass: '', sortable: true },
          ]"
          thead-class="bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-300 uppercase text-xs"
          sortable
          filterable
          filter-placeholder="Buscar ejercicio…"
        >
          <template #rows="{ rows }">
            <tr v-for="(fila, idx) in rows" :key="`${fila.fecha}-${idx}`" class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors bg-white dark:bg-gray-800">
              <td class="px-4 py-3">
                <div class="font-medium text-gray-900 dark:text-white">{{ fila.fecha }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">{{ fila.dia }}</div>
              </td>
              <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white">
                {{ fila.nombre }}
                <span v-if="fila.superserie_grupo" class="ml-2 inline-flex items-center rounded-md bg-indigo-50 px-2 py-0.5 text-xs font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-700/10 dark:bg-indigo-900/40 dark:text-indigo-400">
                  Superserie {{ fila.superserie_grupo }}
                </span>
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
          </template>

          <template #cards="{ rows }">
            <div v-for="fila in rows" :key="`${fila.fecha}-${fila.nombre}`" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-4 space-y-3">
              <!-- Header: ejercicio + peso máximo destacado -->
              <div class="flex items-start justify-between gap-3">
                <div class="min-w-0 flex-1">
                  <p class="font-semibold text-gray-900 dark:text-white">{{ fila.nombre }}</p>
                  <span v-if="fila.superserie_grupo" class="mt-1 inline-flex items-center rounded-md bg-indigo-50 px-2 py-0.5 text-xs font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-700/10 dark:bg-indigo-900/40 dark:text-indigo-400">
                    Superserie {{ fila.superserie_grupo }}
                  </span>
                </div>
                <div class="text-right flex-shrink-0">
                  <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Máx</p>
                  <p class="font-black text-indigo-600 dark:text-indigo-400 text-lg leading-tight">
                    {{ fila.maxWeight }} kg
                  </p>
                </div>
              </div>

              <!-- Fecha y día -->
              <div class="flex items-center justify-between text-sm">
                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Fecha</span>
                <span class="text-gray-700 dark:text-gray-300 text-right">
                  {{ fila.fecha }}
                  <span class="text-xs text-gray-500 dark:text-gray-400">· {{ fila.dia }}</span>
                </span>
              </div>

              <!-- Series y peso promedio -->
              <div class="flex items-center justify-between text-sm">
                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Series</span>
                <span class="text-gray-700 dark:text-gray-300">{{ fila.seriesCount }} series</span>
              </div>
              <div class="flex items-center justify-between text-sm">
                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Promedio</span>
                <span class="text-gray-500 dark:text-gray-400">{{ fila.avgWeight }} kg</span>
              </div>
            </div>
          </template>
        </ResponsiveTable>
      </div>
    </div>

    <!-- Per-ejercicio detail cards -->
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
                  :y="scaleBarY(point.avgPeso, globalMaxWeight)"
                  :width="barWidth(ejercicio.timeline.length)"
                  :height="barHeight(point.avgPeso, globalMaxWeight)"
                  rx="8"
                  :fill="`url(#bar-gradient-${ejercicio.slug})`"
                  opacity="0.95"
                />
              </g>

              <g v-if="ejercicio.timeline.length">
                <g v-for="(point, index) in ejercicio.timeline" :key="`label-${ejercicio.slug}-${index}`">
                  <text :x="barX(index, ejercicio.timeline.length)" y="20" text-anchor="middle" fill="#cbd5e1" font-size="11">{{ point.fechaLabel }}</text>
                  <text :x="barX(index, ejercicio.timeline.length)" :y="scaleBarY(point.avgPeso, globalMaxWeight) - 10" text-anchor="middle" fill="#e2e8f0" font-size="11" font-weight="700">
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
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">Sesiones</p>
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
</template>

<script setup>
import ResponsiveTable from '../ResponsiveTable.vue';

// Chart geometry constants
const chartWidth = 760;
const chartHeight = 260;
const marginLeft = 50;
const marginRight = 20;
const marginBottom = 36;

defineProps({
    tablaProgreso: { type: Array, required: true },
    resumenEjercicios: { type: Array, required: true },
    globalMaxWeight: { type: Number, required: true },
});

const barX = (index, total) => {
    const usable = chartWidth - marginLeft - marginRight;
    if (total <= 1) return marginLeft + usable / 2;
    return marginLeft + (usable / (total - 1)) * index;
};

const barWidth = (total) => {
    if (total <= 1) return 22;
    const usable = chartWidth - marginLeft - marginRight;
    return Math.max(16, Math.min(38, usable / (total * 4)));
};

const scaleY = (maxValue, tickIndex) => {
    const usableHeight = chartHeight - marginBottom - 30;
    const top = 30;
    const value = maxValue * (1 - tickIndex / 4);
    return top + usableHeight - (value / maxValue) * usableHeight;
};

const scaleBarY = (value, maxValue) => {
    const max = maxValue || 10;
    const usableHeight = chartHeight - marginBottom - 50;
    const top = 35;
    return top + usableHeight - (value / max) * usableHeight;
};

const barHeight = (value, maxValue) => {
    const baseY = chartHeight - marginBottom;
    return baseY - scaleBarY(value, maxValue);
};
</script>
