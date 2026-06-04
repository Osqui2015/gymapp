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

        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
          <div class="bg-white dark:bg-gray-800 px-6 pt-5 pb-4 sm:p-6 sm:pb-4">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
              Asignar Rutina a {{ alumnoSeleccionado?.name }}
            </h3>

            <div class="space-y-4">
              <!-- Selector de Rutina -->
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Seleccionar Rutina</label>
                <select
                  v-model="rutinaForm.rutina_id"
                  class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500"
                  @change="onRutinaChange"
                >
                  <option value="">-- Seleccionar una rutina --</option>
                  <optgroup v-for="grupo in rutinasAgrupadas" :key="grupo.etiqueta" :label="grupo.etiqueta">
                    <option v-for="rutina in grupo.rutinas" :key="rutina.id" :value="rutina.id">
                      {{ rutina.dia }} - {{ rutina.ejercicios_count || 0 }} ejercicios
                    </option>
                  </optgroup>
                </select>
                <p v-if="rutinas.length === 0" class="mt-1 text-sm text-amber-600 dark:text-amber-400">
                  ⚠️ No tienes rutinas creadas. Crea rutinas primero para asignarlas.
                </p>
              </div>

              <!-- Info de la rutina seleccionada -->
              <div v-if="rutinaSeleccionada" class="p-4 bg-indigo-50 dark:bg-indigo-900/20 rounded-lg border border-indigo-200 dark:border-indigo-800">
                <h4 class="font-semibold text-indigo-700 dark:text-indigo-300 mb-2">Detalles de la Rutina:</h4>
                <div class="grid grid-cols-2 gap-2 text-sm">
                  <p><span class="text-gray-500">Nivel:</span> <span class="font-medium">{{ rutinaSeleccionada.nivel }}</span></p>
                  <p><span class="text-gray-500">Modalidad:</span> <span class="font-medium">{{ rutinaSeleccionada.modalidad }}</span></p>
                  <p><span class="text-gray-500">Día:</span> <span class="font-medium">{{ rutinaSeleccionada.dia }}</span></p>
                </div>
                <div v-if="rutinaSeleccionada.ejercicios && rutinaSeleccionada.ejercicios.length > 0" class="mt-3">
                  <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Ejercicios incluidos:</p>
                  <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                    <li v-for="(ej, idx) in rutinaSeleccionada.ejercicios" :key="idx">
                      • {{ ej.ejercicio_nombre }} ({{ ej.series }}x{{ ej.reps_min }}-{{ ej.reps_max }})
                    </li>
                  </ul>
                </div>
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
              :disabled="!rutinaForm.rutina_id || guardando"
              class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {{ guardando ? 'Guardando...' : 'Asignar Rutina' }}
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

    <!-- Toast notifications -->
    <transition name="fade">
      <div v-if="toast.show" :class="['fixed bottom-4 right-4 px-6 py-3 rounded-lg shadow-lg text-white', toast.type === 'success' ? 'bg-green-600' : 'bg-red-600']">
        {{ toast.message }}
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

const alumnos = ref([]);
const rutinas = ref([]);
const cargando = ref(true);
const mostrarModal = ref(false);
const alumnoSeleccionado = ref(null);
const guardando = ref(false);
const rutinaSeleccionada = ref(null);

const toast = ref({
  show: false,
  message: '',
  type: 'success'
});

const rutinaForm = ref({
  rutina_id: '',
  dia_actual: 'Día 1',
});

const alumnosConRutina = computed(() => {
  return alumnos.value.filter(a => a.rutina_seleccionada).length;
});

const alumnosSinRutina = computed(() => {
  return alumnos.value.filter(a => !a.rutina_seleccionada).length;
});

// Agrupar rutinas por nivel y modalidad
const rutinasAgrupadas = computed(() => {
  const grupos = {};
  
  rutinas.value.forEach(rutina => {
    const key = `${rutina.nivel} - ${rutina.modalidad}`;
    if (!grupos[key]) {
      grupos[key] = {
        etiqueta: key,
        rutinas: []
      };
    }
    grupos[key].rutinas.push(rutina);
  });
  
  return Object.values(grupos);
});

const maxDias = computed(() => {
  if (rutinaSeleccionada.value) {
    return parseInt(rutinaSeleccionada.value.modalidad) || 1;
  }
  return 1;
});

const onRutinaChange = () => {
  if (rutinaForm.value.rutina_id) {
    rutinaSeleccionada.value = rutinas.value.find(r => r.id === parseInt(rutinaForm.value.rutina_id));
    // Actualizar día máximo según la modalidad de la rutina seleccionada
    rutinaForm.value.dia_actual = 'Día 1';
  } else {
    rutinaSeleccionada.value = null;
  }
};

const obtenerAlumnos = async () => {
  try {
    const response = await axios.get('/api/trainer/alumnos');
    alumnos.value = response.data;
  } catch (error) {
    console.error('Error:', error);
    showToast('No se pudieron cargar los alumnos', 'error');
  } finally {
    cargando.value = false;
  }
};

const obtenerRutinas = async () => {
  try {
    const response = await axios.get('/api/trainer/mis-rutinas');
    // Agrupar ejercicios por rutina
    const rutinasData = response.data;
    const grouped = {};
    
    rutinasData.forEach(r => {
      if (!grouped[r.id]) {
        grouped[r.id] = {
          ...r,
          ejercicios: []
        };
      }
      if (r.ejercicio) {
        grouped[r.id].ejercicios.push(r.ejercicio);
      }
    });
    
    rutinas.value = Object.values(grouped);
  } catch (error) {
    console.error('Error:', error);
    showToast('No se pudieron cargar las rutinas', 'error');
  }
};

const abrirModalAsignar = (alumno) => {
  alumnoSeleccionado.value = alumno;
  
  // Si ya tiene rutina asignada, cargarla
  if (alumno.rutina_seleccionada && alumno.rutina_seleccionada.rutina_id) {
    rutinaForm.value = {
      rutina_id: alumno.rutina_seleccionada.rutina_id.toString(),
      dia_actual: alumno.rutina_seleccionada.dia_actual || 'Día 1',
    };
    // Buscar la rutina completa
    const rutinaExistente = rutinas.value.find(r => r.id === parseInt(alumno.rutina_seleccionada.rutina_id));
    if (rutinaExistente) {
      rutinaSeleccionada.value = rutinaExistente;
    } else {
      // Cargar la rutina si no está en la lista
      cargarRutinaCompleta(alumno.rutina_seleccionada.rutina_id);
    }
  } else {
    rutinaForm.value = {
      rutina_id: '',
      dia_actual: 'Día 1',
    };
    rutinaSeleccionada.value = null;
  }
  
  mostrarModal.value = true;
};

const cargarRutinaCompleta = async (rutinaId) => {
  try {
    // Obtener todas las rutinas del servidor
    const response = await axios.get('/api/rutinas');
    // Buscar la rutina específica
    const rutina = response.data.find(r => r.id === rutinaId);
    if (rutina) {
      rutinaSeleccionada.value = {
        ...rutina,
        ejercicios: []
      };
    }
  } catch (error) {
    console.error('Error:', error);
  }
};

const cerrarModal = () => {
  mostrarModal.value = false;
  alumnoSeleccionado.value = null;
  rutinaSeleccionada.value = null;
};

const showToast = (message, type = 'success') => {
  toast.value = { show: true, message, type };
  setTimeout(() => {
    toast.value.show = false;
  }, 3000);
};

const asignarRutina = async () => {
  if (!alumnoSeleccionado.value || !rutinaForm.value.rutina_id) return;
  
  guardando.value = true;
  try {
    await axios.post('/api/trainer/asignar-rutina', {
      alumno_id: alumnoSeleccionado.value.id,
      rutina_id: parseInt(rutinaForm.value.rutina_id),
      dia_actual: rutinaForm.value.dia_actual,
    });
    
    await obtenerAlumnos();
    cerrarModal();
    showToast('Rutina asignada correctamente');
  } catch (error) {
    console.error('Error:', error);
    showToast(error.response?.data?.error || 'No se pudo asignar la rutina', 'error');
  } finally {
    guardando.value = false;
  }
};

onMounted(() => {
  obtenerAlumnos();
  obtenerRutinas();
});
</script>

<style scoped>
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
</style>