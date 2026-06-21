<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
      <Breadcrumbs :items="[{ label: 'Inicio', href: '/dashboard' }, { label: 'Rutinas', href: '/rutinas' }, { label: 'Crear nueva' }]" />
      <div class="flex justify-between items-center mb-8">
        <h2 class="text-3xl font-bold text-gray-900 dark:text-white">Crear Nueva Rutina</h2>
        <div class="flex gap-3">
          <a
            href="/rutinas"
            class="px-5 py-3 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg font-medium transition-all"
          >
            Cancelar
          </a>
          <button
            @click="guardarRutina"
            :disabled="!esValida"
            class="ripple px-6 py-3 rounded-lg font-semibold transition-all shadow-md"
            :class="esValida ? 'bg-green-600 hover:bg-green-700 text-white' : 'bg-gray-300 text-gray-500 cursor-not-allowed'"
          >
            Guardar Rutina
          </button>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <div class="lg:col-span-1 space-y-4">
          <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-5 border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4">Configuración</h3>
            
            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Nombre de la Rutina</label>
                <input
                  v-model="rutina.nombre"
                  type="text"
                  placeholder="Ej. Mi Rutina de Fuerza"
                  class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500"
                  required
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Cantidad de Días</label>
                <select
                  v-model="rutina.modalidad"
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
            </div>
          </div>

          <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-5 border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4">Buscar Ejercicios</h3>
            <div class="relative">
              <input
                v-model="busqueda"
                type="text"
                placeholder="Escribe para buscar..."
                class="w-full px-4 py-2 pl-10 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500"
              />
              <svg class="absolute left-3 top-3 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
            
            <div v-if="resultados.length > 0" class="mt-2 max-h-64 overflow-y-auto border border-gray-200 dark:border-gray-600 rounded-lg">
              <button
                v-for="ej in resultados"
                :key="ej.id"
                @click="agregarEjercicioAGlobal(ej)"
                class="w-full px-4 py-2 text-left hover:bg-gray-50 dark:hover:bg-gray-700 border-b border-gray-100 dark:border-gray-700 last:border-b-0"
              >
                <span class="font-medium text-gray-800 dark:text-white">{{ ej.nombre }}</span>
                <span class="text-xs text-gray-500 dark:text-gray-400 ml-2">{{ ej.equipamiento }}</span>
              </button>
            </div>
            <p v-if="busqueda.length >= 2 && resultados.length === 0" class="mt-2 text-sm text-gray-500 dark:text-gray-400">
              No se encontraron ejercicios
            </p>
          </div>
        </div>

        <div class="lg:col-span-3">
          <div v-if="diasConfigurados.length > 0" class="space-y-4">
            <div
              v-for="(dia, index) in diasConfigurados"
              :key="index"
              class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border-2 transition-colors overflow-hidden"
              :class="dragOverIndex[`${index}-end`] ? 'border-indigo-500 dark:border-indigo-400' : 'border-gray-200 dark:border-gray-700'"
            >
              <div
                class="bg-gradient-to-r from-indigo-500 to-purple-500 px-6 py-4 flex justify-between items-center"
                @dragover="onDragOver(index, null, $event)"
                @dragleave="onDragLeave(index, null)"
                @drop="onDrop(index, null, $event)"
              >
                <h3 class="text-lg font-bold text-white">{{ dia.nombre }}</h3>
                <button
                  @click="quitarDia(index)"
                  class="text-white hover:text-red-200 p-1"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>

              <div class="p-4">
                <EmptyState
                  v-if="dia.ejercicios.length === 0"
                  emoji="💪"
                  title="Este día todavía no tiene ejercicios"
                  description="Buscá ejercicios abajo y agregalos a este día. Podés configurar series, repeticiones y descanso para cada uno."
                  variant="compact"
                />

                <div v-if="dia.ejercicios.length > 0" class="overflow-x-auto mb-4">
                  <table class="w-full text-sm">
                    <thead>
                      <tr class="border-b border-gray-200 dark:border-gray-700">
                        <th class="px-2 py-2 w-8"></th>
                        <th class="px-4 py-2 text-left font-semibold text-gray-600 dark:text-gray-400">Ejercicio</th>
                        <th class="px-4 py-2 text-center font-semibold text-gray-600 dark:text-gray-400">Superserie</th>
                        <th class="px-4 py-2 text-center font-semibold text-gray-600 dark:text-gray-400">Series</th>
                        <th class="px-4 py-2 text-center font-semibold text-gray-600 dark:text-gray-400">Reps Min</th>
                        <th class="px-4 py-2 text-center font-semibold text-gray-600 dark:text-gray-400">Reps Max</th>
                        <th class="px-4 py-2 text-center font-semibold text-gray-600 dark:text-gray-400">Descanso</th>
                        <th class="px-4 py-2"></th>
                      </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                      <tr
                        v-for="(ej, ejIndex) in dia.ejercicios"
                        :key="`${index}-${ejIndex}-${ej.ejercicio_nombre}`"
                        :draggable="true"
                        @dragstart="onDragStart(index, ejIndex, $event)"
                        @dragover="onDragOver(index, ejIndex, $event)"
                        @dragleave="onDragLeave(index, ejIndex)"
                        @drop="onDrop(index, ejIndex, $event)"
                        @dragend="onDragEnd"
                        :class="[
                          getSuperserieRowClass(ej.superserie_grupo),
                          'hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors cursor-grab active:cursor-grabbing',
                          dragOverIndex[`${index}-${ejIndex}`] ? 'ring-2 ring-indigo-500 ring-inset' : '',
                          draggingFromIndex === `${index}-${ejIndex}` ? 'opacity-40' : '',
                        ]"
                      >
                        <td class="px-2 py-2 text-center text-gray-400 cursor-grab select-none" aria-label="Arrastrar para reordenar">
                          <svg class="w-4 h-4 inline" fill="currentColor" viewBox="0 0 20 20">
                            <circle cx="7" cy="5" r="1.5" />
                            <circle cx="13" cy="5" r="1.5" />
                            <circle cx="7" cy="10" r="1.5" />
                            <circle cx="13" cy="10" r="1.5" />
                            <circle cx="7" cy="15" r="1.5" />
                            <circle cx="13" cy="15" r="1.5" />
                          </svg>
                        </td>
                        <td class="px-4 py-2 font-medium text-gray-800 dark:text-white">
                          <span class="inline-block w-6 text-xs text-gray-400 font-mono">{{ ejIndex + 1 }}.</span>
                          {{ ej.ejercicio_nombre }}
                        </td>
                        <td class="px-4 py-2 text-center">
                          <select
                            v-model="ej.superserie_grupo"
                            @mousedown.stop
                            @dragstart.stop
                            class="px-2 py-1 text-xs border border-gray-300 dark:border-gray-600 rounded dark:bg-gray-700 dark:text-gray-200 focus:ring-1 focus:ring-indigo-500"
                          >
                            <option :value="null">Ninguna</option>
                            <option :value="1">Grupo 1</option>
                            <option :value="2">Grupo 2</option>
                            <option :value="3">Grupo 3</option>
                            <option :value="4">Grupo 4</option>
                          </select>
                        </td>
                        <td class="px-4 py-2">
                          <input
                            v-model.number="ej.series"
                            type="number"
                            min="1"
                            @mousedown.stop
                            @dragstart.stop
                            class="w-16 px-2 py-1 border border-gray-300 dark:border-gray-600 rounded text-center dark:bg-gray-700 dark:text-gray-200"
                          />
                        </td>
                        <td class="px-4 py-2">
                          <input
                            v-model="ej.reps_min"
                            type="text"
                            @mousedown.stop
                            @dragstart.stop
                            class="w-20 px-2 py-1 border border-gray-300 dark:border-gray-600 rounded text-center dark:bg-gray-700 dark:text-gray-200"
                          />
                        </td>
                        <td class="px-4 py-2">
                          <input
                            v-model="ej.reps_max"
                            type="text"
                            @mousedown.stop
                            @dragstart.stop
                            class="w-20 px-2 py-1 border border-gray-300 dark:border-gray-600 rounded text-center dark:bg-gray-700 dark:text-gray-200"
                          />
                        </td>
                        <td class="px-4 py-2">
                          <input
                            v-model.number="ej.descanso_min"
                            type="number"
                            step="0.5"
                            min="0"
                            @mousedown.stop
                            @dragstart.stop
                            class="w-20 px-2 py-1 border border-gray-300 dark:border-gray-600 rounded text-center dark:bg-gray-700 dark:text-gray-200"
                          />
                        </td>
                        <td class="px-4 py-2 text-center">
                          <button
                            @click="quitarEjercicio(index, ejIndex)"
                            @mousedown.stop
                            @dragstart.stop
                            class="text-red-600 hover:text-red-800 p-1"
                            :aria-label="`Quitar ${ej.ejercicio_nombre} de ${dia.nombre}`"
                            title="Quitar ejercicio"
                          >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                          </button>
                        </td>
                      </tr>
                      <!-- Drop zone al final del día (también recibe drops desde otros días) -->
                      <tr
                        @dragover="onDragOver(index, null, $event)"
                        @dragleave="onDragLeave(index, null)"
                        @drop="onDrop(index, null, $event)"
                        :class="[
                          'transition-colors',
                          dragOverIndex[`${index}-end`]
                            ? 'bg-indigo-50 dark:bg-indigo-950/30 ring-2 ring-indigo-500 ring-inset'
                            : 'opacity-0 hover:opacity-100',
                        ]"
                      >
                        <td colspan="8" class="px-2 py-3 text-center text-xs text-indigo-600 dark:text-indigo-400 italic">
                          <span v-if="dragOverIndex[`${index}-end`]">⬇ Soltá acá para mover a {{ dia.nombre }}</span>
                          <span v-else>&nbsp;</span>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                  <p class="mt-1 text-xs text-gray-400 dark:text-gray-500 italic px-1">
                    💡 Arrastrá las filas (≡) para reordenar — también podés mover ejercicios entre días arrastrándolos al final de otra lista o soltándolos sobre la cabecera del día.
                  </p>
                </div>

                <div class="mt-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                  <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Agregar ejercicio a {{ dia.nombre }}:</p>
                  <div class="flex gap-2">
                    <input
                      v-model="dia.busquedaEjercicio"
                      @input="onBuscarInput(index)"
                      type="text"
                      :placeholder="'Buscar ejercicio...'"
                      class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-600 dark:text-gray-200"
                    />
                  </div>
                  <div v-if="dia.resultadosBusqueda && dia.resultadosBusqueda.length > 0" class="mt-2 max-h-40 overflow-y-auto border border-gray-200 dark:border-gray-600 rounded-lg">
                    <button
                      v-for="ej in dia.resultadosBusqueda"
                      :key="ej.id"
                      @click="agregarEjercicioADia(index, ej)"
                      class="w-full px-4 py-2 text-left hover:bg-indigo-50 dark:hover:bg-indigo-900/20 border-b border-gray-100 dark:border-gray-700 last:border-b-0"
                    >
                      <span class="font-medium text-gray-800 dark:text-white">{{ ej.nombre }}</span>
                      <span class="text-xs text-gray-500 dark:text-gray-400 ml-2">{{ ej.equipamiento }}</span>
                    </button>
                  </div>
                  <p v-if="dia.busquedaEjercicio && dia.busquedaEjercicio.length >= 2 && (!dia.resultadosBusqueda || dia.resultadosBusqueda.length === 0)" class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Sin resultados
                  </p>
                </div>
              </div>
            </div>

            <button
              v-if="diasConfigurados.length < parseInt(rutina.modalidad || 0)"
              @click="agregarDia"
              class="w-full py-4 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl text-gray-500 dark:text-gray-400 hover:border-indigo-500 hover:text-indigo-500 transition-all flex items-center justify-center gap-2"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
              </svg>
              Agregar Día
            </button>
          </div>

          <div v-else class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
            <EmptyState
              emoji="📋"
              title="Empezá tu rutina"
              description="Seleccioná el nombre y la cantidad de días en el panel de la izquierda. Después vas a poder agregar ejercicios a cada día con sus series, repeticiones y descanso."
            />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import axios from 'axios';
import { useToast } from '../composables/useToast';
import Breadcrumbs from './Breadcrumbs.vue';
import EmptyState from './EmptyState.vue';

const toast = useToast();

const rutina = ref({
  nombre: '',
  modalidad: '',
});

const getSuperserieRowClass = (grupo) => {
  if (!grupo) return '';
  switch (grupo) {
    case 1: return 'border-l-4 border-indigo-500 dark:border-indigo-400 bg-indigo-50/10 dark:bg-indigo-950/20';
    case 2: return 'border-l-4 border-emerald-500 dark:border-emerald-400 bg-emerald-50/10 dark:bg-emerald-950/20';
    case 3: return 'border-l-4 border-pink-500 dark:border-pink-400 bg-pink-50/10 dark:bg-pink-950/20';
    case 4: return 'border-l-4 border-amber-500 dark:border-amber-400 bg-amber-50/10 dark:bg-amber-950/20';
    default: return 'border-l-4 border-gray-500 dark:border-gray-400 bg-gray-50/10 dark:bg-gray-950/20';
  }
};

const diasConfigurados = ref([]);
const busqueda = ref('');
const resultados = ref([]);

const esValida = computed(() => {
  return rutina.value.nombre && 
         rutina.value.modalidad && 
         diasConfigurados.value.length > 0 &&
         diasConfigurados.value.some(d => d.ejercicios.length > 0);
});

watch(() => rutina.value.modalidad, (nuevaModalidad) => {
  if (nuevaModalidad) {
    const numDias = parseInt(nuevaModalidad);
    const diasActuales = diasConfigurados.value.length;
    
    if (numDias > diasActuales) {
      for (let i = diasActuales; i < numDias; i++) {
        diasConfigurados.value.push({
          nombre: `Día ${i + 1}`,
          ejercicios: [],
          busquedaEjercicio: '',
          resultadosBusqueda: [],
        });
      }
    } else if (numDias < diasActuales) {
      diasConfigurados.value = diasConfigurados.value.slice(0, numDias);
    }
  }
});

watch(busqueda, async (nuevaBusqueda) => {
  if (nuevaBusqueda && nuevaBusqueda.length >= 2) {
    try {
      const response = await axios.get('/api/ejercicios', { params: { busqueda: nuevaBusqueda } });
      resultados.value = response.data.data || response.data;
    } catch (error) {
      console.error('Error:', error);
      resultados.value = [];
    }
  } else {
    resultados.value = [];
  }
});

const onBuscarInput = async (diaIndex) => {
  const texto = diasConfigurados.value[diaIndex].busquedaEjercicio;
  if (texto && texto.length >= 2) {
    try {
      const response = await axios.get('/api/ejercicios', { params: { busqueda: texto } });
      diasConfigurados.value[diaIndex].resultadosBusqueda = response.data.data || response.data;
    } catch (error) {
      console.error('Error:', error);
      diasConfigurados.value[diaIndex].resultadosBusqueda = [];
    }
  } else {
    diasConfigurados.value[diaIndex].resultadosBusqueda = [];
  }
};

const agregarEjercicioAGlobal = (ejercicio) => {
  if (diasConfigurados.value.length > 0) {
    agregarEjercicioADia(0, ejercicio);
  }
  busqueda.value = '';
  resultados.value = [];
};

const agregarEjercicioADia = (diaIndex, ejercicio) => {
  diasConfigurados.value[diaIndex].ejercicios.push({
    ejercicio_nombre: ejercicio.nombre,
    series: 3,
    reps_min: '8',
    reps_max: '12',
    descanso_min: 1.5,
    orden: diasConfigurados.value[diaIndex].ejercicios.length,
    superserie_grupo: null,
  });
  diasConfigurados.value[diaIndex].busquedaEjercicio = '';
  diasConfigurados.value[diaIndex].resultadosBusqueda = [];
};

const quitarEjercicio = (diaIndex, ejIndex) => {
  const removed = diasConfigurados.value[diaIndex].ejercicios.splice(ejIndex, 1)[0];
  toast.info(`Ejercicio "${removed.ejercicio_nombre}" quitado`, {
    duration: 5000,
    action: {
      label: 'Deshacer',
      onClick: () => {
        diasConfigurados.value[diaIndex].ejercicios.splice(ejIndex, 0, removed);
      },
    },
  });
};

const quitarDia = async (diaIndex) => {
  const confirmed = await toast.confirm(
    `¿Eliminar "${diasConfigurados.value[diaIndex].nombre}" y todos sus ejercicios?`,
    { title: 'Eliminar día', confirmLabel: 'Sí, eliminar', type: 'error' }
  );
  if (!confirmed) return;
  const removed = diasConfigurados.value.splice(diaIndex, 1)[0];
  toast.info(`"${removed.nombre}" eliminado`, {
    duration: 5000,
    action: {
      label: 'Deshacer',
      onClick: () => {
        diasConfigurados.value.splice(diaIndex, 0, removed);
      },
    },
  });
};

const agregarDia = () => {
  diasConfigurados.value.push({
    nombre: `Día ${diasConfigurados.value.length + 1}`,
    ejercicios: [],
    busquedaEjercicio: '',
    resultadosBusqueda: [],
  });
};

const guardarRutina = async () => {
  if (!esValida.value) return;

  try {
    const ejerciciosParaGuardar = [];
    diasConfigurados.value.forEach((dia, diaIndex) => {
      dia.ejercicios.forEach((ej, ejIndex) => {
        ejerciciosParaGuardar.push({
          nivel: 'Personalizada',
          modalidad: rutina.value.nombre,
          dia: dia.nombre,
          ejercicio_nombre: ej.ejercicio_nombre,
          series: ej.series,
          reps_min: ej.reps_min,
          reps_max: ej.reps_max,
          descanso_min: ej.descanso_min,
          orden: ejIndex,
          superserie_grupo: ej.superserie_grupo,
        });
      });
    });

    for (const ejercicio of ejerciciosParaGuardar) {
      await axios.post('/api/rutinas', ejercicio);
    }

    alert('Rutina guardada correctamente');
    window.location.href = '/rutinas';
  } catch (error) {
    console.error('Error:', error);
    alert('No se pudo guardar la rutina');
  }
};

// === Drag & drop para reordenar ejercicios (dentro del día O entre días) ===
// Estructura de la clave: "diaIndex-ejIndex" (ejIndex es null para dropear al final del día).
const draggingFromIndex = ref(null);
const dragOverIndex = ref({});

const onDragStart = (diaIndex, ejIndex, event) => {
  draggingFromIndex.value = `${diaIndex}-${ejIndex}`;
  event.dataTransfer.effectAllowed = 'move';
  // Necesario para que Firefox dispare el drop
  event.dataTransfer.setData('text/plain', `${diaIndex}-${ejIndex}`);
};

const onDragOver = (diaIndex, ejIndex, event) => {
  event.dataTransfer.dropEffect = 'move';
  const key = ejIndex === null ? `${diaIndex}-end` : `${diaIndex}-${ejIndex}`;
  if (draggingFromIndex.value === `${diaIndex}-${ejIndex}`) return;
  if (!dragOverIndex.value[key]) {
    dragOverIndex.value = { ...dragOverIndex.value, [key]: true };
  }
};

const onDragLeave = (diaIndex, ejIndex) => {
  const key = ejIndex === null ? `${diaIndex}-end` : `${diaIndex}-${ejIndex}`;
  if (dragOverIndex.value[key]) {
    const copy = { ...dragOverIndex.value };
    delete copy[key];
    dragOverIndex.value = copy;
  }
};

const onDrop = (diaIndex, targetEjIndex, event) => {
  event.preventDefault();
  const fromKey = event.dataTransfer.getData('text/plain') || draggingFromIndex.value;
  if (!fromKey) return;
  const [fromDiaStr, fromEjStr] = fromKey.split('-');
  const fromDia = parseInt(fromDiaStr, 10);
  const fromEj = parseInt(fromEjStr, 10);
  if (Number.isNaN(fromDia) || Number.isNaN(fromEj)) return;

  const sourceEjercicios = diasConfigurados.value[fromDia]?.ejercicios;
  const targetEjercicios = diasConfigurados.value[diaIndex]?.ejercicios;
  if (!sourceEjercicios || !targetEjercicios) return;

  // targetEjIndex=null significa "agregar al final"
  const insertIndex = targetEjIndex === null
    ? targetEjercicios.length
    : Math.max(0, Math.min(targetEjIndex, targetEjercicios.length));

  // Mismo lugar: no hacer nada
  if (fromDia === diaIndex && (targetEjIndex === null ? fromEj === targetEjercicios.length - 1 : fromEj === targetEjIndex)) {
    draggingFromIndex.value = null;
    dragOverIndex.value = {};
    return;
  }

  const [moved] = sourceEjercicios.splice(fromEj, 1);
  targetEjercicios.splice(insertIndex, 0, moved);

  draggingFromIndex.value = null;
  dragOverIndex.value = {};
};

const onDragEnd = () => {
  draggingFromIndex.value = null;
  dragOverIndex.value = {};
};
</script>