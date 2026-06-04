<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8">
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
          <p class="text-sm text-gray-400 dark:text-gray-500">Contacta a tu trainer para queconfigure tu plan de entrenamiento.</p>
        </div>
      </div>

      <!-- Vista para usuarios NO alumnos: Catálogo completo -->
      <template v-else>
        <div class="flex justify-between items-center mb-8">
          <h2 class="text-3xl font-bold text-gray-900 dark:text-white">Rutinas de Entrenamiento</h2>
          <a
            href="/rutinas/crear"
            class="bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white px-5 py-3 rounded-lg font-semibold transition-all shadow-md hover:shadow-lg flex items-center gap-2"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            Crear Nueva Rutina
          </a>
        </div>

        <!-- Rutinas Personalizadas -->
        <div v-if="personalRutinas && Object.keys(personalRutinas.modalidades).length > 0" class="mb-10">
          <div class="flex items-center mb-6">
            <div class="w-3 h-8 rounded-full mr-3 bg-gradient-to-b from-indigo-500 to-purple-500 animate-pulse"></div>
            <h3 class="text-2xl font-extrabold text-gray-800 dark:text-white">Rutinas Personalizadas</h3>
          </div>

          <div v-for="modalidad in personalRutinas.modalidades" :key="modalidad.nombre" class="mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden border border-gray-200 dark:border-gray-700">
              <button
                @click="toggleAcordeon('Personalizada', modalidad.nombre)"
                class="w-full px-6 py-5 text-left flex justify-between items-center bg-gradient-to-r from-gray-50 to-white hover:from-gray-100 hover:to-gray-50 dark:from-gray-800 dark:to-gray-700 dark:hover:from-gray-700 dark:hover:to-gray-600 transition-all duration-200"
              >
                <div class="flex items-center">
                  <span class="text-xl font-bold text-gray-800 dark:text-white text-indigo-600 dark:text-indigo-400">{{ modalidad.nombre }}</span>
                  <span class="ml-3 px-3 py-1 text-sm font-medium bg-indigo-100 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-300 rounded-full">
                    {{ modalidad.dias.length }} días
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
                    class="w-full px-6 py-4 text-left flex justify-between items-center bg-blue-50 hover:bg-blue-100 dark:bg-gray-700 dark:hover:bg-gray-600 transition-colors"
                  >
                    <div class="flex items-center">
                      <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z" />
                      </svg>
                      <span class="font-semibold text-blue-800 dark:text-blue-300">{{ dia.nombre }}</span>
                      <span class="ml-2 text-sm text-blue-600 dark:text-blue-400">({{ dia.ejercicios.length }} ejercicios)</span>
                    </div>
                    <svg :class="{'rotate-180': isDiaOpen('Personalizada', modalidad.nombre, dia.nombre)}" class="w-5 h-5 text-blue-600 dark:text-blue-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                  </button>

                  <div v-if="isDiaOpen('Personalizada', modalidad.nombre, dia.nombre)" class="p-5 bg-gray-50 dark:bg-gray-900">
                    <table class="w-full text-sm">
                      <thead>
                        <tr class="bg-white dark:bg-gray-800 border-b-2 border-gray-200 dark:border-gray-700">
                          <th class="px-4 py-3 text-left font-bold text-gray-700 dark:text-gray-300">Ejercicio</th>
                          <th class="px-4 py-3 text-center font-bold text-gray-700 dark:text-gray-300">Series</th>
                          <th class="px-4 py-3 text-center font-bold text-gray-700 dark:text-gray-300">Reps</th>
                          <th class="px-4 py-3 text-center font-bold text-gray-700 dark:text-gray-300">Descanso</th>
                          <th class="px-4 py-3 text-left font-bold text-gray-700 dark:text-gray-300 hidden md:table-cell">Descripción</th>
                        </tr>
                      </thead>
                      <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="ejercicio in dia.ejercicios" :key="ejercicio.id" class="bg-white hover:bg-gray-50 dark:bg-gray-800 dark:hover:bg-gray-700 transition-colors">
                          <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ ejercicio.ejercicio_nombre }}</td>
                          <td class="px-4 py-3 text-center text-gray-700 dark:text-gray-300">
                            <span class="px-2 py-1 bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300 rounded font-semibold">{{ ejercicio.series }}</span>
                          </td>
                          <td class="px-4 py-3 text-center text-gray-700 dark:text-gray-300">{{ ejercicio.reps_min }} - {{ ejercicio.reps_max }}</td>
                          <td class="px-4 py-3 text-center text-gray-700 dark:text-gray-300">
                            <span class="text-orange-600 dark:text-orange-400 font-medium">{{ ejercicio.descanso_min }} min</span>
                          </td>
                          <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs max-w-xs hidden md:table-cell">{{ ejercicio.ejercicio?.descripcion || '-' }}</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>

                <div class="p-5 bg-gradient-to-r from-gray-50 to-white dark:from-gray-900/20 dark:to-gray-800/20 border-t border-gray-200 dark:border-gray-700 flex flex-col sm:flex-row gap-3">
                  <button
                    @click="seleccionarRutina('Personalizada', modalidad.nombre)"
                    class="flex-1 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white px-6 py-4 rounded-lg font-bold transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5"
                  >
                    Seleccionar {{ modalidad.nombre }}
                  </button>
                  <button
                    @click="eliminarRutina('Personalizada', modalidad.nombre)"
                    class="bg-red-600 hover:bg-red-700 text-white px-6 py-4 rounded-lg font-bold transition-all duration-200 shadow-md hover:shadow-lg flex items-center justify-center gap-2"
                  >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    Eliminar Rutina
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Rutinas Predeterminadas -->
        <div :class="{'mt-12 pt-8 border-t border-gray-200 dark:border-gray-700': personalRutinas && Object.keys(personalRutinas.modalidades).length > 0}">
          <div v-for="(nivelData, nivelNombre) in defaultRutinas" :key="nivelNombre" class="mb-10">
            <div class="flex items-center mb-4">
              <div :class="getNivelColor(nivelNombre)" class="w-3 h-8 rounded-full mr-3"></div>
              <h3 class="text-2xl font-bold text-gray-800 dark:text-white">{{ nivelNombre }}</h3>
            </div>

            <div v-for="modalidad in nivelData.modalidades" :key="modalidad.nombre" class="mb-6">
              <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden border border-gray-200 dark:border-gray-700">
                <button
                  @click="toggleAcordeon(nivelNombre, modalidad.nombre)"
                  class="w-full px-6 py-5 text-left flex justify-between items-center bg-gradient-to-r from-gray-50 to-white hover:from-gray-100 hover:to-gray-50 dark:from-gray-800 dark:to-gray-700 dark:hover:from-gray-700 dark:hover:to-gray-600 transition-all duration-200"
                >
                  <div class="flex items-center">
                    <span class="text-xl font-bold text-gray-800 dark:text-white">{{ modalidad.nombre }}</span>
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
                      class="w-full px-6 py-4 text-left flex justify-between items-center bg-blue-50 hover:bg-blue-100 dark:bg-gray-700 dark:hover:bg-gray-600 transition-colors"
                    >
                      <div class="flex items-center">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                        <span class="font-semibold text-blue-800 dark:text-blue-300">{{ dia.nombre }}</span>
                        <span class="ml-2 text-sm text-blue-600 dark:text-blue-400">({{ dia.ejercicios.length }} ejercicios)</span>
                      </div>
                      <svg :class="{'rotate-180': isDiaOpen(nivelNombre, modalidad.nombre, dia.nombre)}" class="w-5 h-5 text-blue-600 dark:text-blue-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                      </svg>
                    </button>

                    <div v-if="isDiaOpen(nivelNombre, modalidad.nombre, dia.nombre)" class="p-5 bg-gray-50 dark:bg-gray-900">
                      <table class="w-full text-sm">
                        <thead>
                          <tr class="bg-white dark:bg-gray-800 border-b-2 border-gray-200 dark:border-gray-700">
                            <th class="px-4 py-3 text-left font-bold text-gray-700 dark:text-gray-300">Ejercicio</th>
                            <th class="px-4 py-3 text-center font-bold text-gray-700 dark:text-gray-300">Series</th>
                            <th class="px-4 py-3 text-center font-bold text-gray-700 dark:text-gray-300">Reps</th>
                            <th class="px-4 py-3 text-center font-bold text-gray-700 dark:text-gray-300">Descanso</th>
                            <th class="px-4 py-3 text-left font-bold text-gray-700 dark:text-gray-300 hidden md:table-cell">Descripción</th>
                          </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                          <tr v-for="ejercicio in dia.ejercicios" :key="ejercicio.id" class="bg-white hover:bg-gray-50 dark:bg-gray-800 dark:hover:bg-gray-700 transition-colors">
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ ejercicio.ejercicio_nombre }}</td>
                            <td class="px-4 py-3 text-center text-gray-700 dark:text-gray-300">
                              <span class="px-2 py-1 bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300 rounded font-semibold">{{ ejercicio.series }}</span>
                            </td>
                            <td class="px-4 py-3 text-center text-gray-700 dark:text-gray-300">{{ ejercicio.reps_min }} - {{ ejercicio.reps_max }}</td>
                            <td class="px-4 py-3 text-center text-gray-700 dark:text-gray-300">
                              <span class="text-orange-600 dark:text-orange-400 font-medium">{{ ejercicio.descanso_min }} min</span>
                            </td>
                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs max-w-xs hidden md:table-cell">{{ ejercicio.ejercicio?.descripcion || '-' }}</td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>

                  <div class="p-5 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border-t border-gray-200 dark:border-gray-700">
                    <button
                      @click="seleccionarRutina(nivelNombre, modalidad.nombre)"
                      class="w-full bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white px-6 py-4 rounded-lg font-bold transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5"
                    >
                      Seleccionar {{ nivelNombre }} - {{ modalidad.nombre }}
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

const rutinaStore = useRutinaStore();

const rutinasAgrupadas = ref({});
const openItems = ref({});
const isSelecting = ref(false);
const userRutina = ref(null);
const userRole = ref(null);
const isAlumno = computed(() => userRole.value === 'alumno');

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
    alert('No se pudo guardar la rutina. Intenta de nuevo.');
  } finally {
    isSelecting.value = false;
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
    alert('Rutina eliminada correctamente.');
    
    if (userRutina.value && userRutina.value.nivel === nivel && userRutina.value.modalidad === modalidad) {
      userRutina.value = null;
      rutinaStore.limpiar();
    }
    
    await fetchRutinas();
  } catch (error) {
    console.error('Error al eliminar la rutina:', error);
    alert(error.response?.data?.message || 'No se pudo eliminar la rutina.');
  }
};

onMounted(() => {
  fetchUserInfo();
  fetchRutinas();
});
</script>