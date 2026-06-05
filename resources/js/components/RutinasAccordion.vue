<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8">
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

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Vista para Alumnos: Solo mostrar su rutina asignada -->
      <div v-if="isAlumno && userRutina" class="mb-8">
        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-2xl shadow-2xl p-6 md:p-8 mb-6">
          <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
              <p class="text-xs md:text-sm uppercase tracking-[0.2em] text-indigo-200 mb-2">Tu rutina asignada</p>
              <h2 class="text-2xl md:text-3xl font-bold text-white">
                {{ userRutina.nivel }} {{ userRutina.modalidad }}
              </h2>
              <p class="mt-2 text-indigo-200">Día actual: {{ userRutina.dia_actual }}</p>
            </div>
            <a
              href="/dashboard"
              class="inline-flex items-center justify-center bg-white text-indigo-600 px-6 py-3 rounded-xl font-semibold hover:bg-indigo-50 transition-colors shadow-lg"
            >
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              Comenzar Entrenamiento
            </a>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
          <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Información de tu rutina</h3>
          <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
              <p class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ userRutina.nivel }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">Nivel</p>
            </div>
            <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
              <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ userRutina.modalidad }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">Frecuencia</p>
            </div>
            <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
              <p class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ userRutina.dia_actual }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">Progreso</p>
            </div>
            <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
              <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">Personal</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">Asignada por</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Vista para Alumnos sin rutina -->
      <div v-else-if="isAlumno && !userRutina" class="text-center py-16">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8 max-w-md mx-auto border border-gray-200 dark:border-gray-700">
          <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-2">Rutina Pendiente</h3>
          <p class="text-gray-500 dark:text-gray-400 mb-6">Tu trainer aún no te ha asignado una rutina de entrenamiento.</p>
          <p class="text-sm text-gray-400 dark:text-gray-500">Contacta a tu trainer para que configure tu plan de entrenamiento.</p>
        </div>
      </div>

      <!-- Vista para usuarios NO alumnos: Catálogo completo -->
      <template v-else>
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
          <div>
            <h2 class="text-3xl font-extrabold text-gray-900 dark:text-white">Explorar Rutinas</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Selecciona, comparte o importa planes de entrenamiento</p>
          </div>
          <a
            href="/rutinas/crear"
            class="bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white px-5 py-3 rounded-xl font-semibold transition-all shadow-md hover:shadow-lg flex items-center gap-2"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            Crear Nueva Rutina
          </a>
        </div>

        <!-- Navigation Tabs -->
        <div class="flex border-b border-gray-200 dark:border-gray-700 mb-8 gap-6 overflow-x-auto scrollbar-hide">
          <button
            @click="catalogoTab = 'predeterminadas'"
            :class="[
              catalogoTab === 'predeterminadas'
                ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 dark:border-indigo-400'
                : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300',
              'pb-4 text-sm font-semibold border-b-2 transition-all flex items-center gap-2 whitespace-nowrap'
            ]"
          >
            <span>📋</span> Rutinas Oficiales
          </button>
          <button
            @click="catalogoTab = 'personalizadas'"
            :class="[
              catalogoTab === 'personalizadas'
                ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 dark:border-indigo-400'
                : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300',
              'pb-4 text-sm font-semibold border-b-2 transition-all flex items-center gap-2 whitespace-nowrap'
            ]"
          >
            <span>👤</span> Mis Rutinas Personalizadas
          </button>
          <button
            @click="catalogoTab = 'comunitarias'"
            :class="[
              catalogoTab === 'comunitarias'
                ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 dark:border-indigo-400'
                : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300',
              'pb-4 text-sm font-semibold border-b-2 transition-all flex items-center gap-2 whitespace-nowrap'
            ]"
          >
            <span>🌎</span> Catálogo Comunitario
          </button>
        </div>

        <!-- TAB: Rutinas Predeterminadas (Oficiales) -->
        <div v-show="catalogoTab === 'predeterminadas'" class="space-y-6">
          <div v-for="(nivelData, nivelNombre) in defaultRutinas" :key="nivelNombre" class="mb-10">
            <div class="flex items-center mb-4">
              <div :class="getNivelColor(nivelNombre)" class="w-3 h-8 rounded-full mr-3"></div>
              <h3 class="text-2xl font-extrabold text-gray-800 dark:text-white">{{ nivelNombre }}</h3>
            </div>

            <div v-for="modalidad in nivelData.modalidades" :key="modalidad.nombre" class="mb-6">
              <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden border border-gray-200 dark:border-gray-700">
                <button
                  @click="toggleAcordeon(nivelNombre, modalidad.nombre)"
                  class="w-full px-6 py-5 text-left flex justify-between items-center bg-gradient-to-r from-gray-50 to-white hover:from-gray-100 hover:to-gray-50 dark:from-gray-800 dark:to-gray-700 dark:hover:from-gray-700/60 dark:hover:to-gray-600/60 transition-all duration-200"
                >
                  <div class="flex items-center">
                    <span class="text-lg font-bold text-gray-800 dark:text-white">{{ modalidad.nombre }}</span>
                    <span class="ml-3 px-3 py-1 text-sm font-medium bg-indigo-100 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-300 rounded-full">
                      {{ modalidad.dias.length }} días
                    </span>
                  </div>
                  <svg :class="{'rotate-180': isAcordeonOpen(nivelNombre, modalidad.nombre)}" class="w-6 h-6 text-gray-600 dark:text-gray-400 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                  </svg>
                </button>

                <div v-if="isAcordeonOpen(nivelNombre, modalidad.nombre)" class="border-t border-gray-200 dark:border-gray-700">
                  <div v-for="dia in modalidad.dias" :key="dia.nombre" class="border-b last:border-b-0 border-gray-100 dark:border-gray-700">
                    <button
                      @click="toggleDia(nivelNombre, modalidad.nombre, dia.nombre)"
                      class="w-full px-6 py-4 text-left flex justify-between items-center bg-blue-50/50 hover:bg-blue-100/50 dark:bg-gray-900/40 dark:hover:bg-gray-900/60 transition-colors"
                    >
                      <div class="flex items-center">
                        <svg class="w-5 h-5 text-indigo-500 dark:text-indigo-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                        <span class="font-semibold text-indigo-950 dark:text-indigo-300">{{ dia.nombre }}</span>
                        <span class="ml-2 text-xs text-indigo-500 dark:text-indigo-400">({{ dia.ejercicios.length }} ejercicios)</span>
                      </div>
                      <svg :class="{'rotate-180': isDiaOpen(nivelNombre, modalidad.nombre, dia.nombre)}" class="w-5 h-5 text-indigo-500 dark:text-indigo-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                      </svg>
                    </button>

                    <div v-if="isDiaOpen(nivelNombre, modalidad.nombre, dia.nombre)" class="p-5 bg-gray-50/30 dark:bg-gray-900/30">
                      <table class="w-full text-sm">
                        <thead>
                          <tr class="bg-white dark:bg-gray-800 border-b-2 border-gray-200 dark:border-gray-700">
                            <th class="px-4 py-3 text-left font-bold text-gray-700 dark:text-gray-300">Ejercicio</th>
                            <th class="px-4 py-3 text-center font-bold text-gray-700 dark:text-gray-300">Series</th>
                            <th class="px-4 py-3 text-center font-bold text-gray-700 dark:text-gray-300">Reps</th>
                            <th class="px-4 py-3 text-center font-bold text-gray-700 dark:text-gray-300">Descanso</th>
                          </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                          <tr v-for="ejercicio in dia.ejercicios" :key="ejercicio.id" class="bg-white hover:bg-gray-50 dark:bg-gray-800 dark:hover:bg-gray-700 transition-all" :class="getSuperserieClass(ejercicio)">
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                              {{ ejercicio.ejercicio_nombre }}
                              <span v-if="ejercicio.superserie_grupo" class="ml-2 inline-flex items-center rounded-md bg-indigo-50 px-2 py-0.5 text-xs font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-700/10 dark:bg-indigo-900/40 dark:text-indigo-400">
                                Superserie {{ ejercicio.superserie_grupo }}
                              </span>
                            </td>
                            <td class="px-4 py-3 text-center text-gray-700 dark:text-gray-300">
                              <span class="px-2 py-1 bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300 rounded font-semibold">{{ ejercicio.series }}</span>
                            </td>
                            <td class="px-4 py-3 text-center text-gray-700 dark:text-gray-300">{{ ejercicio.reps_min }} - {{ ejercicio.reps_max }}</td>
                            <td class="px-4 py-3 text-center text-gray-700 dark:text-gray-300">
                              <span class="text-orange-600 dark:text-orange-400 font-medium">{{ ejercicio.descanso_min }} min</span>
                            </td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>

                  <div class="p-5 bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-950/20 dark:to-purple-950/20 border-t border-gray-200 dark:border-gray-700">
                    <button
                      @click="seleccionarRutina(nivelNombre, modalidad.nombre)"
                      class="w-full bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white px-6 py-4 rounded-xl font-bold transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5"
                    >
                      Seleccionar {{ nivelNombre }} - {{ modalidad.nombre }}
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- TAB: Rutinas Personalizadas (Mis Rutinas) -->
        <div v-show="catalogoTab === 'personalizadas'" class="space-y-6">
          <div v-if="!personalRutinas || Object.keys(personalRutinas.modalidades).length === 0" class="text-center py-16 bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-8 shadow-sm">
            <span class="text-4xl block mb-2">👤</span>
            <p class="font-bold text-gray-700 dark:text-gray-300">No tienes rutinas personalizadas aún.</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 mb-4">Crea tus propias rutinas o importa planes desde el catálogo comunitario.</p>
            <a
              href="/rutinas/crear"
              class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-semibold text-sm shadow transition-all"
            >
              Crear mi primera rutina
            </a>
          </div>

          <div v-else>
            <div v-for="modalidad in personalRutinas.modalidades" :key="modalidad.nombre" class="mb-6">
              <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden border border-gray-200 dark:border-gray-700">
                <button
                  @click="toggleAcordeon('Personalizada', modalidad.nombre)"
                  class="w-full px-6 py-5 text-left flex justify-between items-center bg-gradient-to-r from-gray-50 to-white hover:from-gray-100 hover:to-gray-50 dark:from-gray-800 dark:to-gray-700 dark:hover:from-gray-700/60 dark:hover:to-gray-600/60 transition-all duration-200"
                >
                  <div class="flex items-center">
                    <span class="text-lg font-bold text-indigo-600 dark:text-indigo-400">{{ modalidad.nombre }}</span>
                    <span class="ml-3 px-3 py-1 text-sm font-medium bg-indigo-100 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-300 rounded-full">
                      {{ modalidad.dias.length }} días
                    </span>
                    <!-- Badge isShared -->
                    <span v-if="isRoutineShared(modalidad)" class="ml-2 px-2.5 py-0.5 text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300 rounded-full flex items-center gap-1 shadow-sm">
                      <span>🌎</span> Compartida
                    </span>
                  </div>
                  <svg :class="{'rotate-180': isAcordeonOpen('Personalizada', modalidad.nombre)}" class="w-6 h-6 text-gray-600 dark:text-gray-400 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                  </svg>
                </button>

                <div v-if="isAcordeonOpen('Personalizada', modalidad.nombre)" class="border-t border-gray-200 dark:border-gray-700">
                  <div v-for="dia in modalidad.dias" :key="dia.nombre" class="border-b last:border-b-0 border-gray-100 dark:border-gray-700">
                    <button
                      @click="toggleDia('Personalizada', modalidad.nombre, dia.nombre)"
                      class="w-full px-6 py-4 text-left flex justify-between items-center bg-blue-50/50 hover:bg-blue-100/50 dark:bg-gray-900/40 dark:hover:bg-gray-900/60 transition-colors"
                    >
                      <div class="flex items-center">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                        <span class="font-semibold text-blue-900 dark:text-blue-300">{{ dia.nombre }}</span>
                        <span class="ml-2 text-xs text-blue-600 dark:text-blue-400">({{ dia.ejercicios.length }} ejercicios)</span>
                      </div>
                      <svg :class="{'rotate-180': isDiaOpen('Personalizada', modalidad.nombre, dia.nombre)}" class="w-5 h-5 text-blue-600 dark:text-blue-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                      </svg>
                    </button>

                    <div v-if="isDiaOpen('Personalizada', modalidad.nombre, dia.nombre)" class="p-5 bg-gray-50/30 dark:bg-gray-900/30">
                      <table class="w-full text-sm">
                        <thead>
                          <tr class="bg-white dark:bg-gray-800 border-b-2 border-gray-200 dark:border-gray-700">
                            <th class="px-4 py-3 text-left font-bold text-gray-700 dark:text-gray-300">Ejercicio</th>
                            <th class="px-4 py-3 text-center font-bold text-gray-700 dark:text-gray-300">Series</th>
                            <th class="px-4 py-3 text-center font-bold text-gray-700 dark:text-gray-300">Reps</th>
                            <th class="px-4 py-3 text-center font-bold text-gray-700 dark:text-gray-300">Descanso</th>
                          </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                          <tr v-for="ejercicio in dia.ejercicios" :key="ejercicio.id" class="bg-white hover:bg-gray-50 dark:bg-gray-800 dark:hover:bg-gray-700 transition-all" :class="getSuperserieClass(ejercicio)">
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                              {{ ejercicio.ejercicio_nombre }}
                              <span v-if="ejercicio.superserie_grupo" class="ml-2 inline-flex items-center rounded-md bg-indigo-50 px-2 py-0.5 text-xs font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-700/10 dark:bg-indigo-900/40 dark:text-indigo-400">
                                Superserie {{ ejercicio.superserie_grupo }}
                              </span>
                            </td>
                            <td class="px-4 py-3 text-center text-gray-700 dark:text-gray-300">
                              <span class="px-2 py-1 bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300 rounded font-semibold">{{ ejercicio.series }}</span>
                            </td>
                            <td class="px-4 py-3 text-center text-gray-700 dark:text-gray-300">{{ ejercicio.reps_min }} - {{ ejercicio.reps_max }}</td>
                            <td class="px-4 py-3 text-center text-gray-700 dark:text-gray-300">
                              <span class="text-orange-600 dark:text-orange-400 font-medium">{{ ejercicio.descanso_min }} min</span>
                            </td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>

                  <div class="p-5 bg-gradient-to-r from-gray-50 to-white dark:from-gray-900/20 dark:to-gray-800/20 border-t border-gray-200 dark:border-gray-700 flex flex-col sm:flex-row gap-3">
                    <button
                      @click="seleccionarRutina('Personalizada', modalidad.nombre)"
                      class="flex-1 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white px-6 py-3.5 rounded-xl font-bold transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5"
                    >
                      Seleccionar {{ modalidad.nombre }}
                    </button>
                    <!-- Compartir Button -->
                    <button
                      v-if="!isRoutineShared(modalidad)"
                      @click="compartirRutina('Personalizada', modalidad.nombre)"
                      class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-3.5 rounded-xl font-bold transition-all duration-200 shadow-md hover:shadow-lg flex items-center justify-center gap-1.5"
                    >
                      <span>🌎</span> Compartir
                    </button>
                    <!-- Eliminar Button -->
                    <button
                      @click="eliminarRutina('Personalizada', modalidad.nombre)"
                      class="bg-red-600 hover:bg-red-700 text-white px-5 py-3.5 rounded-xl font-bold transition-all duration-200 shadow-md hover:shadow-lg flex items-center justify-center gap-1.5"
                    >
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                      Eliminar
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- TAB: Catálogo Comunitario -->
        <div v-show="catalogoTab === 'comunitarias'" class="space-y-6">
          <div v-if="Object.keys(communityRutinas).length === 0" class="text-center py-16 bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-8 shadow-sm">
            <span class="text-4xl block mb-2">🌎</span>
            <p class="font-bold text-gray-700 dark:text-gray-300">No hay rutinas compartidas en la comunidad aún.</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">¡Sé el primero en compartir una rutina personalizada con el resto de los usuarios!</p>
          </div>

          <div v-else>
            <div v-for="modalidad in communityRutinas" :key="`${modalidad.nombre}-${modalidad.created_by}`" class="mb-6">
              <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden border border-gray-200 dark:border-gray-700">
                <button
                  @click="toggleAcordeon('Comunitaria', `${modalidad.nombre}-${modalidad.created_by}`)"
                  class="w-full px-6 py-5 text-left flex justify-between items-center bg-gradient-to-r from-gray-50 to-white hover:from-gray-100 hover:to-gray-50 dark:from-gray-800 dark:to-gray-700 dark:hover:from-gray-700/60 dark:hover:to-gray-600/60 transition-all duration-200"
                >
                  <div class="flex items-center">
                    <span class="text-lg font-bold text-gray-800 dark:text-white">{{ modalidad.nombre }}</span>
                    <span class="ml-3 px-3 py-1 text-sm font-medium bg-indigo-100 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-300 rounded-full">
                      {{ modalidad.dias.length }} días
                    </span>
                    <span class="ml-3 text-xs font-semibold text-gray-500 dark:text-gray-400 italic">
                      Creado por: @{{ nicknameCreator(modalidad) }}
                    </span>
                  </div>
                  <svg :class="{'rotate-180': isAcordeonOpen('Comunitaria', `${modalidad.nombre}-${modalidad.created_by}`)}" class="w-6 h-6 text-gray-600 dark:text-gray-400 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                  </svg>
                </button>

                <div v-if="isAcordeonOpen('Comunitaria', `${modalidad.nombre}-${modalidad.created_by}`)" class="border-t border-gray-200 dark:border-gray-700">
                  <div v-for="dia in modalidad.dias" :key="dia.nombre" class="border-b last:border-b-0 border-gray-100 dark:border-gray-700">
                    <button
                      @click="toggleDia('Comunitaria', `${modalidad.nombre}-${modalidad.created_by}`, dia.nombre)"
                      class="w-full px-6 py-4 text-left flex justify-between items-center bg-blue-50/50 hover:bg-blue-100/50 dark:bg-gray-900/40 dark:hover:bg-gray-900/60 transition-colors"
                    >
                      <div class="flex items-center">
                        <svg class="w-5 h-5 text-indigo-500 dark:text-indigo-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                        <span class="font-semibold text-indigo-950 dark:text-indigo-300">{{ dia.nombre }}</span>
                        <span class="ml-2 text-xs text-indigo-500 dark:text-indigo-400">({{ dia.ejercicios.length }} ejercicios)</span>
                      </div>
                      <svg :class="{'rotate-180': isDiaOpen('Comunitaria', `${modalidad.nombre}-${modalidad.created_by}`, dia.nombre)}" class="w-5 h-5 text-indigo-500 dark:text-indigo-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                      </svg>
                    </button>

                    <div v-if="isDiaOpen('Comunitaria', `${modalidad.nombre}-${modalidad.created_by}`, dia.nombre)" class="p-5 bg-gray-50/30 dark:bg-gray-900/30">
                      <table class="w-full text-sm">
                        <thead>
                          <tr class="bg-white dark:bg-gray-800 border-b-2 border-gray-200 dark:border-gray-700">
                            <th class="px-4 py-3 text-left font-bold text-gray-700 dark:text-gray-300">Ejercicio</th>
                            <th class="px-4 py-3 text-center font-bold text-gray-700 dark:text-gray-300">Series</th>
                            <th class="px-4 py-3 text-center font-bold text-gray-700 dark:text-gray-300">Reps</th>
                            <th class="px-4 py-3 text-center font-bold text-gray-700 dark:text-gray-300">Descanso</th>
                          </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                          <tr v-for="ejercicio in dia.ejercicios" :key="ejercicio.id" class="bg-white hover:bg-gray-50 dark:bg-gray-800 dark:hover:bg-gray-700 transition-all" :class="getSuperserieClass(ejercicio)">
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                              {{ ejercicio.ejercicio_nombre }}
                              <span v-if="ejercicio.superserie_grupo" class="ml-2 inline-flex items-center rounded-md bg-indigo-50 px-2 py-0.5 text-xs font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-700/10 dark:bg-indigo-900/40 dark:text-indigo-400">
                                Superserie {{ ejercicio.superserie_grupo }}
                              </span>
                            </td>
                            <td class="px-4 py-3 text-center text-gray-700 dark:text-gray-300">
                              <span class="px-2 py-1 bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300 rounded font-semibold">{{ ejercicio.series }}</span>
                            </td>
                            <td class="px-4 py-3 text-center text-gray-700 dark:text-gray-300">{{ ejercicio.reps_min }} - {{ ejercicio.reps_max }}</td>
                            <td class="px-4 py-3 text-center text-gray-700 dark:text-gray-300">
                              <span class="text-orange-600 dark:text-orange-400 font-medium">{{ ejercicio.descanso_min }} min</span>
                            </td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>

                  <div class="p-5 bg-gradient-to-r from-emerald-50 to-green-50 dark:from-emerald-950/20 dark:to-green-950/20 border-t border-gray-200 dark:border-gray-700">
                    <button
                      @click="importarRutina(modalidad)"
                      class="w-full bg-gradient-to-r from-emerald-600 to-green-600 hover:from-emerald-700 hover:to-green-700 text-white px-6 py-4 rounded-xl font-bold transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5 flex items-center justify-center gap-1.5"
                    >
                      <span>📥</span> Importar a Mis Rutinas
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </template>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRutinaStore } from '../stores/rutina';
import axios from 'axios';
import confetti from 'canvas-confetti';

const getSuperserieClass = (ejercicio) => {
  const grupo = ejercicio.superserie_grupo;
  if (!grupo) return '';
  switch (grupo) {
    case 1: return 'border-l-4 border-indigo-500 dark:border-indigo-400 bg-indigo-50/10 dark:bg-indigo-950/20';
    case 2: return 'border-l-4 border-emerald-500 dark:border-emerald-400 bg-emerald-50/10 dark:bg-emerald-950/20';
    case 3: return 'border-l-4 border-pink-500 dark:border-pink-400 bg-pink-50/10 dark:bg-pink-950/20';
    case 4: return 'border-l-4 border-amber-500 dark:border-amber-400 bg-amber-50/10 dark:bg-amber-950/20';
    default: return 'border-l-4 border-gray-500 dark:border-gray-400 bg-gray-50/10 dark:bg-gray-950/20';
  }
};

const rutinaStore = useRutinaStore();

const catalogoTab = ref('predeterminadas');
const rutinasAgrupadas = ref({});
const comunitariasList = ref([]);
const openItems = ref({});
const isSelecting = ref(false);
const userRutina = ref(null);
const userRole = ref(null);
const isAlumno = computed(() => userRole.value === 'alumno');

const toast = ref({
  show: false,
  message: '',
  type: 'success'
});

const showNotification = (message, type = 'success') => {
  toast.value = { show: true, message, type };
  setTimeout(() => {
    toast.value.show = false;
  }, 4000);
};

const triggerSuccessConfetti = () => {
  confetti({
    particleCount: 80,
    spread: 70,
    origin: { y: 0.6 }
  });
};

const defaultRutinas = computed(() => {
  const result = {};
  Object.keys(rutinasAgrupadas.value).forEach(nivel => {
    if (nivel !== 'Personalizada') {
      result[nivel] = rutinasAgrupadas.value[nivel];
    }
  });
  return result;
});

const personalRutinas = computed(() => {
  return rutinasAgrupadas.value['Personalizada'] || null;
});

// Group community routines
const communityRutinas = computed(() => {
  const agrupadas = {};

  comunitariasList.value.forEach(r => {
    const key = `${r.modalidad}-${r.created_by}`;
    if (!agrupadas[key]) {
      agrupadas[key] = {
        nombre: r.modalidad,
        created_by: r.created_by,
        creador_obj: r.creador,
        dias: {}
      };
    }
    if (!agrupadas[key].dias[r.dia]) {
      agrupadas[key].dias[r.dia] = { nombre: r.dia, ejercicios: [] };
    }
    agrupadas[key].dias[r.dia].ejercicios.push(r);
  });

  // Convert dias to sorted arrays
  Object.keys(agrupadas).forEach(k => {
    agrupadas[k].dias = Object.values(agrupadas[k].dias).sort((a, b) => a.nombre.localeCompare(b.nombre));
  });

  return agrupadas;
});

const nicknameCreator = (modalidad) => {
  return modalidad.creador_obj?.nick || modalidad.creador_obj?.name || `user-${modalidad.created_by}`;
};

const isRoutineShared = (modalidad) => {
  // If any exercise in this custom routine has publica = true
  return modalidad.dias.some(d => d.ejercicios.some(e => e.publica));
};

const fetchUserInfo = async () => {
  try {
    const response = await axios.get('/api/user-info');
    userRole.value = response.data.role;
    if (userRole.value === 'alumno') {
      const rutinaResponse = await axios.get('/api/user-rutina');
      userRutina.value = rutinaResponse.data || null;
    }
  } catch (error) {
    userRole.value = null;
    userRutina.value = null;
  }
};

const getNivelColor = (nivel) => {
  const colors = {
    'Principiante': 'bg-green-500',
    'Intermedio': 'bg-yellow-500',
    'Avanzado': 'bg-red-500',
  };
  return colors[nivel] || 'bg-gray-500';
};

const toggleAcordeon = (nivel, modalidad) => {
  const key = `acordeon-${nivel}-${modalidad}`;
  openItems.value[key] = !openItems.value[key];
};

const isAcordeonOpen = (nivel, modalidad) => {
  return openItems.value[`acordeon-${nivel}-${modalidad}`] || false;
};

const toggleDia = (nivel, modalidad, dia) => {
  const key = `dia-${nivel}-${modalidad}-${dia}`;
  openItems.value[key] = !openItems.value[key];
};

const isDiaOpen = (nivel, modalidad, dia) => {
  return openItems.value[`dia-${nivel}-${modalidad}-${dia}`] || false;
};

const fetchRutinas = async () => {
  try {
    const response = await axios.get('/api/rutinas');
    const rutinas = response.data;

    const agrupadas = {};

    rutinas.forEach(r => {
      if (!agrupadas[r.nivel]) {
        agrupadas[r.nivel] = { modalidades: {} };
      }
      if (!agrupadas[r.nivel].modalidades[r.modalidad]) {
        agrupadas[r.nivel].modalidades[r.modalidad] = { nombre: r.modalidad, dias: {} };
      }
      if (!agrupadas[r.nivel].modalidades[r.modalidad].dias[r.dia]) {
        agrupadas[r.nivel].modalidades[r.modalidad].dias[r.dia] = { nombre: r.dia, ejercicios: [] };
      }
      agrupadas[r.nivel].modalidades[r.modalidad].dias[r.dia].ejercicios.push(r);
    });

    Object.keys(agrupadas).forEach(nivel => {
      Object.keys(agrupadas[nivel].modalidades).forEach(mod => {
        agrupadas[nivel].modalidades[mod] = {
          nombre: mod,
          dias: Object.values(agrupadas[nivel].modalidades[mod].dias).sort((a, b) => a.nombre.localeCompare(b.nombre))
        };
      });
    });

    rutinasAgrupadas.value = agrupadas;
  } catch (error) {
    console.error('Error:', error);
  }
};

const fetchComunitarias = async () => {
  try {
    const response = await axios.get('/api/rutinas', { params: { comunitarias: true } });
    comunitariasList.value = response.data || [];
  } catch (error) {
    console.error('Error al cargar rutinas comunitarias:', error);
  }
};

const seleccionarRutina = async (nivel, modalidad) => {
  if (isSelecting.value) return;
  isSelecting.value = true;

  try {
    await axios.post('/api/user-rutina', {
      nivel,
      modalidad,
      dia_actual: 'Día 1',
    });

    rutinaStore.seleccionar(`${nivel} ${modalidad}`, 'Todos los días');
    window.location.href = '/dashboard';
  } catch (error) {
    console.error('Error:', error);
    showNotification('No se pudo guardar la rutina. Intenta de nuevo.', 'error');
  } finally {
    isSelecting.value = false;
  }
};

const compartirRutina = async (nivel, modalidad) => {
  if (!confirm(`¿Deseas compartir la rutina "${modalidad}" con la comunidad? Otros usuarios podrán verla e importarla.`)) return;

  try {
    const response = await axios.post('/api/rutinas/compartir', { nivel, modalidad });
    showNotification(response.data.message || 'Rutina compartida con éxito.', 'success');

    // Confetti if new achievements unlocked
    if (response.data.new_medals && response.data.new_medals.length > 0) {
      response.data.new_medals.forEach(medal => {
        showNotification(`🏆 ¡Felicidades! Desbloqueaste la medalla: ${medal.nombre}`, 'success');
      });
      triggerSuccessConfetti();
    } else {
      triggerSuccessConfetti();
    }

    await fetchRutinas();
    await fetchComunitarias();
  } catch (error) {
    console.error('Error al compartir rutina:', error);
    showNotification('Error al compartir la rutina.', 'error');
  }
};

const importarRutina = async (modalidadObj) => {
  try {
    const response = await axios.post('/api/rutinas/importar', {
      nivel: 'Personalizada',
      modalidad: modalidadObj.nombre,
      created_by: modalidadObj.created_by
    });

    showNotification(response.data.message || 'Rutina importada con éxito.', 'success');
    triggerSuccessConfetti();

    await fetchRutinas();
    // Switch to personalized tab
    catalogoTab.value = 'personalizadas';
  } catch (error) {
    console.error('Error al importar rutina:', error);
    showNotification('Error al importar la rutina.', 'error');
  }
};

const eliminarRutina = async (nivel, modalidad) => {
  const confirmed = confirm(
    `¿Estás seguro de que deseas eliminar la rutina "${modalidad}"? Se mantendrá el historial de entrenamiento de los alumnos, pero no podrán volver a seleccionarla.`
  );
  if (!confirmed) return;

  try {
    await axios.delete('/api/rutinas', {
      data: { nivel, modalidad }
    });
    showNotification('Rutina eliminada correctamente.', 'success');
    
    if (userRutina.value && userRutina.value.nivel === nivel && userRutina.value.modalidad === modalidad) {
      userRutina.value = null;
      rutinaStore.limpiar();
    }
    
    await fetchRutinas();
    await fetchComunitarias();
  } catch (error) {
    console.error('Error al eliminar la rutina:', error);
    showNotification(error.response?.data?.message || 'No se pudo eliminar la rutina.', 'error');
  }
};

onMounted(() => {
  fetchUserInfo();
  fetchRutinas();
  fetchComunitarias();
});
</script>

<style scoped>
.scrollbar-hide::-webkit-scrollbar {
  display: none;
}
.scrollbar-hide {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>