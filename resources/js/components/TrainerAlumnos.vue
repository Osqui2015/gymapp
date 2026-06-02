<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between items-center mb-8">
        <h2 class="text-3xl font-bold text-gray-900 dark:text-white">Gestión de Alumnos</h2>
        <a
          href="/dashboard"
          class="px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg font-medium transition-all flex items-center gap-2"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          Volver
        </a>
      </div>

      <div v-if="cargando" class="flex justify-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
      </div>

      <div v-else>
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
          <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Total Alumnos</p>
                <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ alumnos.length }}</p>
              </div>
              <div class="bg-indigo-100 dark:bg-indigo-900/30 p-3 rounded-full">
                <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
              </div>
            </div>
          </div>
          <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Con Rutina Asignada</p>
                <p class="mt-2 text-3xl font-bold text-green-600 dark:text-green-400">{{ alumnosConRutina }}</p>
              </div>
              <div class="bg-green-100 dark:bg-green-900/30 p-3 rounded-full">
                <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
            </div>
          </div>
          <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Sin Rutina</p>
                <p class="mt-2 text-3xl font-bold text-orange-600 dark:text-orange-400">{{ alumnosSinRutina }}</p>
              </div>
              <div class="bg-orange-100 dark:bg-orange-900/30 p-3 rounded-full">
                <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
              </div>
            </div>
          </div>
        </div>

        <!-- Lista de Alumnos -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden border border-gray-200 dark:border-gray-700">
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-800 border-b-2 border-gray-200 dark:border-gray-700">
                  <th class="px-4 py-4 text-left font-bold text-gray-700 dark:text-gray-300">Alumno</th>
                  <th class="px-4 py-4 text-center font-bold text-gray-700 dark:text-gray-300">Email</th>
                  <th class="px-4 py-4 text-center font-bold text-gray-700 dark:text-gray-300">Rutina Asignada</th>
                  <th class="px-4 py-4 text-center font-bold text-gray-700 dark:text-gray-300">Día Actual</th>
                  <th class="px-4 py-4 text-center font-bold text-gray-700 dark:text-gray-300">Acciones</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                <tr v-for="alumno in alumnos" :key="alumno.id" class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                  <td class="px-4 py-4">
                    <div class="flex items-center gap-3">
                      <div class="bg-indigo-100 dark:bg-indigo-900/30 w-10 h-10 rounded-full flex items-center justify-center">
                        <span class="text-indigo-600 dark:text-indigo-400 font-bold">{{ alumno.name.charAt(0).toUpperCase() }}</span>
                      </div>
                      <div>
                        <p class="font-medium text-gray-900 dark:text-white">{{ alumno.name }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">@{{ alumno.nick }}</p>
                      </div>
                    </div>
                  </td>
                  <td class="px-4 py-4 text-center text-gray-600 dark:text-gray-300">{{ alumno.email }}</td>
                  <td class="px-4 py-4 text-center">
                    <span v-if="alumno.rutina_seleccionada" class="px-3 py-1 bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300 rounded-full text-xs font-semibold">
                      {{ alumno.rutina_seleccionada.nivel }} {{ alumno.rutina_seleccionada.modalidad }}
                    </span>
                    <span v-else class="px-3 py-1 bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400 rounded-full text-xs font-semibold">
                      Sin asignar
                    </span>
                  </td>
                  <td class="px-4 py-4 text-center">
                    <span v-if="alumno.rutina_seleccionada" class="text-gray-700 dark:text-gray-300">
                      {{ alumno.rutina_seleccionada.dia_actual || 'Día 1' }}
                    </span>
                    <span v-else class="text-gray-400 dark:text-gray-500">-</span>
                  </td>
                  <td class="px-4 py-4 text-center">
                    <button
                      @click="abrirModalAsignar(alumno)"
                      class="inline-flex items-center gap-1 px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-medium transition-colors"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                      </svg>
                      {{ alumno.rutina_seleccionada ? 'Cambiar' : 'Asignar' }}
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-if="alumnos.length === 0" class="p-12 text-center">
            <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            <p class="text-gray-500 dark:text-gray-400">No tienes alumnos asignados</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal para asignar rutina -->
    <div v-if="mostrarModal" class="fixed inset-0 z-50 overflow-y-auto" @click.self="cerrarModal">
      <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity bg-gray-900 bg-opacity-75" @click="cerrarModal"></div>

        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
          <div class="bg-white dark:bg-gray-800 px-6 pt-5 pb-4 sm:p-6 sm:pb-4">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
              Asignar Rutina a {{ alumnoSeleccionado?.name }}
            </h3>

            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Nivel</label>
                <select
                  v-model="rutinaForm.nivel"
                  class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500"
                >
                  <option value="">Seleccionar nivel</option>
                  <option value="Principiante">Principiante</option>
                  <option value="Intermedio">Intermedio</option>
                  <option value="Avanzado">Avanzado</option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Modalidad (días)</label>
                <select
                  v-model="rutinaForm.modalidad"
                  class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500"
                >
                  <option value="">Seleccionar días</option>
                  <option value="1 Día">1 Día</option>
                  <option value="2 Días">2 Días</option>
                  <option value="3 Días">3 Días</option>
                  <option value="4 Días">4 Días</option>
                  <option value="5 Días">5 Días</option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Día actual</label>
                <select
                  v-model="rutinaForm.dia_actual"
                  class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500"
                >
                  <option v-for="n in maxDias" :key="n" :value="`Día ${n}`">Día {{ n }}</option>
                </select>
              </div>
            </div>
          </div>

          <div class="bg-gray-50 dark:bg-gray-700 px-6 py-4 sm:flex sm:flex-row-reverse">
            <button
              @click="asignarRutina"
              :disabled="!rutinaForm.nivel || !rutinaForm.modalidad || guardando"
              class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {{ guardando ? 'Guardando...' : 'Guardar' }}
            </button>
            <button
              @click="cerrarModal"
              class="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
            >
              Cancelar
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

const alumnos = ref([]);
const cargando = ref(true);
const mostrarModal = ref(false);
const alumnoSeleccionado = ref(null);
const guardando = ref(false);

const rutinaForm = ref({
  nivel: '',
  modalidad: '',
  dia_actual: 'Día 1',
});

const alumnosConRutina = computed(() => {
  return alumnos.value.filter(a => a.rutina_seleccionada).length;
});

const alumnosSinRutina = computed(() => {
  return alumnos.value.filter(a => !a.rutina_seleccionada).length;
});

const maxDias = computed(() => {
  const modalidad = rutinaForm.value.modalidad;
  if (!modalidad) return 1;
  return parseInt(modalidad) || 1;
});

const obtenerAlumnos = async () => {
  try {
    const response = await axios.get('/api/trainer/alumnos');
    alumnos.value = response.data;
  } catch (error) {
    console.error('Error:', error);
    alert('No se pudieron cargar los alumnos');
  } finally {
    cargando.value = false;
  }
};

const abrirModalAsignar = (alumno) => {
  alumnoSeleccionado.value = alumno;
  if (alumno.rutina_seleccionada) {
    rutinaForm.value = {
      nivel: alumno.rutina_seleccionada.nivel,
      modalidad: alumno.rutina_seleccionada.modalidad,
      dia_actual: alumno.rutina_seleccionada.dia_actual || 'Día 1',
    };
  } else {
    rutinaForm.value = {
      nivel: '',
      modalidad: '',
      dia_actual: 'Día 1',
    };
  }
  mostrarModal.value = true;
};

const cerrarModal = () => {
  mostrarModal.value = false;
  alumnoSeleccionado.value = null;
};

const asignarRutina = async () => {
  if (!alumnoSeleccionado.value) return;
  
  guardando.value = true;
  try {
    await axios.post(`/api/trainer/alumnos/${alumnoSeleccionado.value.id}/rutina`, {
      nivel: rutinaForm.value.nivel,
      modalidad: rutinaForm.value.modalidad,
      dia_actual: rutinaForm.value.dia_actual,
    });
    
    await obtenerAlumnos();
    cerrarModal();
    alert('Rutina asignada correctamente');
  } catch (error) {
    console.error('Error:', error);
    alert(error.response?.data?.error || 'No se pudo asignar la rutina');
  } finally {
    guardando.value = false;
  }
};

onMounted(() => {
  obtenerAlumnos();
});
</script>
