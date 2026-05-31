<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
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
            class="px-6 py-3 rounded-lg font-semibold transition-all shadow-md"
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
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Nivel</label>
                <select
                  v-model="rutina.nivel"
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
              class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden"
            >
              <div class="bg-gradient-to-r from-indigo-500 to-purple-500 px-6 py-4 flex justify-between items-center">
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
                <div v-if="dia.ejercicios.length > 0" class="overflow-x-auto mb-4">
                  <table class="w-full text-sm">
                    <thead>
                      <tr class="border-b border-gray-200 dark:border-gray-700">
                        <th class="px-4 py-2 text-left font-semibold text-gray-600 dark:text-gray-400">Ejercicio</th>
                        <th class="px-4 py-2 text-center font-semibold text-gray-600 dark:text-gray-400">Series</th>
                        <th class="px-4 py-2 text-center font-semibold text-gray-600 dark:text-gray-400">Reps Min</th>
                        <th class="px-4 py-2 text-center font-semibold text-gray-600 dark:text-gray-400">Reps Max</th>
                        <th class="px-4 py-2 text-center font-semibold text-gray-600 dark:text-gray-400">Descanso</th>
                        <th class="px-4 py-2 text-center font-semibold text-gray-600 dark:text-gray-400">Orden</th>
                        <th class="px-4 py-2"></th>
                      </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                      <tr v-for="(ej, ejIndex) in dia.ejercicios" :key="ejIndex" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="px-4 py-2 font-medium text-gray-800 dark:text-white">{{ ej.ejercicio_nombre }}</td>
                        <td class="px-4 py-2">
                          <input
                            v-model.number="ej.series"
                            type="number"
                            min="1"
                            class="w-16 px-2 py-1 border border-gray-300 dark:border-gray-600 rounded text-center dark:bg-gray-700 dark:text-gray-200"
                          />
                        </td>
                        <td class="px-4 py-2">
                          <input
                            v-model="ej.reps_min"
                            type="text"
                            class="w-20 px-2 py-1 border border-gray-300 dark:border-gray-600 rounded text-center dark:bg-gray-700 dark:text-gray-200"
                          />
                        </td>
                        <td class="px-4 py-2">
                          <input
                            v-model="ej.reps_max"
                            type="text"
                            class="w-20 px-2 py-1 border border-gray-300 dark:border-gray-600 rounded text-center dark:bg-gray-700 dark:text-gray-200"
                          />
                        </td>
                        <td class="px-4 py-2">
                          <input
                            v-model.number="ej.descanso_min"
                            type="number"
                            step="0.5"
                            min="0"
                            class="w-20 px-2 py-1 border border-gray-300 dark:border-gray-600 rounded text-center dark:bg-gray-700 dark:text-gray-200"
                          />
                        </td>
                        <td class="px-4 py-2">
                          <input
                            v-model.number="ej.orden"
                            type="number"
                            min="0"
                            class="w-16 px-2 py-1 border border-gray-300 dark:border-gray-600 rounded text-center dark:bg-gray-700 dark:text-gray-200"
                          />
                        </td>
                        <td class="px-4 py-2 text-center">
                          <button
                            @click="quitarEjercicio(index, ejIndex)"
                            class="text-red-600 hover:text-red-800 p-1"
                          >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                          </button>
                        </td>
                      </tr>
                    </tbody>
                  </table>
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

          <div v-else class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-12 border border-gray-200 dark:border-gray-700 text-center">
            <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
            </svg>
            <p class="text-gray-500 dark:text-gray-400 mb-4">Selecciona el nivel y días para comenzar</p>
            <p class="text-sm text-gray-400 dark:text-gray-500">Luego podrás agregar ejercicios a cada día</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';

const router = useRouter();

const rutina = ref({
  nivel: '',
  modalidad: '',
});

const diasConfigurados = ref([]);
const busqueda = ref('');
const resultados = ref([]);

const esValida = computed(() => {
  return rutina.value.nivel && 
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
  });
  diasConfigurados.value[diaIndex].busquedaEjercicio = '';
  diasConfigurados.value[diaIndex].resultadosBusqueda = [];
};

const quitarEjercicio = (diaIndex, ejIndex) => {
  diasConfigurados.value[diaIndex].ejercicios.splice(ejIndex, 1);
};

const quitarDia = (diaIndex) => {
  diasConfigurados.value.splice(diaIndex, 1);
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
          nivel: rutina.value.nivel,
          modalidad: rutina.value.modalidad,
          dia: dia.nombre,
          ejercicio_nombre: ej.ejercicio_nombre,
          series: ej.series,
          reps_min: ej.reps_min,
          reps_max: ej.reps_max,
          descanso_min: ej.descanso_min,
          orden: ejIndex,
        });
      });
    });

    for (const ejercicio of ejerciciosParaGuardar) {
      await axios.post('/api/rutinas', ejercicio);
    }

    alert('Rutina guardada correctamente');
    router.push('/rutinas');
  } catch (error) {
    console.error('Error:', error);
    alert('No se pudo guardar la rutina');
  }
};
</script>