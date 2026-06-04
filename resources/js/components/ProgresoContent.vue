<template>
  <div class="min-h-screen bg-gradient-to-br from-gray-50 to-indigo-50 dark:from-gray-900 dark:to-indigo-900/40 py-8">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
          <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white flex items-center gap-2">
            <span>📊</span> Progreso & Evolución
          </h1>
          <p class="mt-2 text-gray-600 dark:text-gray-400">Controla tus medidas, metas personales y logros desbloqueados</p>
        </div>
        <a
          href="/dashboard"
          class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-xl border border-gray-200 dark:border-gray-700 font-semibold text-sm shadow-sm transition-all"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          Volver al Dashboard
        </a>
      </div>

      <!-- Navigation Tabs -->
      <div class="flex border-b border-gray-200 dark:border-gray-700 mb-8 gap-6 overflow-x-auto scrollbar-hide">
        <button
          @click="activeTab = 'medidas'"
          :class="[
            activeTab === 'medidas'
              ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 dark:border-indigo-400'
              : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300',
            'pb-4 text-sm font-semibold border-b-2 transition-all flex items-center gap-2 whitespace-nowrap'
          ]"
        >
          <span>📏</span> Medidas Corporales
        </button>
        <button
          @click="activeTab = 'metas'"
          :class="[
            activeTab === 'metas'
              ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 dark:border-indigo-400'
              : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300',
            'pb-4 text-sm font-semibold border-b-2 transition-all flex items-center gap-2 whitespace-nowrap'
          ]"
        >
          <span>🎯</span> Metas Personales
        </button>
        <button
          @click="activeTab = 'logros'"
          :class="[
            activeTab === 'logros'
              ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 dark:border-indigo-400'
              : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300',
            'pb-4 text-sm font-semibold border-b-2 transition-all flex items-center gap-2 whitespace-nowrap'
          ]"
        >
          <span>🏆</span> Medallas y Logros
        </button>
      </div>

      <!-- TAB: Medidas Corporales -->
      <div v-show="activeTab === 'medidas'">
        <!-- Mensaje de recordatorio de 15 días -->
        <div
          v-if="puedeRegistrar && progresos.length > 0"
          class="mb-6 p-4 bg-amber-50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-900/50 rounded-xl animate-pulse"
        >
          <div class="flex items-center gap-3">
            <div class="flex-shrink-0">
              <svg class="w-6 h-6 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div>
              <p class="font-bold text-amber-800 dark:text-amber-200 text-sm">¡Es hora de tu medición!</p>
              <p class="text-xs text-amber-700 dark:text-amber-300 mt-0.5">Han pasado más de 15 días desde tu último registro. Ingresa tus nuevas medidas para ver tu progreso.</p>
            </div>
          </div>
        </div>

        <!-- Información del último registro -->
        <div
          v-if="ultimoRegistro && !puedeRegistrar"
          class="mb-6 p-4 bg-green-50 dark:bg-green-950/20 border border-green-200 dark:border-green-900/50 rounded-xl"
        >
          <div class="flex items-center justify-between flex-wrap gap-2 text-sm">
            <div class="flex items-center gap-3">
              <div class="flex-shrink-0">
                <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <div>
                <p class="font-bold text-green-800 dark:text-green-200">Último registro</p>
                <p class="text-xs text-green-700 dark:text-green-300" id="fecha-ultimo">
                  {{ formatFecha(ultimoRegistro.fecha) }}
                </p>
              </div>
            </div>
            <div class="text-xs font-semibold text-green-700 dark:text-green-400">
              Podrás registrar un nuevo progreso en {{ diasRestantesParaRegistrar }} días
            </div>
          </div>
        </div>

        <!-- Sección de Gráfico de Evolución (Interactive Chart.js) -->
        <div v-show="progresos.length > 1" class="mb-8 bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-5 border border-gray-100 dark:border-gray-700">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
              <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <span>📈</span> Gráfico de Evolución Física
              </h3>
              <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Monitorea los cambios de tus medidas en el tiempo de forma interactiva</p>
            </div>
            <select
              v-model="metricaGrafica"
              class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm font-semibold"
            >
              <option value="peso">Peso (kg)</option>
              <option value="cintura">Cintura (cm)</option>
              <option value="brazos">Brazos/Bíceps (cm)</option>
              <option value="pecho">Pecho (cm)</option>
              <option value="hombros">Hombros (cm)</option>
              <option value="muslos">Muslos (cm)</option>
            </select>
          </div>

          <!-- Chart.js Canvas -->
          <div class="relative w-full bg-gray-50 dark:bg-gray-900/30 rounded-xl p-4 min-h-[300px]">
            <canvas id="progresoChart" class="w-full max-h-[300px]"></canvas>
          </div>
        </div>

        <div class="grid lg:grid-cols-2 gap-8">
          <!-- Formulario de Registro -->
          <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-4 sm:p-6 border border-gray-100 dark:border-gray-700 h-fit">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
              <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
              </svg>
              Registrar Medidas
            </h2>

            <form @submit.prevent="guardarProgreso" class="space-y-6">
              <!-- Datos Personales -->
              <div class="border-b border-gray-200 dark:border-gray-700 pb-6">
                <h3 class="text-base font-semibold text-gray-700 dark:text-gray-300 mb-4 flex items-center gap-2">
                  <span>📋</span> Datos Personales
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Peso (kg)</label>
                    <input
                      v-model.number="form.peso"
                      type="number"
                      step="0.01"
                      class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm"
                      placeholder="Ej: 75.5"
                      :required="progresos.length === 0"
                    />
                  </div>
                  <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Altura (cm)</label>
                    <input
                      v-model.number="form.altura"
                      type="number"
                      step="0.01"
                      class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm"
                      placeholder="Ej: 175"
                      :required="progresos.length === 0"
                    />
                  </div>
                  <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Edad</label>
                    <input
                      v-model.number="form.edad"
                      type="number"
                      class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm"
                      placeholder="Ej: 25"
                      :required="progresos.length === 0"
                    />
                  </div>
                  <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Sexo</label>
                    <select
                      v-model="form.sexo"
                      class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm"
                      :required="progresos.length === 0"
                    >
                      <option value="">Seleccionar</option>
                      <option value="masculino">Masculino</option>
                      <option value="femenino">Femenino</option>
                    </select>
                  </div>
                </div>
              </div>

              <!-- Medidas Corporales -->
              <div>
                <h3 class="text-base font-semibold text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-2">
                  <span>📐</span> Medidas Corporales (Lado Derecho)
                </h3>
                <p class="text-[11px] text-gray-500 dark:text-gray-400 mb-4 italic">Mide siempre del mismo lado para mantener la consistencia</p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <!-- Cuello -->
                  <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                      Cuello (cm)
                      <span class="text-[10px] text-gray-400">(Bajo la nuez)</span>
                    </label>
                    <input
                      v-model.number="form.cuello"
                      type="number"
                      step="0.1"
                      class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm"
                      placeholder="Ej: 38"
                    />
                  </div>

                  <!-- Hombros -->
                  <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                      Hombros (cm)
                      <span class="text-[10px] text-gray-400">(Contorno completo)</span>
                    </label>
                    <input
                      v-model.number="form.hombros"
                      type="number"
                      step="0.1"
                      class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm"
                      placeholder="Ej: 110"
                    />
                  </div>

                  <!-- Pecho -->
                  <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                      Pecho (cm)
                      <span class="text-[10px] text-gray-400">(Prominente)</span>
                    </label>
                    <input
                      v-model.number="form.pecho"
                      type="number"
                      step="0.1"
                      class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm"
                      placeholder="Ej: 100"
                    />
                  </div>

                  <!-- Brazos -->
                  <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                      Brazos/Bíceps (cm)
                      <span class="text-[10px] text-gray-400">(Parte más gruesa)</span>
                    </label>
                    <input
                      v-model.number="form.brazos"
                      type="number"
                      step="0.1"
                      class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm"
                      placeholder="Ej: 35"
                    />
                  </div>

                  <!-- Cintura -->
                  <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                      Cintura (cm)
                      <span class="text-[10px] text-gray-400">(Pérdida de grasa)</span>
                    </label>
                    <input
                      v-model.number="form.cintura"
                      type="number"
                      step="0.1"
                      class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm"
                      placeholder="Ej: 85"
                    />
                  </div>

                  <!-- Cadera -->
                  <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                      Cadera/Glúteos (cm)
                      <span class="text-[10px] text-gray-400">(Parte más ancha)</span>
                    </label>
                    <input
                      v-model.number="form.cadera"
                      type="number"
                      step="0.1"
                      class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm"
                      placeholder="Ej: 95"
                    />
                  </div>

                  <!-- Muslos -->
                  <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                      Muslos (cm)
                      <span class="text-[10px] text-gray-400">(Parte más gruesa)</span>
                    </label>
                    <input
                      v-model.number="form.muslos"
                      type="number"
                      step="0.1"
                      class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm"
                      placeholder="Ej: 55"
                    />
                  </div>

                  <!-- Pantorrillas -->
                  <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                      Pantorrillas (cm)
                      <span class="text-[10px] text-gray-400">(Parte más ancha)</span>
                    </label>
                    <input
                      v-model.number="form.pantorrillas"
                      type="number"
                      step="0.1"
                      class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm"
                      placeholder="Ej: 38"
                    />
                  </div>
                </div>
              </div>

              <button
                type="submit"
                :disabled="guardando || !puedeRegistrar"
                class="w-full py-3 px-4 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-bold rounded-xl hover:from-indigo-700 hover:to-purple-700 transition-all shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none flex items-center justify-center gap-2"
              >
                <svg v-if="guardando" class="animate-spin w-5 h-5 text-white" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>{{ guardando ? 'Guardando...' : 'Guardar Progreso' }}</span>
              </button>
            </form>
          </div>

          <!-- Historial de Progreso -->
          <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-4 sm:p-6 border border-gray-100 dark:border-gray-700">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
              <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
              </svg>
              Historial de Progreso
            </h2>

            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                  <tr>
                    <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Fecha</th>
                    <th class="px-3 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Peso</th>
                    <th class="px-3 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Cintura</th>
                    <th class="px-3 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Pecho</th>
                    <th class="px-3 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Brazos</th>
                    <th class="px-3 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Acción</th>
                  </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                  <tr v-if="progresos.length === 0">
                    <td colspan="6" class="px-3 py-8 text-center text-gray-500 dark:text-gray-400">
                      <svg class="mx-auto w-12 h-12 text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                      </svg>
                      <p class="font-semibold text-sm">No hay registros aún</p>
                      <p class="text-xs mt-1">Ingresa tus medidas para comenzar</p>
                    </td>
                  </tr>
                  <tr
                    v-for="(p, index) in progresos"
                    :key="p.id"
                    :class="[index % 2 === 0 ? 'bg-white dark:bg-gray-800' : 'bg-gray-50/50 dark:bg-gray-700/30']"
                    class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                  >
                    <td class="px-3 py-3 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-white">
                      {{ formatFecha(p.fecha) }}
                    </td>
                    <td class="px-3 py-3 whitespace-nowrap text-sm text-center text-gray-600 dark:text-gray-300 font-mono">
                      {{ p.peso ? p.peso + ' kg' : '-' }}
                    </td>
                    <td class="px-3 py-3 whitespace-nowrap text-sm text-center text-gray-600 dark:text-gray-300 font-mono">
                      {{ p.cintura ? p.cintura + ' cm' : '-' }}
                    </td>
                    <td class="px-3 py-3 whitespace-nowrap text-sm text-center text-gray-600 dark:text-gray-300 font-mono">
                      {{ p.pecho ? p.pecho + ' cm' : '-' }}
                    </td>
                    <td class="px-3 py-3 whitespace-nowrap text-sm text-center text-gray-600 dark:text-gray-300 font-mono">
                      {{ p.brazos ? p.brazos + ' cm' : '-' }}
                    </td>
                    <td class="px-3 py-3 whitespace-nowrap text-sm text-center">
                      <button
                        @click="verDetalle(p.id)"
                        class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 font-semibold transition-colors"
                      >
                        Ver
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Tips de Medición -->
        <div class="mt-8 bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-900/10 dark:to-purple-900/10 rounded-2xl p-4 sm:p-6 border border-indigo-100 dark:border-indigo-900/30">
          <h3 class="text-lg font-bold text-indigo-900 dark:text-indigo-100 mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Tips para tomar tus medidas
          </h3>
          <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4 text-xs text-indigo-800 dark:text-indigo-200">
            <div class="flex items-start gap-2">
              <span class="text-indigo-500 font-bold">1.</span>
              <p>Usa siempre una cinta métrica flexible y el <strong>lado derecho</strong> de tu cuerpo.</p>
            </div>
            <div class="flex items-start gap-2">
              <span class="text-indigo-500 font-bold">2.</span>
              <p>Mide a primera hora de la mañana, después de ir al baño.</p>
            </div>
            <div class="flex items-start gap-2">
              <span class="text-indigo-500 font-bold">3.</span>
              <p>No presiones la cinta, debe estar cómoda pero ajustada.</p>
            </div>
            <div class="flex items-start gap-2">
              <span class="text-indigo-500 font-bold">4.</span>
              <p>Mantén los brazos a los lados del cuerpo al medir hombros.</p>
            </div>
            <div class="flex items-start gap-2">
              <span class="text-indigo-500 font-bold">5.</span>
              <p>Respira normalmente al medir el pecho.</p>
            </div>
            <div class="flex items-start gap-2">
              <span class="text-indigo-500 font-bold">6.</span>
              <p>Si puedes, pide ayuda para las medidas de hombros.</p>
            </div>
          </div>
        </div>
      </div>

      <!-- TAB: Metas Personales -->
      <div v-show="activeTab === 'metas'" class="space-y-8 animate-fadeIn">
        <div class="grid md:grid-cols-3 gap-8">
          <!-- Crear Meta -->
          <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-5 border border-gray-100 dark:border-gray-700 h-fit md:col-span-1">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
              <span>🎯</span> Establecer Meta
            </h3>
            <form @submit.prevent="crearMeta" class="space-y-4">
              <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Métrica Objetivo</label>
                <select
                  v-model="nuevaMeta.tipo"
                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm"
                  required
                >
                  <option value="entrenamiento_semanal">Entrenamientos Semanales (Sesiones)</option>
                  <option value="peso_corporal">Peso Corporal (kg)</option>
                  <option value="cintura_corporal">Medida de Cintura (cm)</option>
                  <option value="brazos_corporal">Medida de Brazos/Bíceps (cm)</option>
                  <option value="pecho_corporal">Medida de Pecho (cm)</option>
                  <option value="otro">Otro</option>
                </select>
              </div>

              <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Valor Objetivo</label>
                <input
                  v-model.number="nuevaMeta.valor_objetivo"
                  type="number"
                  step="0.01"
                  min="0.1"
                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm"
                  placeholder="Ej: 3 (entrenamientos) o 72.5 (kg)"
                  required
                />
              </div>

              <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Descripción / Notas</label>
                <input
                  v-model="nuevaMeta.descripcion"
                  type="text"
                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm"
                  placeholder="Ej: Entrenar 3 veces por semana para consistencia"
                  required
                />
              </div>

              <button
                type="submit"
                :disabled="creandoMeta"
                class="w-full py-2.5 px-4 bg-indigo-600 text-white font-bold rounded-xl hover:bg-indigo-700 transition-all shadow-md flex items-center justify-center gap-2 text-sm"
              >
                <span v-if="creandoMeta">Procesando...</span>
                <span v-else>Establecer Objetivo</span>
              </button>
            </form>
          </div>

          <!-- Listado de Metas -->
          <div class="md:col-span-2 space-y-4">
            <div class="flex justify-between items-center mb-2">
              <h3 class="text-lg font-bold text-gray-900 dark:text-white">Mis Objetivos</h3>
              <span class="px-2.5 py-1 text-xs font-bold rounded-full bg-indigo-100 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300">
                {{ metas.filter(m => m.completada).length }} / {{ metas.length }} completados
              </span>
            </div>

            <div v-if="metas.length === 0" class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 border border-gray-100 dark:border-gray-700 text-center text-gray-500 dark:text-gray-400">
              <span class="text-4xl block mb-2">🎯</span>
              <p class="font-bold">No has definido metas personales todavía.</p>
              <p class="text-xs mt-1">Establece objetivos de peso, medidas o entrenamiento para mantenerte motivado.</p>
            </div>

            <div v-else class="grid sm:grid-cols-2 gap-4">
              <div
                v-for="meta in metas"
                :key="meta.id"
                class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-100 dark:border-gray-700 shadow-md relative overflow-hidden transition-all hover:shadow-lg flex flex-col justify-between"
                :class="{'ring-2 ring-emerald-500/50 dark:ring-emerald-400/30': meta.completada}"
              >
                <!-- Badge Completada -->
                <div v-if="meta.completada" class="absolute top-0 right-0 bg-emerald-500 text-white text-[9px] font-black uppercase px-2 py-1 rounded-bl-lg shadow-sm">
                  Alcanzada
                </div>

                <div>
                  <div class="flex items-center gap-2.5 mb-2">
                    <span class="text-2xl">{{ getMetaEmoji(meta.tipo) }}</span>
                    <h4 class="font-bold text-sm text-gray-900 dark:text-white uppercase tracking-wide">
                      {{ formatMetaTipo(meta.tipo) }}
                    </h4>
                  </div>
                  <p class="text-xs text-gray-600 dark:text-gray-400 mb-3">{{ meta.descripcion }}</p>
                  <p class="text-sm font-black text-indigo-600 dark:text-indigo-400 font-mono mb-4">
                    Objetivo: {{ parseFloat(meta.valor_objetivo) }}
                  </p>
                </div>

                <div class="flex gap-2 border-t border-gray-100 dark:border-gray-700 pt-3">
                  <button
                    @click="toggleMetaCompletada(meta)"
                    class="flex-1 py-1.5 px-3 rounded-lg text-xs font-bold transition-all flex items-center justify-center gap-1.5"
                    :class="meta.completada 
                      ? 'bg-amber-100 hover:bg-amber-200 text-amber-800 dark:bg-amber-950/30 dark:text-amber-300' 
                      : 'bg-emerald-600 hover:bg-emerald-700 text-white'"
                  >
                    <span v-if="meta.completada">Reabrir</span>
                    <span v-else>✓ Lograda</span>
                  </button>
                  <button
                    @click="eliminarMeta(meta.id)"
                    class="p-1.5 rounded-lg bg-red-50 hover:bg-red-100 dark:bg-red-950/20 text-red-600 dark:text-red-400 hover:text-red-800 transition-colors"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- TAB: Medallas y Logros -->
      <div v-show="activeTab === 'logros'" class="space-y-8 animate-fadeIn">
        <!-- Stats Summary -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4" v-if="logrosStats">
          <div class="rounded-2xl border border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-md text-center">
            <span class="text-2xl block mb-1">🏋️‍♂️</span>
            <p class="text-xs text-gray-500 dark:text-gray-400">Series Completadas</p>
            <p class="mt-1 text-2xl font-black text-indigo-600 dark:text-indigo-400 font-mono">{{ logrosStats.total_series }}</p>
          </div>
          <div class="rounded-2xl border border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-md text-center">
            <span class="text-2xl block mb-1">🔥</span>
            <p class="text-xs text-gray-500 dark:text-gray-400">Racha Actual</p>
            <p class="mt-1 text-2xl font-black text-amber-600 dark:text-amber-400 font-mono">{{ logrosStats.streak }} días</p>
          </div>
          <div class="rounded-2xl border border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-md text-center">
            <span class="text-2xl block mb-1">📅</span>
            <p class="text-xs text-gray-500 dark:text-gray-400">Días Entrenados</p>
            <p class="mt-1 text-2xl font-black text-green-600 dark:text-green-400 font-mono">{{ logrosStats.unique_days }}</p>
          </div>
          <div class="rounded-2xl border border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-md text-center">
            <span class="text-2xl block mb-1">🎯</span>
            <p class="text-xs text-gray-500 dark:text-gray-400">Objetivos Alcanzados</p>
            <p class="mt-1 text-2xl font-black text-purple-600 dark:text-purple-400 font-mono">{{ logrosStats.completed_goals_count }}</p>
          </div>
        </div>

        <!-- Badges Grid -->
        <div class="space-y-4">
          <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
            <span>🏆</span> Vitrina de Medallas
          </h3>
          <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-6">
            <div
              v-for="logro in logros"
              :key="logro.slug"
              class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-100 dark:border-gray-700 shadow-md flex flex-col items-center text-center transition-all hover:scale-105"
              :class="{'opacity-60 grayscale dark:border-gray-800': !logro.desbloqueada, 'ring-2 ring-indigo-500/30 bg-gradient-to-br from-white to-indigo-50/20 dark:from-gray-800 dark:to-indigo-900/10': logro.desbloqueada}"
            >
              <!-- Icon/Badge Container -->
              <div
                class="w-16 h-16 rounded-full flex items-center justify-center text-3xl mb-4 relative shadow-inner"
                :class="logro.desbloqueada ? 'bg-indigo-100 dark:bg-indigo-950/60' : 'bg-gray-100 dark:bg-gray-700'"
              >
                <span>{{ logro.icono }}</span>
                <!-- Lock Icon for Locked medals -->
                <div v-if="!logro.desbloqueada" class="absolute -bottom-1 -right-1 bg-gray-600 text-white rounded-full p-1 text-[10px] w-5 h-5 flex items-center justify-center">
                  🔒
                </div>
              </div>

              <h4 class="font-extrabold text-sm text-gray-800 dark:text-white mb-1">{{ logro.nombre }}</h4>
              <p class="text-xs text-gray-500 dark:text-gray-400 mb-4 h-10 flex items-center justify-center">{{ logro.descripcion }}</p>

              <!-- Progress bar for locked, or unlocked date -->
              <div class="w-full mt-auto">
                <div v-if="logro.desbloqueada" class="text-[10px] font-semibold text-emerald-600 dark:text-emerald-400">
                  Desbloqueada el {{ formatFechaMedalla(logro.ganado_at) }}
                </div>
                <div v-else class="space-y-1">
                  <div class="h-1.5 w-full bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                    <div
                      class="h-full bg-indigo-600 rounded-full"
                      :style="{ width: `${(logro.progreso / logro.objetivo) * 100}%` }"
                    ></div>
                  </div>
                  <div class="text-[9px] font-mono text-gray-400">
                    {{ logro.progreso }} / {{ logro.objetivo }}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal de Detalle de Medidas -->
    <div
      v-if="modalDetalle.mostrar"
      class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4"
      @click.self="cerrarModal"
    >
      <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto border border-gray-100 dark:border-gray-700 animate-scaleIn">
        <div class="p-6">
          <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-100 dark:border-gray-700">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
              <span>📋</span> Detalle de Progreso: {{ formatFecha(modalDetalle.progreso.fecha) }}
            </h3>
            <button @click="cerrarModal" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition-colors">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Contenido del Detalle -->
          <div class="space-y-6">
            <!-- Datos Personales -->
            <div
              v-if="modalDetalle.progreso.peso || modalDetalle.progreso.altura || modalDetalle.progreso.edad || modalDetalle.progreso.sexo"
              class="border-b border-gray-100 dark:border-gray-700 pb-4"
            >
              <h4 class="font-bold text-sm text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
                <span>👤</span> Datos Generales
              </h4>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                <div v-if="modalDetalle.progreso.peso" class="flex justify-between items-center p-2.5 rounded-lg bg-gray-50 dark:bg-gray-900/50">
                  <span class="text-gray-500">Peso:</span>
                  <div class="flex items-center gap-2">
                    <span class="font-bold font-mono">{{ modalDetalle.progreso.peso }} kg</span>
                    <span
                      v-if="modalDetalle.comparacion.peso && modalDetalle.comparacion.peso.diferencia !== null"
                      :class="[
                        modalDetalle.comparacion.peso.diferencia > 0 ? 'text-green-600 bg-green-50 dark:bg-green-950/20' : 
                        modalDetalle.comparacion.peso.diferencia < 0 ? 'text-red-600 bg-red-50 dark:bg-red-950/20' : 'text-gray-500 bg-gray-100 dark:bg-gray-800',
                        'text-xs font-semibold px-2 py-0.5 rounded-full'
                      ]"
                    >
                      {{ modalDetalle.comparacion.peso.diferencia > 0 ? '+' : '' }}{{ modalDetalle.comparacion.peso.diferencia }}
                    </span>
                  </div>
                </div>

                <div v-if="modalDetalle.progreso.altura" class="flex justify-between items-center p-2.5 rounded-lg bg-gray-50 dark:bg-gray-900/50">
                  <span class="text-gray-500">Altura:</span>
                  <span class="font-bold font-mono">{{ modalDetalle.progreso.altura }} cm</span>
                </div>

                <div v-if="modalDetalle.progreso.edad" class="flex justify-between items-center p-2.5 rounded-lg bg-gray-50 dark:bg-gray-900/50">
                  <span class="text-gray-500">Edad:</span>
                  <span class="font-bold font-mono">{{ modalDetalle.progreso.edad }} años</span>
                </div>

                <div v-if="modalDetalle.progreso.sexo" class="flex justify-between items-center p-2.5 rounded-lg bg-gray-50 dark:bg-gray-900/50">
                  <span class="text-gray-500">Sexo:</span>
                  <span class="font-bold capitalize">{{ modalDetalle.progreso.sexo }}</span>
                </div>
              </div>
            </div>

            <!-- Medidas Corporales -->
            <div>
              <h4 class="font-bold text-sm text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
                <span>📏</span> Medidas Corporales
              </h4>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                <div
                  v-for="campo in camposMedidas"
                  :key="campo"
                  v-show="modalDetalle.comparacion[campo]"
                  class="flex justify-between items-center p-2.5 rounded-lg bg-gray-50 dark:bg-gray-900/50"
                >
                  <span class="text-gray-500 capitalize">{{ labelCampos[campo] }}:</span>
                  <div class="flex items-center gap-2" v-if="modalDetalle.comparacion[campo]">
                    <span class="font-bold font-mono">{{ modalDetalle.comparacion[campo].actual }} cm</span>
                    <span
                      v-if="modalDetalle.comparacion[campo].diferencia !== null"
                      :class="[
                        modalDetalle.comparacion[campo].diferencia > 0 ? 'text-green-600 bg-green-50 dark:bg-green-950/20' : 
                        modalDetalle.comparacion[campo].diferencia < 0 ? 'text-red-600 bg-red-50 dark:bg-red-950/20' : 'text-gray-500 bg-gray-100 dark:bg-gray-800',
                        'text-xs font-semibold px-2 py-0.5 rounded-full'
                      ]"
                    >
                      {{ modalDetalle.comparacion[campo].diferencia > 0 ? '+' : '' }}{{ modalDetalle.comparacion[campo].diferencia }}
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Tips y Sugerencias de acuerdo a la evolución -->
            <div
              v-if="modalDetalle.comparacion.cintura && modalDetalle.comparacion.cintura.diferencia < 0"
              class="p-4 bg-green-50 dark:bg-green-950/20 border border-green-200 dark:border-green-900/30 rounded-xl"
            >
              <p class="text-xs text-green-800 dark:text-green-200">
                <strong>🎉 ¡Excelente!</strong> Tu cintura ha disminuido {{ Math.abs(modalDetalle.comparacion.cintura.diferencia) }} cm. 
                Esto indica una pérdida de tejido adiposo (grasa corporal). ¡Continúa así!
              </p>
            </div>

            <div
              v-if="modalDetalle.comparacion.brazos && modalDetalle.comparacion.brazos.diferencia > 0"
              class="p-4 bg-indigo-50 dark:bg-indigo-950/20 border border-indigo-200 dark:border-indigo-900/30 rounded-xl"
            >
              <p class="text-xs text-indigo-800 dark:text-indigo-200">
                <strong>💪 ¡Excelente progresión!</strong> Tus brazos han aumentado {{ modalDetalle.comparacion.brazos.diferencia }} cm.
                Esto sugiere una ganancia de hipertrofia y masa muscular. ¡Sigue entrenando duro!
              </p>
            </div>
          </div>

          <div class="mt-8 pt-4 border-t border-gray-100 dark:border-gray-700 flex justify-end">
            <button
              @click="cerrarModal"
              class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-650 text-gray-700 dark:text-gray-200 font-bold rounded-xl transition-colors text-sm"
            >
              Cerrar
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Notification Toast floating -->
    <div
      v-if="toast.show"
      class="fixed bottom-5 right-5 z-50 max-w-sm w-full bg-white dark:bg-gray-800 rounded-xl shadow-2xl border border-gray-200 dark:border-gray-700 pointer-events-auto overflow-hidden transition-all duration-300"
    >
      <div class="p-4 flex items-center gap-3">
        <div class="shrink-0">
          <svg v-if="toast.type === 'success'" class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <svg v-else class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <div class="flex-1">
          <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 mt-0.5">
            {{ toast.message }}
          </p>
        </div>
        <button @click="toast.show = false" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue';
import axios from 'axios';
import { Chart, registerables } from 'chart.js';
import confetti from 'canvas-confetti';

Chart.register(...registerables);

const activeTab = ref('medidas');
const progresos = ref([]);
const puedeRegistrar = ref(true);
const ultimoRegistro = ref(null);
const guardando = ref(false);
const metricaGrafica = ref('peso');

// Goals (Metas) data
const metas = ref([]);
const creandoMeta = ref(false);
const nuevaMeta = ref({
  tipo: 'entrenamiento_semanal',
  descripcion: '',
  valor_objetivo: ''
});

// Medals (Logros) data
const logros = ref([]);
const logrosStats = ref(null);

let chartInstance = null;

const form = ref({
  peso: '',
  altura: '',
  edad: '',
  sexo: '',
  cuello: '',
  hombros: '',
  pecho: '',
  brazos: '',
  cintura: '',
  cadera: '',
  muslos: '',
  pantorrillas: ''
});

const modalDetalle = ref({
  mostrar: false,
  progreso: {},
  comparacion: {}
});

const toast = ref({
  show: false,
  message: '',
  type: 'success'
});

const camposMedidas = ['cuello', 'hombros', 'pecho', 'brazos', 'cintura', 'cadera', 'muslos', 'pantorrillas'];
const labelCampos = {
  cuello: 'Cuello',
  hombros: 'Hombros',
  pecho: 'Pecho',
  brazos: 'Brazos',
  cintura: 'Cintura',
  cadera: 'Cadera',
  muslos: 'Muslos',
  pantorrillas: 'Pantorrillas'
};

const showNotification = (message, type = 'success') => {
  toast.value = { show: true, message, type };
  setTimeout(() => {
    toast.value.show = false;
  }, 4000);
};

// Confetti triggers
const triggerCelebration = () => {
  const duration = 2000;
  const end = Date.now() + duration;

  (function frame() {
    confetti({
      particleCount: 7,
      angle: 60,
      spread: 55,
      origin: { x: 0, y: 0.8 }
    });
    confetti({
      particleCount: 7,
      angle: 120,
      spread: 55,
      origin: { x: 1, y: 0.8 }
    });

    if (Date.now() < end) {
      requestAnimationFrame(frame);
    }
  })();
};

// Calular días restantes para habilitar registro
const diasRestantesParaRegistrar = computed(() => {
  if (!ultimoRegistro.value) return 0;
  const ultimo = new Date(ultimoRegistro.value.fecha);
  const hoy = new Date();
  const diasPasados = Math.floor((hoy - ultimo) / (1000 * 60 * 60 * 24));
  return Math.max(0, 14 - diasPasados);
});

// Fetch all progress data
const cargarProgresos = async () => {
  try {
    const response = await axios.get('/api/progreso');
    progresos.value = response.data.progresos || [];
    ultimoRegistro.value = response.data.ultimo || null;
    puedeRegistrar.value = response.data.puede_registrar;

    if (ultimoRegistro.value) {
      form.value.peso = ultimoRegistro.value.peso || '';
      form.value.altura = ultimoRegistro.value.altura || '';
      form.value.edad = ultimoRegistro.value.edad || '';
      form.value.sexo = ultimoRegistro.value.sexo || '';
      form.value.cuello = ultimoRegistro.value.cuello || '';
      form.value.hombros = ultimoRegistro.value.hombros || '';
      form.value.pecho = ultimoRegistro.value.pecho || '';
      form.value.brazos = ultimoRegistro.value.brazos || '';
      form.value.cintura = ultimoRegistro.value.cintura || '';
      form.value.cadera = ultimoRegistro.value.cadera || '';
      form.value.muslos = ultimoRegistro.value.muslos || '';
      form.value.pantorrillas = ultimoRegistro.value.pantorrillas || '';
    }

    nextTick(() => {
      initChart();
    });
  } catch (error) {
    console.error('Error al cargar progresos:', error);
  }
};

// Save progress measures
const guardarProgreso = async () => {
  const tieneDatos = Object.keys(form.value)
    .filter(k => !['sexo', 'edad', 'altura'].includes(k))
    .some(k => form.value[k] !== '' && form.value[k] !== null);

  if (!tieneDatos) {
    showNotification('Por favor, ingresa al menos una medida física.', 'error');
    return;
  }

  guardando.value = true;
  try {
    const response = await axios.post('/api/progreso', form.value);
    showNotification(response.data.message || 'Progreso guardado correctamente', 'success');

    // Check newly unlocked medals from response
    if (response.data.new_medals && response.data.new_medals.length > 0) {
      response.data.new_medals.forEach(medal => {
        showNotification(`🏆 ¡Felicidades! Desbloqueaste la medalla: ${medal.nombre}`, 'success');
      });
      triggerCelebration();
    }

    await cargarProgresos();
    await cargarLogros(); // Reload achievements stats
  } catch (error) {
    console.error('Error:', error);
    showNotification(error.response?.data?.message || 'Error al guardar el progreso corporal.', 'error');
  } finally {
    guardando.value = false;
  }
};

// Get detailed progress differences
const verDetalle = async (id) => {
  try {
    const response = await axios.get('/api/progreso/detalle', { params: { id } });
    modalDetalle.value = {
      mostrar: true,
      progreso: response.data.progreso,
      comparacion: response.data.comparacion
    };
  } catch (error) {
    console.error('Error al obtener detalle:', error);
    showNotification('No se pudo cargar el detalle del registro.', 'error');
  }
};

const cerrarModal = () => {
  modalDetalle.value.mostrar = false;
};

// Helpers de formateo
const formatFecha = (dateStr) => {
  if (!dateStr) return '';
  const date = new Date(dateStr + 'T00:00:00');
  return date.toLocaleDateString('es-ES', {
    day: '2-digit',
    month: 'long',
    year: 'numeric'
  });
};

const formatFechaMedalla = (dateStr) => {
  if (!dateStr) return '';
  const date = new Date(dateStr);
  return date.toLocaleDateString('es-ES', {
    day: '2-digit',
    month: 'short',
    year: 'numeric'
  });
};

// Goals (Metas) Logic
const cargarMetas = async () => {
  try {
    const response = await axios.get('/api/metas');
    metas.value = response.data || [];
  } catch (error) {
    console.error('Error al cargar metas:', error);
  }
};

const crearMeta = async () => {
  creandoMeta.value = true;
  try {
    const response = await axios.post('/api/metas', nuevaMeta.value);
    showNotification(response.data.message || 'Meta creada con éxito.', 'success');
    nuevaMeta.value = {
      tipo: 'entrenamiento_semanal',
      descripcion: '',
      valor_objetivo: ''
    };
    await cargarMetas();
    await cargarLogros();
  } catch (error) {
    console.error('Error al crear meta:', error);
    showNotification(error.response?.data?.message || 'Error al crear la meta.', 'error');
  } finally {
    creandoMeta.value = false;
  }
};

const toggleMetaCompletada = async (meta) => {
  try {
    const response = await axios.post(`/api/metas/${meta.id}/completar`);
    showNotification(response.data.message || 'Meta actualizada.', 'success');

    // Confetti and notification if newly completed
    if (response.data.new_medals && response.data.new_medals.length > 0) {
      response.data.new_medals.forEach(medal => {
        showNotification(`🏆 ¡Felicidades! Desbloqueaste la medalla: ${medal.nombre}`, 'success');
      });
      triggerCelebration();
    } else if (response.data.meta.completada) {
      triggerCelebration();
    }

    await cargarMetas();
    await cargarLogros();
  } catch (error) {
    console.error('Error al actualizar meta:', error);
    showNotification('Error al actualizar la meta.', 'error');
  }
};

const eliminarMeta = async (id) => {
  if (!confirm('¿Estás seguro de que deseas eliminar esta meta?')) return;
  try {
    await axios.delete(`/api/metas/${id}`);
    showNotification('Meta eliminada correctamente.', 'success');
    await cargarMetas();
    await cargarLogros();
  } catch (error) {
    console.error('Error al eliminar la meta:', error);
    showNotification('Error al eliminar la meta.', 'error');
  }
};

const getMetaEmoji = (tipo) => {
  const emojis = {
    entrenamiento_semanal: '🏋️‍♂️',
    peso_corporal: '⚖️',
    cintura_corporal: '📏',
    brazos_corporal: '💪',
    pecho_corporal: '👕',
    otro: '🎯'
  };
  return emojis[tipo] || '🎯';
};

const formatMetaTipo = (tipo) => {
  const labels = {
    entrenamiento_semanal: 'Entrenamientos/Semana',
    peso_corporal: 'Peso Corporal (kg)',
    cintura_corporal: 'Medida Cintura (cm)',
    brazos_corporal: 'Medida Brazos (cm)',
    pecho_corporal: 'Medida Pecho (cm)',
    otro: 'Otro Objetivo'
  };
  return labels[tipo] || tipo;
};

// Achievements (Logros) Logic
const cargarLogros = async () => {
  try {
    const response = await axios.get('/api/logros');
    logros.value = response.data.logros || [];
    logrosStats.value = response.data.stats || null;
  } catch (error) {
    console.error('Error al cargar logros:', error);
  }
};

// Chart.js initialization
const initChart = () => {
  const ctx = document.getElementById('progresoChart');
  if (!ctx) return;

  if (chartInstance) {
    chartInstance.destroy();
  }

  const key = metricaGrafica.value;
  const validData = progresos.value
    .filter(p => p[key] !== null && p[key] !== undefined && Number(p[key]) > 0)
    .map(p => ({
      fecha: p.fecha,
      valor: parseFloat(p[key])
    }));

  if (validData.length === 0) {
    return;
  }

  // Sorting ascending by date for chart line
  validData.sort((a, b) => new Date(a.fecha) - new Date(b.fecha));

  const labels = validData.map(d => {
    const date = new Date(d.fecha + 'T00:00:00');
    return date.toLocaleDateString('es-ES', { day: '2-digit', month: 'short' });
  });
  const dataValues = validData.map(d => d.valor);

  const gridColor = document.documentElement.classList.contains('dark') ? '#374151' : '#e2e8f0';
  const textColor = document.documentElement.classList.contains('dark') ? '#9ca3af' : '#4b5563';

  // Gradient fill
  const canvasCtx = ctx.getContext('2d');
  const gradient = canvasCtx.createLinearGradient(0, 0, 0, 300);
  gradient.addColorStop(0, 'rgba(99, 102, 241, 0.3)');
  gradient.addColorStop(1, 'rgba(99, 102, 241, 0.0)');

  chartInstance = new Chart(ctx, {
    type: 'line',
    data: {
      labels: labels,
      datasets: [{
        label: formatMetaTipo(key + '_corporal') || key,
        data: dataValues,
        borderColor: '#6366f1',
        borderWidth: 3,
        backgroundColor: gradient,
        fill: true,
        tension: 0.35,
        pointBackgroundColor: '#6366f1',
        pointBorderColor: '#ffffff',
        pointBorderWidth: 2,
        pointRadius: 6,
        pointHoverRadius: 8,
        pointHoverBackgroundColor: '#4f46e5',
        pointHoverBorderColor: '#ffffff',
        pointHoverBorderWidth: 3
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
          titleFont: { size: 12, weight: 'bold' },
          bodyFont: { size: 12 },
          padding: 12,
          cornerRadius: 10,
          displayColors: false,
          callbacks: {
            label: function(context) {
              return ` ${context.parsed.y} ${key === 'peso' ? 'kg' : 'cm'}`;
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
            font: { family: 'ui-sans-serif, system-ui' }
          }
        },
        x: {
          grid: {
            display: false
          },
          ticks: {
            color: textColor,
            font: { family: 'ui-sans-serif, system-ui' }
          }
        }
      }
    }
  });
};

watch(metricaGrafica, () => {
  nextTick(() => {
    initChart();
  });
});

watch(activeTab, (newTab) => {
  if (newTab === 'medidas') {
    nextTick(() => {
      initChart();
    });
  }
});

onMounted(() => {
  cargarProgresos();
  cargarMetas();
  cargarLogros();
});
</script>

<style scoped>
/* Scrollbar removal helper */
.scrollbar-hide::-webkit-scrollbar {
  display: none;
}
.scrollbar-hide {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(8px); }
  to { opacity: 1; transform: translateY(0); }
}
.animate-fadeIn {
  animation: fadeIn 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
</style>
