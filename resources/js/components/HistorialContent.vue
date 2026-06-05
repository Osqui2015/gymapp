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
        <div class="flex border-b border-gray-200 dark:border-gray-700 mb-6 gap-6 overflow-x-auto scrollbar-hide">
          <button
            @click="activeTab = 'matrix'"
            :class="activeTab === 'matrix' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 dark:border-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
            class="pb-4 text-sm font-semibold border-b-2 transition-all flex items-center gap-2 whitespace-nowrap"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
            </svg>
            Matriz de Progreso
          </button>
          <button
            @click="activeTab = 'evolution'"
            :class="activeTab === 'evolution' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 dark:border-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
            class="pb-4 text-sm font-semibold border-b-2 transition-all flex items-center gap-2 whitespace-nowrap"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            Evolución Detallada
          </button>
          <button
            @click="activeTab = 'rm_calculator'"
            :class="activeTab === 'rm_calculator' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 dark:border-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
            class="pb-4 text-sm font-semibold border-b-2 transition-all flex items-center gap-2 whitespace-nowrap"
          >
            <span>💪</span> Estimador 1RM
          </button>
          <button
            v-if="isTrainerOrAdmin || hasTrainer"
            @click="activeTab = 'key_exercises'"
            :class="activeTab === 'key_exercises' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 dark:border-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
            class="pb-4 text-sm font-semibold border-b-2 transition-all flex items-center gap-2 whitespace-nowrap"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
            Ejercicios Clave
          </button>
        </div>

        <!-- Vista: Matriz de Progreso -->
        <div v-show="activeTab === 'matrix'" class="space-y-6">
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
                        <span v-if="row.superserie_grupo" class="ml-2 inline-flex items-center rounded-md bg-indigo-50 px-2 py-0.5 text-xs font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-700/10 dark:bg-indigo-900/40 dark:text-indigo-400">
                          Superserie {{ row.superserie_grupo }}
                        </span>
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
        <div v-show="activeTab === 'evolution'" class="space-y-6">
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

        <!-- TAB: Estimador de 1RM -->
        <div v-show="activeTab === 'rm_calculator'" class="grid md:grid-cols-3 gap-8">
          <!-- Calculadora Manual -->
          <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-lg p-5 h-fit md:col-span-1">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
              <span>🧮</span> Calculadora de 1RM
            </h3>
            
            <div class="space-y-4">
              <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Peso Levantado (kg)</label>
                <input
                  v-model.number="calculator.weight"
                  type="number"
                  min="0.1"
                  step="0.5"
                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm"
                />
              </div>

              <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Repeticiones Realizadas</label>
                <input
                  v-model.number="calculator.reps"
                  type="number"
                  min="1"
                  max="30"
                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm"
                />
              </div>

              <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Fórmula de Estimación</label>
                <div class="grid grid-cols-2 gap-2">
                  <button
                    @click="calculator.formula = 'epley'"
                    class="py-1.5 text-xs font-semibold rounded-lg border transition-all"
                    :class="calculator.formula === 'epley' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-transparent text-gray-500 border-gray-300 dark:border-gray-600'"
                  >
                    Epley
                  </button>
                  <button
                    @click="calculator.formula = 'lander'"
                    class="py-1.5 text-xs font-semibold rounded-lg border transition-all"
                    :class="calculator.formula === 'lander' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-transparent text-gray-500 border-gray-300 dark:border-gray-600'"
                  >
                    Lander
                  </button>
                </div>
              </div>

              <!-- Output Box -->
              <div class="mt-6 p-4 bg-indigo-50 dark:bg-indigo-950/20 border border-indigo-100 dark:border-indigo-900/50 rounded-xl text-center">
                <p class="text-xs text-indigo-700 dark:text-indigo-400 uppercase tracking-wider font-bold">1RM Estimado</p>
                <p class="text-4xl font-black text-indigo-600 dark:text-indigo-400 font-mono mt-1">
                  {{ estimated1RM.toFixed(1) }} <span class="text-sm font-normal">kg</span>
                </p>
              </div>

              <!-- Percentage Breakdown Table -->
              <div v-if="estimated1RM > 0" class="mt-4 border-t border-gray-100 dark:border-gray-700 pt-4">
                <p class="text-xs font-bold text-gray-700 dark:text-gray-300 mb-2">Porcentajes del 1RM:</p>
                <div class="grid grid-cols-2 gap-2 text-xs">
                  <div
                    v-for="p in percentages1RM"
                    :key="p.percentage"
                    class="flex justify-between p-2 bg-gray-50 dark:bg-gray-900/50 rounded"
                  >
                    <span class="font-bold text-gray-500 font-mono">{{ p.percentage }}%</span>
                    <span class="font-extrabold text-gray-800 dark:text-gray-200 font-mono">{{ p.weight }} kg</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Récords Personales e Historial de 1RM -->
          <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-lg p-5 md:col-span-2">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6 pb-4 border-b border-gray-100 dark:border-gray-700">
              <div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                  <span>🏆</span> Records e 1RM Históricos
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Estimación en base a las mayores cargas reales registradas</p>
              </div>
              <select
                v-model="rmFormula"
                class="px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 text-xs font-bold"
              >
                <option value="epley">Epley formula</option>
                <option value="lander">Lander formula</option>
              </select>
            </div>

            <div class="overflow-x-auto">
              <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-300 uppercase text-[10px] tracking-wider">
                  <tr>
                    <th class="px-4 py-3 font-bold">Ejercicio</th>
                    <th class="px-4 py-3 font-bold text-center">Record de Carga (PR)</th>
                    <th class="px-4 py-3 font-bold text-right">1RM Estimado</th>
                    <th class="px-4 py-3 font-bold text-center">Fecha del Record</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                  <tr v-if="historical1RMs.length === 0">
                    <td colspan="4" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                      No hay suficientes datos del historial con pesos y repeticiones para estimar records.
                    </td>
                  </tr>
                  <tr
                    v-for="record in historical1RMs"
                    :key="record.name"
                    class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors bg-white dark:bg-gray-800"
                  >
                    <td class="px-4 py-3.5 font-bold text-gray-900 dark:text-white">
                      {{ record.name }}
                    </td>
                    <td class="px-4 py-3.5 text-center text-gray-700 dark:text-gray-300 font-mono">
                      {{ record.weight.toFixed(1) }} kg × {{ record.reps }} reps
                    </td>
                    <td class="px-4 py-3.5 text-right font-black text-indigo-600 dark:text-indigo-400 font-mono text-base">
                      {{ record.rm.toFixed(1) }} kg
                    </td>
                    <td class="px-4 py-3.5 text-center text-xs text-gray-500 dark:text-gray-400 font-medium">
                      {{ record.date }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Vista: Ejercicios Clave -->
        <div v-show="activeTab === 'key_exercises'" class="space-y-6 animate-fadeIn">
          <!-- Banner explicativo -->
          <div class="rounded-xl bg-indigo-50 dark:bg-indigo-950/20 border border-indigo-100 dark:border-indigo-900/50 p-4 flex gap-3">
            <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div>
              <h4 class="font-bold text-indigo-900 dark:text-indigo-200 text-sm">Ejercicios Clave de Seguimiento</h4>
              <p class="text-xs text-indigo-800 dark:text-indigo-300 mt-1">
                {{ isTrainerOrAdmin ? 'Define y monitorea los ejercicios fundamentales para este alumno. Puedes dejar comentarios sobre técnica, cargas y observaciones que el alumno verá en su perfil.' : 'Ejercicios marcados por tu entrenador para priorizar y evaluar de cerca tu evolución de fuerza y 1RM estimado.' }}
              </p>
            </div>
          </div>

          <!-- Formulario para agregar Ejercicio Clave (Trainer / Admin solamente) -->
          <div v-if="isTrainerOrAdmin" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-lg p-5">
            <h3 class="text-base font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
              <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              Designar Nuevo Ejercicio Clave
            </h3>
            
            <form @submit.prevent="saveKeyExercise" class="grid gap-4 md:grid-cols-3 md:items-end">
              <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Nombre del Ejercicio</label>
                <input
                  v-model="newKeyExercise.nombre"
                  list="exercises-list"
                  type="text"
                  placeholder="Escribe o selecciona..."
                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 text-sm"
                  required
                />
                <datalist id="exercises-list">
                  <option v-for="ex in todosEjercicios" :key="ex" :value="ex" />
                </datalist>
              </div>

              <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Observaciones / Técnica</label>
                <input
                  v-model="newKeyExercise.notas"
                  type="text"
                  placeholder="Ej: Foco en romper el paralelo..."
                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 text-sm"
                />
              </div>

              <div>
                <button
                  type="submit"
                  :disabled="savingKeyExercise"
                  class="w-full inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:opacity-50 transition-all gap-2"
                >
                  <span v-if="savingKeyExercise" class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent"></span>
                  <span>{{ savingKeyExercise ? 'Guardando...' : 'Designar Ejercicio' }}</span>
                </button>
              </div>
            </form>
          </div>

          <!-- Spinner para carga de Ejercicios Clave -->
          <div v-if="keyExercisesLoading" class="text-center py-12 text-gray-500 dark:text-gray-400">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600 mx-auto mb-2"></div>
            Cargando ejercicios clave...
          </div>

          <!-- Mensaje cuando no hay ejercicios clave -->
          <div v-else-if="ejerciciosClave.length === 0" class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8 text-center border border-gray-200 dark:border-gray-700">
            <p class="text-gray-500 dark:text-gray-400 text-lg">No hay ejercicios clave designados actualmente.</p>
          </div>

          <!-- Listado de Ejercicios Clave -->
          <div v-else class="grid gap-6 md:grid-cols-2">
            <div
              v-for="ej in ejerciciosClave"
              :key="ej.id"
              class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-lg overflow-hidden flex flex-col justify-between"
            >
              <!-- Card Header -->
              <div class="p-5 border-b border-gray-100 dark:border-gray-700 flex items-start justify-between gap-4">
                <div>
                  <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ ej.ejercicio_nombre }}</h3>
                  <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                    Asignado por: {{ ej.trainer ? ej.trainer.name : 'Entrenador' }}
                  </p>
                </div>
                <button
                  v-if="isTrainerOrAdmin"
                  @click="deleteKeyExercise(ej.id)"
                  class="p-1 rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-950/30 transition-colors"
                  title="Eliminar de Ejercicios Clave"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </div>

              <!-- Card Body: Notas y Gráfico -->
              <div class="p-5 space-y-4 flex-1">
                <!-- Observaciones técnicas del entrenador -->
                <div class="rounded-xl bg-slate-50 dark:bg-slate-900/50 border border-slate-100 dark:border-slate-800 p-4">
                  <div class="flex items-center justify-between gap-2 mb-2">
                    <span class="text-xs font-bold text-slate-500 dark:text-slate-400 flex items-center gap-1">
                      <span>📝</span> NOTAS DEL ENTRENADOR
                    </span>
                    <button
                      v-if="isTrainerOrAdmin && editingNotesId !== ej.id"
                      @click="startEditingNotes(ej)"
                      class="text-xs text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300 font-semibold"
                    >
                      Editar nota
                    </button>
                  </div>

                  <!-- Edición de notas -->
                  <div v-if="editingNotesId === ej.id" class="space-y-2">
                    <textarea
                      v-model="editingNotesValue"
                      class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500"
                      rows="2"
                    ></textarea>
                    <div class="flex justify-end gap-2 text-xs">
                      <button
                        @click="cancelEditingNotes"
                        class="px-2.5 py-1.5 border border-gray-300 dark:border-gray-600 rounded text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 font-medium"
                      >
                        Cancelar
                      </button>
                      <button
                        @click="saveEditingNotes(ej)"
                        class="px-2.5 py-1.5 bg-indigo-600 text-white rounded hover:bg-indigo-500 font-semibold shadow-sm"
                      >
                        Guardar
                      </button>
                    </div>
                  </div>

                  <!-- Visualización de notas -->
                  <p v-else class="text-xs md:text-sm text-slate-700 dark:text-slate-300 italic whitespace-pre-line">
                    {{ ej.notas_trainer || 'Sin observaciones o notas técnicas registradas.' }}
                  </p>
                </div>

                <!-- Gráfico de progreso 1RM -->
                <div>
                  <p class="text-xs font-bold text-gray-500 dark:text-gray-400 mb-2">Evolución de 1RM Estimado (Fórmula: {{ rmFormula === 'epley' ? 'Epley' : 'Lander' }})</p>
                  
                  <div v-if="getExercise1RMTimeline(ej.ejercicio_nombre).length > 0" class="relative w-full bg-slate-50 dark:bg-slate-900/30 rounded-xl p-3 min-h-[220px] border border-slate-100 dark:border-slate-800">
                    <canvas :id="'keyChart-' + ej.id" class="w-full max-h-[220px]"></canvas>
                  </div>
                  
                  <div v-else class="text-center py-8 rounded-xl bg-slate-50 dark:bg-slate-900/30 border border-slate-100 dark:border-slate-800 text-xs text-gray-400 dark:text-gray-500">
                    Sin datos en el historial para trazar el gráfico de este ejercicio.
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
import { computed, onMounted, ref, watch, nextTick } from 'vue';
import axios from 'axios';
import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

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
const hasTrainer = ref(false);
const activeTab = ref('matrix');
const dateSortAsc = ref(true);

// Ejercicios Clave State
const ejerciciosClave = ref([]);
const todosEjercicios = ref([]);
const keyExercisesLoading = ref(false);
const newKeyExercise = ref({ nombre: '', notas: '' });
const savingKeyExercise = ref(false);
const editingNotesId = ref(null);
const editingNotesValue = ref('');
const chartInstances = {};

// 1RM Calculator State
const calculator = ref({
  weight: 80,
  reps: 5,
  formula: 'epley'
});

const estimated1RM = computed(() => {
  const w = parseFloat(calculator.value.weight);
  const r = parseInt(calculator.value.reps);
  if (!w || !r || w <= 0 || r <= 0) return 0;

  if (calculator.value.formula === 'epley') {
    return w * (1 + r / 30);
  } else {
    return (100 * w) / (101.3 - 2.6712 * r);
  }
});

const percentages1RM = computed(() => {
  const rm = estimated1RM.value;
  if (!rm) return [];
  const percentages = [95, 90, 85, 80, 75, 70, 65, 60];
  return percentages.map(p => ({
    percentage: p,
    weight: (rm * (p / 100)).toFixed(1)
  }));
});

const calculate1RMValue = (w, r, formula) => {
  if (!w || !r || w <= 0 || r <= 0) return 0;
  if (formula === 'epley') {
    return w * (1 + r / 30);
  } else {
    return (100 * w) / (101.3 - 2.6712 * r);
  }
};

const rmFormula = ref('epley');

const historical1RMs = computed(() => {
  if (!historial.value || !historial.value.length) return [];

  const exerciseMaxes = {};

  historial.value.forEach(row => {
    const w = parseFloat(row.peso);
    const r = parseInt(row.reps_realizadas);
    if (!w || !r || w <= 0 || r <= 0) return;

    const rmVal = calculate1RMValue(w, r, rmFormula.value);

    if (!exerciseMaxes[row.ejercicio_nombre] || rmVal > exerciseMaxes[row.ejercicio_nombre].rm) {
      exerciseMaxes[row.ejercicio_nombre] = {
        name: row.ejercicio_nombre,
        weight: w,
        reps: r,
        rm: rmVal,
        date: formatDate(row.fecha)
      };
    }
  });

  return Object.values(exerciseMaxes).sort((a, b) => b.rm - a.rm);
});

const toggleDateSort = () => {
  dateSortAsc.value = !dateSortAsc.value;
};

const fetchUserInfo = async () => {
  try {
    const res = await axios.get('/api/user-info');
    userRole.value = res.data.role;
    hasTrainer.value = !!res.data.has_trainer;
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
  await Promise.all([loadHistorial(), loadKeyExercises()]);
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

    const superserie_grupo = exerciseRows.find(row => row.superserie_grupo !== null)?.superserie_grupo || null;

    return {
      name: exerciseName,
      weights: dateWeights,
      superserie_grupo
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
  const date = new Date(value + 'T00:00:00');
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
      
      const superserie_grupo = dayRows.find(row => row.superserie_grupo !== null)?.superserie_grupo || null;

      result.push({
        nombre: ejercicio.nombre,
        fecha: sesion.fechaLabel,
        dia: sesion.diaLabel,
        rawFecha: new Date(sesion.fecha),
        maxWeight: maxWeight.toFixed(1),
        avgWeight: sesion.pesoPromedio,
        seriesCount: dayRows.length,
        superserie_grupo: superserie_grupo
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

const loadKeyExercises = async () => {
  if (!isTrainerOrAdmin.value && !hasTrainer.value) return;
  keyExercisesLoading.value = true;
  try {
    const params = {};
    if (isTrainerOrAdmin.value && selectedAlumnoId.value) {
      params.user_id = selectedAlumnoId.value;
    }
    const res = await axios.get('/api/ejercicios-clave', { params });
    ejerciciosClave.value = res.data.ejercicios_clave || [];
    todosEjercicios.value = res.data.todos_ejercicios || [];
    
    if (activeTab.value === 'key_exercises') {
      nextTick(() => {
        initKeyCharts();
      });
    }
  } catch (err) {
    console.error('Error loading key exercises:', err);
  } finally {
    keyExercisesLoading.value = false;
  }
};

const saveKeyExercise = async () => {
  if (!newKeyExercise.value.nombre.trim()) return;
  savingKeyExercise.value = true;
  try {
    await axios.post('/api/ejercicios-clave', {
      user_id: selectedAlumnoId.value,
      ejercicio_nombre: newKeyExercise.value.nombre.trim(),
      notas_trainer: newKeyExercise.value.notas.trim() || null,
    });
    newKeyExercise.value.nombre = '';
    newKeyExercise.value.notas = '';
    await loadKeyExercises();
  } catch (err) {
    console.error('Error saving key exercise:', err);
    alert('Error al guardar el ejercicio clave');
  } finally {
    savingKeyExercise.value = false;
  }
};

const deleteKeyExercise = async (id) => {
  if (!confirm('¿Estás seguro de que deseas eliminar este ejercicio clave?')) return;
  try {
    await axios.delete(`/api/ejercicios-clave/${id}`);
    await loadKeyExercises();
  } catch (err) {
    console.error('Error deleting key exercise:', err);
    alert('Error al eliminar el ejercicio clave');
  }
};

const startEditingNotes = (ej) => {
  editingNotesId.value = ej.id;
  editingNotesValue.value = ej.notas_trainer || '';
};

const cancelEditingNotes = () => {
  editingNotesId.value = null;
  editingNotesValue.value = '';
};

const saveEditingNotes = async (ej) => {
  try {
    await axios.post('/api/ejercicios-clave', {
      user_id: ej.user_id,
      ejercicio_nombre: ej.ejercicio_nombre,
      notas_trainer: editingNotesValue.value.trim() || null,
    });
    editingNotesId.value = null;
    editingNotesValue.value = '';
    await loadKeyExercises();
  } catch (err) {
    console.error('Error updating notes:', err);
    alert('Error al actualizar las notas');
  }
};

const getExercise1RMTimeline = (exerciseName) => {
  const rows = historial.value.filter(row => row.ejercicio_nombre === exerciseName);
  if (!rows.length) return [];

  const dateMap = {};
  rows.forEach(row => {
    const w = parseFloat(row.peso);
    const r = parseInt(row.reps_realizadas);
    if (!w || !r || w <= 0 || r <= 0) return;

    const rmVal = calculate1RMValue(w, r, rmFormula.value);

    if (!dateMap[row.fecha] || rmVal > dateMap[row.fecha].rm) {
      dateMap[row.fecha] = {
        fecha: row.fecha,
        rm: rmVal,
        weight: w,
        reps: r,
        dia: row.dia || 'Día',
      };
    }
  });

  return Object.values(dateMap).sort((a, b) => new Date(a.fecha) - new Date(b.fecha));
};

const initKeyCharts = () => {
  // Destroy existing key exercise charts
  Object.keys(chartInstances).forEach(key => {
    if (chartInstances[key]) {
      chartInstances[key].destroy();
      delete chartInstances[key];
    }
  });

  // Loop through each key exercise
  ejerciciosClave.value.forEach(ej => {
    const canvasId = 'keyChart-' + ej.id;
    const ctx = document.getElementById(canvasId);
    if (!ctx) return;

    const timeline = getExercise1RMTimeline(ej.ejercicio_nombre);
    if (timeline.length === 0) return;

    const labels = timeline.map(t => {
      const date = new Date(t.fecha + 'T00:00:00');
      return date.toLocaleDateString('es-ES', { day: '2-digit', month: 'short' });
    });
    const dataValues = timeline.map(t => parseFloat(t.rm.toFixed(1)));

    const gridColor = document.documentElement.classList.contains('dark') ? '#374151' : '#e2e8f0';
    const textColor = document.documentElement.classList.contains('dark') ? '#9ca3af' : '#4b5563';

    const canvasCtx = ctx.getContext('2d');
    const gradient = canvasCtx.createLinearGradient(0, 0, 0, 200);
    gradient.addColorStop(0, 'rgba(99, 102, 241, 0.25)');
    gradient.addColorStop(1, 'rgba(99, 102, 241, 0.0)');

    chartInstances[ej.id] = new Chart(ctx, {
      type: 'line',
      data: {
        labels: labels,
        datasets: [{
          label: '1RM Estimado (kg)',
          data: dataValues,
          borderColor: '#6366f1',
          borderWidth: 2.5,
          backgroundColor: gradient,
          fill: true,
          tension: 0.35,
          pointBackgroundColor: '#6366f1',
          pointBorderColor: '#ffffff',
          pointBorderWidth: 1.5,
          pointRadius: 4,
          pointHoverRadius: 6,
          pointHoverBackgroundColor: '#4f46e5',
          pointHoverBorderColor: '#ffffff',
          pointHoverBorderWidth: 2
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: false
          },
          tooltip: {
            backgroundColor: '#1f2937',
            titleFont: { size: 11, weight: 'bold' },
            bodyFont: { size: 11 },
            padding: 8,
            cornerRadius: 8,
            displayColors: false,
            callbacks: {
              label: function(context) {
                const index = context.dataIndex;
                const point = timeline[index];
                return ` 1RM: ${context.parsed.y} kg (${point.weight} kg x ${point.reps} reps)`;
              }
            }
          }
        },
        scales: {
          y: {
            grid: {
              color: gridColor,
              drawBorder: false
            },
            ticks: {
              color: textColor,
              font: { size: 10, family: 'ui-sans-serif, system-ui' }
            }
          },
          x: {
            grid: {
              display: false
            },
            ticks: {
              color: textColor,
              font: { size: 10, family: 'ui-sans-serif, system-ui' }
            }
          }
        }
      }
    });
  });
};

watch(activeTab, (newTab) => {
  if (newTab === 'key_exercises') {
    nextTick(() => {
      initKeyCharts();
    });
  }
});

watch(rmFormula, () => {
  if (activeTab.value === 'key_exercises') {
    nextTick(() => {
      initKeyCharts();
    });
  }
});

onMounted(async () => {
  await fetchUserInfo();
  if (!isTrainerOrAdmin.value) {
    await Promise.all([loadHistorial(), loadKeyExercises()]);
  } else if (selectedAlumnoId.value) {
    await Promise.all([loadHistorial(), loadKeyExercises()]);
  } else {
    loading.value = false;
  }
});
</script>

<style scoped>
svg {
  user-select: none;
}
.scrollbar-hide::-webkit-scrollbar {
  display: none;
}
.scrollbar-hide {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>
