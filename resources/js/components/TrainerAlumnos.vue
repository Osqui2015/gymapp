<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between items-center mb-8">
        <h2 class="text-3xl font-bold text-gray-900 dark:text-white">Gestión de Trainer-Alumnos</h2>
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
        <!-- Selector de Trainer (solo para admins) -->
        <div v-if="userRole === 'administrador'" class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6 border border-gray-200 dark:border-gray-700">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Seleccionar Trainer</label>
          <select
            v-model="trainerSeleccionado"
            @change="onTrainerChange"
            class="w-full sm:w-64 px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500"
          >
            <option value="">-- Seleccionar un trainer --</option>
            <option v-for="trainer in trainers" :key="trainer.id" :value="trainer.id">
              {{ trainer.name }} ({{ trainer.role === 'administrador' ? 'Admin' : 'Trainer' }})
            </option>
          </select>
        </div>

        <!-- Lista de Alumnos con checkboxes -->
        <div v-if="userRole === 'administrador' && trainerSeleccionado" class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden border border-gray-200 dark:border-gray-700">
          <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
              Alumnos para {{ trainerNombre }}
            </h3>
            <button
              @click="guardarCambios"
              :disabled="guardando"
              class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 rounded-lg text-sm font-semibold transition-all shadow-md hover:shadow-lg disabled:opacity-50"
            >
              {{ guardando ? 'Guardando...' : 'Guardar Cambios' }}
            </button>
          </div>

          <!-- Buscador y Seleccionar todos -->
          <div class="p-4 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700 flex flex-col sm:flex-row justify-between items-center gap-4">
            <input
              v-model="busquedaAlumnos"
              type="text"
              placeholder="Buscar alumno por nombre, nick o email..."
              class="w-full sm:w-80 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500"
            />
            <label class="flex items-center gap-2 cursor-pointer whitespace-nowrap">
              <input
                type="checkbox"
                v-model="todosSeleccionados"
                @change="toggleTodos"
                class="w-5 h-5 text-indigo-600 rounded border-gray-300 dark:border-gray-600 focus:ring-indigo-500"
              />
              <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                Seleccionar todos ({{ alumnosFiltrados.length }} alumnos)
              </span>
            </label>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-800 border-b-2 border-gray-200 dark:border-gray-700">
                  <th class="px-4 py-4 text-left w-12">
                    <span class="sr-only">Seleccionar</span>
                  </th>
                  <th class="px-4 py-4 text-left font-bold text-gray-700 dark:text-gray-300">Alumno</th>
                  <th class="px-4 py-4 text-center font-bold text-gray-700 dark:text-gray-300">Email</th>
                  <th class="px-4 py-4 text-center font-bold text-gray-700 dark:text-gray-300">Trainer Actual</th>
                  <th class="px-4 py-4 text-center font-bold text-gray-700 dark:text-gray-300">Estado</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                <tr v-for="alumno in alumnosFiltrados" :key="alumno.id" class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                  <td class="px-4 py-4 text-center">
                    <input
                      type="checkbox"
                      :value="alumno.id"
                      v-model="alumnosSeleccionados"
                      class="w-5 h-5 text-indigo-600 rounded border-gray-300 dark:border-gray-600 focus:ring-indigo-500"
                    />
                  </td>
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
                    <span v-if="alumno.trainer" class="text-sm text-gray-700 dark:text-gray-300">
                      {{ alumno.trainer.name }}
                    </span>
                    <span v-else class="text-gray-400 dark:text-gray-500">Sin asignar</span>
                  </td>
                  <td class="px-4 py-4 text-center">
                    <span
                      v-if="esAlumnoDelTrainer(alumno)"
                      class="px-3 py-1 bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300 rounded-full text-xs font-semibold"
                    >
                      Asignado ✓
                    </span>
                    <span
                      v-else
                      class="px-3 py-1 bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400 rounded-full text-xs font-semibold"
                    >
                      Sin asignar
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-if="alumnosFiltrados.length === 0" class="p-12 text-center">
            <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            <p class="text-gray-500 dark:text-gray-400">No hay alumnos disponibles</p>
          </div>
        </div>

        <!-- Vista de trainer (no admin) -->
        <div v-if="userRole !== 'administrador'">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
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
                  <p class="text-sm text-gray-500 dark:text-gray-400">Con Rutina</p>
                  <p class="mt-2 text-3xl font-bold text-green-600 dark:text-green-400">{{ alumnosConRutina }}</p>
                </div>
                <div class="bg-green-100 dark:bg-green-900/30 p-3 rounded-full">
                  <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
              </div>
            </div>
          </div>

          <!-- Buscador -->
          <div class="mb-4">
            <input
              v-model="busquedaAlumnos"
              type="text"
              placeholder="Buscar alumno por nombre, nick o email..."
              class="w-full sm:w-80 px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500"
            />
          </div>

          <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden border border-gray-200 dark:border-gray-700">
            <div class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead>
                  <tr class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-800 border-b-2 border-gray-200 dark:border-gray-700">
                    <th class="px-4 py-4 text-left font-bold text-gray-700 dark:text-gray-300">Alumno</th>
                    <th class="px-4 py-4 text-center font-bold text-gray-700 dark:text-gray-300">Email</th>
                    <th class="px-4 py-4 text-center font-bold text-gray-700 dark:text-gray-300">Rutina</th>
                    <th class="px-4 py-4 text-center font-bold text-gray-700 dark:text-gray-300">Día Actual</th>
                    <th class="px-4 py-4 text-center font-bold text-gray-700 dark:text-gray-300">Acciones</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                  <tr v-for="alumno in alumnosFiltrados" :key="alumno.id" class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
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

            <div v-if="alumnosFiltrados.length === 0" class="p-12 text-center">
              <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
              <p class="text-gray-500 dark:text-gray-400">{{ busquedaAlumnos ? 'No se encontraron alumnos' : 'No tienes alumnos asignados' }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal para asignar rutina (solo para trainers no admins) -->
    <div v-if="mostrarModal && userRole !== 'administrador'" class="fixed inset-0 z-50 overflow-y-auto" @click.self="cerrarModal">
      <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity bg-gray-900 bg-opacity-75" @click="cerrarModal"></div>

        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
          <div class="bg-white dark:bg-gray-800 px-6 pt-5 pb-4 sm:p-6 sm:pb-4">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
              Asignar Rutina a {{ alumnoSeleccionado?.name }}
            </h3>

            <div class="space-y-4">
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

              <div v-if="rutinaSeleccionada" class="p-4 bg-indigo-50 dark:bg-indigo-900/20 rounded-lg border border-indigo-200 dark:border-indigo-800">
                <h4 class="font-semibold text-indigo-700 dark:text-indigo-300 mb-2">Detalles de la Rutina:</h4>
                <div class="grid grid-cols-2 gap-2 text-sm">
                  <p><span class="text-gray-500">Nivel:</span> <span class="font-medium">{{ rutinaSeleccionada.nivel }}</span></p>
                  <p><span class="text-gray-500">Modalidad:</span> <span class="font-medium">{{ rutinaSeleccionada.modalidad }}</span></p>
                  <p><span class="text-gray-500">Día:</span> <span class="font-medium">{{ rutinaSeleccionada.dia }}</span></p>
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

const userRole = ref('comun');
const trainers = ref([]);
const trainerSeleccionado = ref('');
const trainerNombre = ref('');
const alumnos = ref([]);
const alumnosSeleccionados = ref([]);
const todosSeleccionados = ref(false);
const busquedaAlumnos = ref('');
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

const fetchUserInfo = async () => {
  try {
    const response = await axios.get('/api/user-info');
    userRole.value = response.data.role;
  } catch (error) {
    console.error('Error al obtener rol:', error);
  }
};

const fetchTrainersYAlumnos = async () => {
  try {
    const response = await axios.get('/api/admin/trainers-alumnos');
    trainers.value = response.data.trainers;
    alumnos.value = response.data.alumnos;
  } catch (error) {
    console.error('Error:', error);
    showToast('No se pudieron cargar los datos', 'error');
  } finally {
    cargando.value = false;
  }
};

const fetchAlumnosDelTrainer = async () => {
  try {
    const response = await axios.get('/api/trainer/alumnos');
    alumnos.value = response.data;
  } catch (error) {
    console.error('Error:', error);
  } finally {
    cargando.value = false;
  }
};

const fetchRutinas = async () => {
  try {
    const response = await axios.get('/api/trainer/mis-rutinas');
    const grouped = {};
    
    response.data.forEach(r => {
      if (!grouped[r.id]) {
        grouped[r.id] = { ...r, ejercicios: [] };
      }
      if (r.ejercicio) {
        grouped[r.id].ejercicios.push(r.ejercicio);
      }
    });
    
    rutinas.value = Object.values(grouped);
  } catch (error) {
    console.error('Error:', error);
  }
};

const onTrainerChange = () => {
  if (trainerSeleccionado.value) {
    const trainer = trainers.value.find(t => t.id === parseInt(trainerSeleccionado.value));
    trainerNombre.value = trainer ? trainer.name : '';
    
    // Marcar los alumnos que ya están asignados a este trainer
    alumnosSeleccionados.value = alumnos.value
      .filter(a => a.trainer && a.trainer.id === parseInt(trainerSeleccionado.value))
      .map(a => a.id);
  } else {
    trainerNombre.value = '';
    alumnosSeleccionados.value = [];
  }
  todosSeleccionados.value = false;
};

const esAlumnoDelTrainer = (alumno) => {
  return alumno.trainer && alumno.trainer.id === parseInt(trainerSeleccionado.value);
};

const toggleTodos = () => {
  if (todosSeleccionados.value) {
    alumnosSeleccionados.value = alumnos.value.map(a => a.id);
  } else {
    alumnosSeleccionados.value = [];
  }
};

const guardarCambios = async () => {
  guardando.value = true;
  try {
    await axios.post(`/api/admin/trainers/${trainerSeleccionado.value}/assign-alumnos`, {
      alumno_ids: alumnosSeleccionados.value
    });
    
    // Recargar datos
    await fetchTrainersYAlumnos();
    onTrainerChange();
    
    showToast('Cambios guardados correctamente');
  } catch (error) {
    console.error('Error:', error);
    showToast(error.response?.data?.error || 'No se pudieron guardar los cambios', 'error');
  } finally {
    guardando.value = false;
  }
};

const alumnosConRutina = computed(() => {
  return alumnos.value.filter(a => a.rutina_seleccionada).length;
});

const alumnosFiltrados = computed(() => {
  if (!busquedaAlumnos.value.trim()) {
    return alumnos.value;
  }
  const search = busquedaAlumnos.value.toLowerCase();
  return alumnos.value.filter(alumno => 
    alumno.name.toLowerCase().includes(search) ||
    (alumno.nick && alumno.nick.toLowerCase().includes(search)) ||
    (alumno.email && alumno.email.toLowerCase().includes(search))
  );
});

const rutinasAgrupadas = computed(() => {
  const grupos = {};
  
  rutinas.value.forEach(rutina => {
    const key = `${rutina.nivel} - ${rutina.modalidad}`;
    if (!grupos[key]) {
      grupos[key] = { etiqueta: key, rutinas: [] };
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
    rutinaForm.value.dia_actual = 'Día 1';
  } else {
    rutinaSeleccionada.value = null;
  }
};

const abrirModalAsignar = (alumno) => {
  alumnoSeleccionado.value = alumno;
  
  if (alumno.rutina_seleccionada && alumno.rutina_seleccionada.rutina_id) {
    rutinaForm.value = {
      rutina_id: alumno.rutina_seleccionada.rutina_id.toString(),
      dia_actual: alumno.rutina_seleccionada.dia_actual || 'Día 1',
    };
    const rutinaExistente = rutinas.value.find(r => r.id === parseInt(alumno.rutina_seleccionada.rutina_id));
    if (rutinaExistente) {
      rutinaSeleccionada.value = rutinaExistente;
    }
  } else {
    rutinaForm.value = { rutina_id: '', dia_actual: 'Día 1' };
    rutinaSeleccionada.value = null;
  }
  
  mostrarModal.value = true;
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
    
    await fetchAlumnosDelTrainer();
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
  fetchUserInfo().then(() => {
    if (userRole.value === 'administrador') {
      fetchTrainersYAlumnos();
    } else {
      fetchAlumnosDelTrainer();
      fetchRutinas();
    }
  });
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