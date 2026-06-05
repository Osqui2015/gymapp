<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-6 pb-28 md:py-8 md:pb-8">
    <ToastNotification ref="toastRef" />
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
      <div v-if="rutinaStore.seleccionada" class="mb-8">
        <div class="rounded-2xl bg-gradient-to-r from-slate-950 via-slate-900 to-indigo-950 border border-slate-800 shadow-2xl p-4 md:p-8 mb-5 md:mb-6">
          <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
            <div>
              <p class="text-[10px] md:text-sm uppercase tracking-[0.24em] text-indigo-300 mb-2">Rutina activa</p>
              <h2 class="text-2xl md:text-4xl font-bold text-white leading-tight">
                {{ rutinaStore.seleccionada.nivel }}
              </h2>
              <div class="flex flex-wrap items-center gap-2 md:gap-3 mt-3 md:mt-4 text-xs md:text-sm text-slate-300">
                <span class="inline-flex items-center rounded-full bg-white/10 px-3 py-1 font-medium">
                  {{ rutinaStore.seleccionada.dias }}
                </span>
                <span class="inline-flex items-center rounded-full bg-emerald-500/15 px-3 py-1 font-medium text-emerald-200">
                  Día actual: {{ diaActual }}
                </span>
              </div>
            </div>

            <button
              @click="cambiarRutina"
              class="inline-flex items-center justify-center rounded-xl bg-white/10 px-4 py-2 md:px-5 md:py-3 text-xs md:text-sm font-semibold text-white transition-colors hover:bg-white/15 border border-white/10 self-start"
            >
              Cambiar rutina
            </button>
          </div>
        </div>

        <div class="grid gap-4 md:grid-cols-3 mb-6">
          <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm">
            <p class="text-sm text-gray-500 dark:text-gray-400">Series totales</p>
            <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ seriesTotales }}</p>
          </div>
          <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm">
            <p class="text-sm text-gray-500 dark:text-gray-400">Series completadas</p>
            <p class="mt-2 text-3xl font-bold text-emerald-600 dark:text-emerald-400">{{ seriesCompletadas }}</p>
          </div>
          <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm">
            <p class="text-sm text-gray-500 dark:text-gray-400">Peso registrado</p>
            <p class="mt-2 text-3xl font-bold text-indigo-600 dark:text-indigo-400">{{ pesoRegistrado }} kg</p>
          </div>
          <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm md:col-span-3">
            <p class="text-sm text-gray-500 dark:text-gray-400">Repeticiones registradas</p>
            <p class="mt-2 text-3xl font-bold text-orange-600 dark:text-orange-400">{{ repsRegistradas }}</p>
          </div>
        </div>

        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm mb-6">
          <div class="flex items-center justify-between mb-3">
            <div>
              <p class="text-sm text-gray-500 dark:text-gray-400">Progreso del día</p>
              <p class="text-lg font-semibold text-gray-900 dark:text-white">
                {{ seriesCompletadas }} de {{ seriesTotales }} series
              </p>
            </div>
            <span class="text-sm font-medium text-indigo-600 dark:text-indigo-400">
              {{ progresoDia }}%
            </span>
          </div>
          <div class="h-3 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden">
            <div
              class="h-full rounded-full bg-gradient-to-r from-indigo-500 via-violet-500 to-cyan-500 transition-all duration-300"
              :style="{ width: `${progresoDia}%` }"
            ></div>
          </div>
          <div class="mt-3 flex flex-wrap gap-3 text-sm text-gray-500 dark:text-gray-400">
            <span>Promedio de peso: {{ pesoPromedio }} kg</span>
            <span>•</span>
            <span>Series pendientes: {{ seriesPendientes }}</span>
          </div>
        </div>

        <div class="mb-6">
          <div class="flex flex-wrap gap-2 mb-4">
            <button
              v-for="dia in todosLosDias"
              :key="dia"
              @click="cambiarDia(dia)"
              :class="[
                'px-4 py-2 rounded-lg font-medium transition-all',
                diaActual === dia
                  ? 'bg-indigo-600 text-white shadow-md'
                  : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700'
              ]"
            >
              {{ dia }}
            </button>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden border border-gray-200 dark:border-gray-700">
          <div class="p-4 md:hidden space-y-4">
            <div
              v-for="fila in filasSerie"
              :key="`${fila.uid}-mobile`"
              class="rounded-2xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm transition-all"
              :class="getSuperserieBgClass(fila.superserie_grupo, fila.completado)"
            >
              <div class="flex items-start justify-between gap-3 mb-3">
                <div>
                  <h3 class="text-lg font-extrabold text-gray-900 dark:text-white leading-tight">
                    {{ fila.ejercicio_nombre }}
                    <span v-if="fila.superserie_grupo" class="ml-2 inline-flex items-center rounded-md bg-indigo-100 dark:bg-indigo-950/60 dark:text-indigo-300 px-2 py-0.5 text-xs font-semibold ring-1 ring-inset ring-indigo-500/30">
                      Superserie {{ fila.superserie_grupo }}
                    </span>
                  </h3>
                  <p class="mt-1 text-xs uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Serie {{ fila.series_numero }}</p>
                </div>
                <span class="inline-flex items-center rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300 px-2.5 py-1 text-[11px] font-semibold whitespace-nowrap">
                  {{ fila.reps_min }} - {{ fila.reps_max }} reps
                </span>
              </div>

              <div class="grid grid-cols-2 gap-3 text-sm mb-4">
                <label class="block">
                  <span class="mb-1 block text-gray-500 dark:text-gray-400">Reps hechas</span>
                  <input
                    v-model.number="fila.reps_realizadas"
                    @change="guardarFila(fila)"
                    type="number"
                    min="0"
                    step="1"
                    placeholder="0"
                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-950 px-3 py-2 text-center text-gray-900 dark:text-white focus:border-indigo-500 focus:ring-indigo-500"
                  />
                </label>

                <label class="block">
                  <span class="mb-1 block text-gray-500 dark:text-gray-400">Peso</span>
                  <input
                    v-model.number="fila.peso"
                    @change="guardarFila(fila)"
                    type="number"
                    min="0"
                    step="0.5"
                    placeholder="Kg"
                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-950 px-3 py-2 text-center text-gray-900 dark:text-white focus:border-indigo-500 focus:ring-indigo-500"
                  />
                </label>
              </div>

              <div class="flex items-center justify-between gap-3">
                <div class="text-sm text-orange-600 dark:text-orange-400 font-medium">
                  Descanso: {{ fila.descanso_min }} min
                </div>
                <label class="inline-flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                  <input
                    v-model="fila.completado"
                    @change="guardarFila(fila)"
                    type="checkbox"
                    class="w-5 h-5 rounded cursor-pointer text-indigo-600 focus:ring-indigo-500"
                  />
                  Hecho
                </label>
              </div>
            </div>

            <div v-if="!filasSerie.length" class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 p-6 text-center text-gray-500 dark:text-gray-400">
              No hay ejercicios para este día.
            </div>
          </div>

          <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-800 border-b-2 border-gray-200 dark:border-gray-700">
                  <th class="px-4 py-4 text-left font-bold text-gray-700 dark:text-gray-300">Ejercicio</th>
                  <th class="px-4 py-4 text-center font-bold text-gray-700 dark:text-gray-300">Serie</th>
                  <th class="px-4 py-4 text-center font-bold text-gray-700 dark:text-gray-300">Reps</th>
                  <th class="px-4 py-4 text-center font-bold text-gray-700 dark:text-gray-300">Reps hechas</th>
                  <th class="px-4 py-4 text-center font-bold text-gray-700 dark:text-gray-300">Peso</th>
                  <th class="px-4 py-4 text-center font-bold text-gray-700 dark:text-gray-300">Descanso</th>
                  <th class="px-4 py-4 text-center font-bold text-gray-700 dark:text-gray-300">Completado</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                <template v-if="filasSerie.length">
                  <tr
                    v-for="fila in filasSerie"
                    :key="fila.uid"
                    class="transition-all"
                    :class="getSuperserieRowBgClass(fila)"
                  >
                    <td class="px-4 py-4 font-medium text-gray-900 dark:text-white">
                      {{ fila.ejercicio_nombre }}
                      <span v-if="fila.superserie_grupo" class="ml-2 inline-flex items-center rounded-md bg-indigo-50 dark:bg-indigo-950/40 dark:text-indigo-400 px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset ring-indigo-500/20">
                        Superserie {{ fila.superserie_grupo }}
                      </span>
                    </td>
                    <td class="px-4 py-4 text-center">
                      <span class="px-2 py-1 bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300 rounded font-semibold">{{ fila.series_numero }}</span>
                    </td>
                    <td class="px-4 py-4 text-center text-gray-700 dark:text-gray-300">{{ fila.reps_min }} - {{ fila.reps_max }}</td>
                    <td class="px-4 py-4 text-center">
                      <div class="max-w-[110px] mx-auto">
                        <input
                          v-model.number="fila.reps_realizadas"
                          @change="guardarFila(fila)"
                          type="number"
                          min="0"
                          step="1"
                          placeholder="0"
                          class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2 text-center text-gray-900 dark:text-white focus:border-indigo-500 focus:ring-indigo-500"
                        />
                      </div>
                    </td>
                    <td class="px-4 py-4 text-center">
                      <div class="max-w-[120px] mx-auto">
                        <input
                          v-model.number="fila.peso"
                          @change="guardarFila(fila)"
                          type="number"
                          min="0"
                          step="0.5"
                          placeholder="Kg"
                          class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2 text-center text-gray-900 dark:text-white focus:border-indigo-500 focus:ring-indigo-500"
                        />
                      </div>
                    </td>
                    <td class="px-4 py-4 text-center">
                      <span class="text-orange-600 dark:text-orange-400 font-medium">{{ fila.descanso_min }} min</span>
                    </td>
                    <td class="px-4 py-4 text-center">
                      <input
                        v-model="fila.completado"
                        @change="guardarFila(fila)"
                        type="checkbox"
                        class="w-6 h-6 rounded cursor-pointer text-indigo-600 focus:ring-indigo-500"
                      />
                    </td>
                  </tr>
                </template>
              </tbody>
            </table>
          </div>

          <div class="p-5 bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-700 border-t border-gray-200 dark:border-gray-700">
            <div class="flex flex-wrap justify-center gap-4">
              <button
                v-if="diaIndex > 0"
                @click="diaAnterior"
                class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold transition-all shadow-md hover:shadow-lg"
              >
                ← Día Anterior
              </button>
              <button
                @click="guardarSesion"
                class="hidden md:inline-flex bg-slate-700 hover:bg-slate-800 text-white px-6 py-3 rounded-lg font-semibold transition-all shadow-md hover:shadow-lg"
              >
                Guardar sesión
              </button>
              <button
                @click="siguienteDia"
                :class="['text-white px-8 py-3 rounded-lg font-semibold transition-all shadow-md hover:shadow-lg', botonSiguienteClass]"
              >
                {{ textoBotonSiguiente }}
              </button>
            </div>
          </div>
        </div>

        <div class="fixed inset-x-0 bottom-0 z-40 border-t border-gray-200/80 dark:border-gray-700 bg-gray-950/95 backdrop-blur md:hidden pb-[env(safe-area-inset-bottom)]">
          <div class="max-w-6xl mx-auto px-4 py-3">
            <button
              @click="guardarSesion"
              class="w-full rounded-xl bg-slate-700 hover:bg-slate-800 text-white px-4 py-3 text-sm font-semibold shadow-lg shadow-slate-950/30"
            >
              Guardar sesión
            </button>
          </div>
        </div>
      </div>

      <div v-else class="text-center py-16">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8 max-w-md mx-auto border border-gray-200 dark:border-gray-700">
          <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
          </svg>
          <p class="text-gray-500 dark:text-gray-400 mb-6 text-lg">No hay rutina seleccionada</p>
          <a
            href="/rutinas"
            class="inline-block bg-indigo-600 hover:bg-indigo-700 text-white px-8 py-3 rounded-lg font-semibold transition-all shadow-md hover:shadow-lg"
          >
            Seleccionar Rutina
          </a>
        </div>
      </div>
    </div>

    <!-- Floating Rest Timer Panel -->
    <transition name="slide-fade">
      <div
        v-if="timer.activo"
        class="fixed bottom-20 md:bottom-6 right-6 z-50 w-80 bg-slate-900 text-white rounded-2xl shadow-2xl border border-slate-800 p-4 transform transition-all duration-300"
      >
        <div class="flex items-center justify-between mb-3">
          <div>
            <p class="text-[10px] uppercase tracking-[0.2em] text-indigo-400 font-semibold">Descanso Activo</p>
            <h4 class="text-xs font-bold truncate max-w-[180px]">{{ timer.ejercicioNombre }}</h4>
          </div>
          <button @click="saltarTemporizador" class="text-slate-400 hover:text-white transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div class="flex items-center justify-between gap-4">
          <div class="flex items-center gap-3">
            <div class="relative flex items-center justify-center w-14 h-14">
              <svg class="absolute w-full h-full transform -rotate-90">
                <circle
                  cx="28"
                  cy="28"
                  r="24"
                  stroke="#1e293b"
                  stroke-width="3.5"
                  fill="transparent"
                />
                <circle
                  cx="28"
                  cy="28"
                  r="24"
                  stroke="#6366f1"
                  stroke-width="3.5"
                  fill="transparent"
                  :stroke-dasharray="150.796"
                  :stroke-dashoffset="dashOffset"
                  stroke-linecap="round"
                  class="transition-all duration-300"
                />
              </svg>
              <span class="text-xs font-mono font-bold">{{ formattedRemainingTime }}</span>
            </div>
          </div>

          <div class="flex items-center gap-2 flex-1 justify-end">
            <button
              @click="pausarReanudarTemporizador"
              class="p-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-white transition-colors"
              :title="timer.pausado ? 'Reanudar' : 'Pausar'"
            >
              <svg v-if="timer.pausado" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd" />
              </svg>
              <svg v-else class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM7 8a1 1 0 012 0v4a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v4a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
              </svg>
            </button>
            <button
              @click="agregarTiempoTemporizador"
              class="px-2.5 py-2 text-xs font-semibold rounded-xl bg-slate-800 hover:bg-slate-700 text-white transition-colors"
              title="Añadir 30s"
            >
              <span>+30s</span>
            </button>
            <button
              @click="saltarTemporizador"
              class="px-2.5 py-2 text-xs font-semibold rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white transition-colors"
              title="Saltar"
            >
              <span>Saltar</span>
            </button>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { useRutinaStore } from '../stores/rutina';
import axios from 'axios';
import ToastNotification from './ToastNotification.vue';
import confetti from 'canvas-confetti';

const rutinaStore = useRutinaStore();
const toastRef = ref(null);
const filasSerie = ref([]);
const historialRutina = ref([]);
const diaActual = ref('Día 1');
const todosLosDias = ref([]);

const diaIndex = computed(() => {
  return todosLosDias.value.indexOf(diaActual.value);
});

const esUltimoDia = computed(() => {
  return diaIndex.value === todosLosDias.value.length - 1;
});

const seriesTotales = computed(() => filasSerie.value.length);

const seriesCompletadas = computed(() => filasSerie.value.filter((fila) => fila.completado).length);

const pesoRegistrado = computed(() => {
  return filasSerie.value.reduce((total, fila) => {
    const peso = Number(fila.peso);
    return Number.isFinite(peso) ? total + peso : total;
  }, 0).toFixed(1);
});

const repsRegistradas = computed(() => {
  return filasSerie.value.reduce((total, fila) => {
    const reps = Number(fila.reps_realizadas);
    return Number.isFinite(reps) ? total + reps : total;
  }, 0);
});

const seriesPendientes = computed(() => Math.max(seriesTotales.value - seriesCompletadas.value, 0));

const progresoDia = computed(() => {
  if (!seriesTotales.value) {
    return 0;
  }

  return Math.round((seriesCompletadas.value / seriesTotales.value) * 100);
});

const pesoPromedio = computed(() => {
  const pesosValidos = filasSerie.value
    .map((fila) => Number(fila.peso))
    .filter((peso) => Number.isFinite(peso) && peso > 0);

  if (!pesosValidos.length) {
    return '0.0';
  }

  const promedio = pesosValidos.reduce((total, peso) => total + peso, 0) / pesosValidos.length;
  return promedio.toFixed(1);
});

const getRutinaNombre = () => rutinaStore.seleccionada?.nivel || '';

// Toast helpers
const showToast = (message, type = 'info', duration = 3000) => {
  toastRef.value?.addToast(message, type, duration);
};

const showSuccess = (message) => showToast(message, 'success');
const showError = (message) => showToast(message, 'error');
const showWarning = (message) => showToast(message, 'warning');

// Texto dinámico del botón siguiente
const textoBotonSiguiente = computed(() => {
  if (esUltimoDia.value) {
    return seriesCompletadas.value === seriesTotales.value 
      ? '🎉 Finalizar Rutina' 
      : '⚠️ Terminar e Iniciar';
  }
  if (seriesPendientes.value > 0) {
    return `⚠️ Siguiente Día →`;
  }
  return 'Siguiente Día →';
});

const botonSiguienteClass = computed(() => {
  if (esUltimoDia.value && seriesPendientes.value > 0) {
    return 'bg-orange-500 hover:bg-orange-600';
  }
  if (!esUltimoDia.value && seriesPendientes.value > 0) {
    return 'bg-yellow-500 hover:bg-yellow-600';
  }
  return 'bg-green-600 hover:bg-green-700';
});

// Confetti animation
const triggerConfetti = () => {
  const duration = 3000;
  const end = Date.now() + duration;
  
  const colors = ['#10b981', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6'];
  
  (function frame() {
    confetti({
      particleCount: 5,
      angle: 60,
      spread: 55,
      origin: { x: 0 },
      colors: colors,
    });
    confetti({
      particleCount: 5,
      angle: 120,
      spread: 55,
      origin: { x: 1 },
      colors: colors,
    });

    if (Date.now() < end) {
      requestAnimationFrame(frame);
    }
  })();
};

const fetchUserRutina = async () => {
  try {
    const response = await axios.get('/api/user-rutina');
    if (response.data) {
      const nivelCompleto = `${response.data.nivel} ${response.data.modalidad}`;
      rutinaStore.seleccionar(nivelCompleto, 'Todos los días');
      diaActual.value = response.data.dia_actual || 'Día 1';
    } else {
      rutinaStore.limpiar();
    }
  } catch (error) {
    console.error('Error:', error);
  }
};

const fetchHistorialRutina = async () => {
  if (!rutinaStore.seleccionada) {
    historialRutina.value = [];
    return;
  }

  try {
    const response = await axios.get('/api/historial', {
      params: { rutina_nombre: getRutinaNombre() }
    });

    historialRutina.value = Array.isArray(response.data) ? response.data : [];
  } catch (error) {
    console.error('Error:', error);
    historialRutina.value = [];
  }
};

const construirFilasSerie = (rutinasDelDia) => {
  const registros = new Map(
    historialRutina.value
      .filter((registro) => registro.dia === diaActual.value)
      .map((registro) => [`${registro.ejercicio_nombre}-${registro.series_numero}`, registro])
  );

  const filteredRutinas = rutinasDelDia.filter((rutina) => rutina.dia === diaActual.value);

  const blocks = [];
  const processedSuperseries = new Set();

  filteredRutinas.forEach((rutina) => {
    if (rutina.superserie_grupo) {
      if (!processedSuperseries.has(rutina.superserie_grupo)) {
        processedSuperseries.add(rutina.superserie_grupo);
        const supersetExercises = filteredRutinas.filter(
          (r) => r.superserie_grupo === rutina.superserie_grupo
        );
        blocks.push({
          isSuperset: true,
          grupo: rutina.superserie_grupo,
          exercises: supersetExercises,
        });
      }
    } else {
      blocks.push({
        isSuperset: false,
        exercise: rutina,
      });
    }
  });

  const allSets = [];

  blocks.forEach((block) => {
    if (!block.isSuperset) {
      const rutina = block.exercise;
      const totalSeries = Number(rutina.series) || 1;
      for (let index = 0; index < totalSeries; index++) {
        const serieNumero = index + 1;
        const registro = registros.get(`${rutina.ejercicio_nombre}-${serieNumero}`);
        allSets.push({
          uid: `${rutina.id}-${diaActual.value}-${serieNumero}`,
          rutina_nombre: getRutinaNombre(),
          dia: diaActual.value,
          ejercicio_nombre: rutina.ejercicio_nombre,
          series_numero: serieNumero,
          series_completadas: registro?.series_completadas ?? (registro?.completado ? 1 : 0),
          reps_min: rutina.reps_min,
          reps_max: rutina.reps_max,
          reps_realizadas: registro?.reps_realizadas ?? null,
          descanso_min: rutina.descanso_min,
          peso: registro?.peso ?? null,
          completado: registro?.completado ?? false,
          superserie_grupo: null,
        });
      }
    } else {
      const exercises = block.exercises;
      const maxSeries = Math.max(...exercises.map((r) => Number(r.series) || 1));

      for (let index = 0; index < maxSeries; index++) {
        const serieNumero = index + 1;
        exercises.forEach((rutina) => {
          const totalSeries = Number(rutina.series) || 1;
          if (serieNumero <= totalSeries) {
            const registro = registros.get(`${rutina.ejercicio_nombre}-${serieNumero}`);
            allSets.push({
              uid: `${rutina.id}-${diaActual.value}-${serieNumero}`,
              rutina_nombre: getRutinaNombre(),
              dia: diaActual.value,
              ejercicio_nombre: rutina.ejercicio_nombre,
              series_numero: serieNumero,
              series_completadas: registro?.series_completadas ?? (registro?.completado ? 1 : 0),
              reps_min: rutina.reps_min,
              reps_max: rutina.reps_max,
              reps_realizadas: registro?.reps_realizadas ?? null,
              descanso_min: rutina.descanso_min,
              peso: registro?.peso ?? null,
              completado: registro?.completado ?? false,
              superserie_grupo: block.grupo,
            });
          }
        });
      }
    }
  });

  filasSerie.value = allSets;
};

const fetchRutinasDelDia = async () => {
  if (!rutinaStore.seleccionada) return;

  try {
    const nivel = rutinaStore.seleccionada.nivel.split(' ')[0];
    const modalidad = rutinaStore.seleccionada.nivel.substring(nivel.length + 1);

    const response = await axios.get('/api/rutinas', {
      params: { nivel, modalidad }
    });

    const diasUnicos = [...new Set(response.data.map(r => r.dia))].sort();
    todosLosDias.value = diasUnicos;

    await fetchHistorialRutina();
    construirFilasSerie(response.data);
  } catch (error) {
    console.error('Error:', error);
  }
};

const guardarFila = async (fila, silencioso = false) => {
  try {
    await axios.post('/api/historial/guardar', {
      rutina_nombre: fila.rutina_nombre,
      dia: fila.dia,
      ejercicio_nombre: fila.ejercicio_nombre,
      series_numero: fila.series_numero,
      series_completadas: fila.completado ? 1 : 0,
      reps_min: fila.reps_min,
      reps_max: fila.reps_max,
      reps_realizadas: fila.reps_realizadas === '' || fila.reps_realizadas == null ? null : Number(fila.reps_realizadas),
      descanso_min: fila.descanso_min,
      peso: fila.peso === '' || fila.peso == null ? null : Number(fila.peso),
      completado: fila.completado,
      superserie_grupo: fila.superserie_grupo,
    });

    if (!silencioso && fila.completado && deberiaIniciarTemporizador(fila)) {
      iniciarTemporizador(fila);
    }
  } catch (error) {
    if (!silencioso) {
      console.error('Error:', error);
      showError('No se pudo guardar la serie. Intenta de nuevo.');
    }

    throw error;
  }
};

const guardarProgreso = async () => {
  try {
    await axios.post('/api/user-rutina', {
      nivel: rutinaStore.seleccionada.nivel.split(' ')[0],
      modalidad: rutinaStore.seleccionada.nivel.substring(rutinaStore.seleccionada.nivel.split(' ')[0].length + 1),
      dia_actual: diaActual.value,
    });
  } catch (error) {
    console.error('Error:', error);
  }
};

const cambiarDia = async (dia) => {
  diaActual.value = dia;
  await guardarProgreso();
  await fetchRutinasDelDia();
};

const siguienteDia = async () => {
  // Verificar si hay series incompletas
  if (seriesPendientes.value > 0) {
    const confirmar = confirm(
      `Tienes ${seriesPendientes.value} series sin completar. ¿Quieres avanzar de todas formas?`
    );
    if (!confirmar) return;
  }

  if (diaIndex.value < todosLosDias.value.length - 1) {
    diaActual.value = todosLosDias.value[diaIndex.value + 1];
    await guardarProgreso();
    fetchRutinasDelDia();
  } else {
    await finalizarRutina();
  }
};

const diaAnterior = async () => {
  if (diaIndex.value > 0) {
    diaActual.value = todosLosDias.value[diaIndex.value - 1];
    await guardarProgreso();
    fetchRutinasDelDia();
  }
};

const cambiarRutina = () => {
  if (confirm('¿Estás seguro de cambiar de rutina?')) {
    rutinaStore.limpiar();
    filasSerie.value = [];
    historialRutina.value = [];
    diaActual.value = 'Día 1';
    window.location.href = '/rutinas';
  }
};

const guardarSesion = async () => {
  if (!filasSerie.value.length) {
    showWarning('No hay ejercicios para guardar.');
    return;
  }

  try {
    await Promise.all(filasSerie.value.map((fila) => guardarFila(fila, true)));
    showSuccess('✓ Sesión guardada correctamente');
  } catch (error) {
    console.error('Error:', error);
    showError('No se pudo guardar la sesión. Intenta de nuevo.');
  }
};

const finalizarRutina = async () => {
  if (!rutinaStore.seleccionada) return;

  try {
    await Promise.all(filasSerie.value.map((fila) => guardarFila(fila, true)));

    const nivel = rutinaStore.seleccionada.nivel.split(' ')[0];
    const modalidad = rutinaStore.seleccionada.nivel.substring(nivel.length + 1);

    const response = await axios.post('/api/historial/finalizar-rutina', {
      nivel,
      modalidad,
    });

    diaActual.value = response.data.dia_actual || 'Día 1';
    await guardarProgreso();
    await fetchRutinasDelDia();
    
    // Mostrar confetti y toast de éxito
    triggerConfetti();
    showSuccess('🎉 ¡Felicidades! Has completado la rutina. Se reinició al Día 1.');
  } catch (error) {
    console.error('Error:', error);
    showError('No se pudo finalizar la rutina. Intenta de nuevo.');
  }
};

onMounted(async () => {
  await fetchUserRutina();
  if (rutinaStore.seleccionada) {
    await fetchHistorialRutina();
    fetchRutinasDelDia();
  }
});

watch(() => rutinaStore.seleccionada, (newVal) => {
  if (newVal) {
    fetchHistorialRutina();
    fetchRutinasDelDia();
  }
});

// Lógica del Temporizador de Descanso
const timer = ref({
  activo: false,
  totalSegundos: 0,
  segundosRestantes: 0,
  ejercicioNombre: '',
  timerId: null,
  pausado: false,
});

const formattedRemainingTime = computed(() => {
  const mins = Math.floor(timer.value.segundosRestantes / 60);
  const secs = timer.value.segundosRestantes % 60;
  return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
});

const dashOffset = computed(() => {
  if (timer.value.totalSegundos === 0) return 0;
  const circumference = 150.796;
  const ratio = timer.value.segundosRestantes / timer.value.totalSegundos;
  return circumference - (ratio * circumference);
});

const deberiaIniciarTemporizador = (fila) => {
  if (!fila.superserie_grupo) {
    return true;
  }
  const setsEnRonda = filasSerie.value.filter(
    (f) => f.superserie_grupo === fila.superserie_grupo && f.series_numero === fila.series_numero
  );
  return setsEnRonda.every((f) => f.completado);
};

const getSuperserieBgClass = (grupo, completado) => {
  if (completado) {
    return 'ring-2 ring-green-500/30 bg-green-50 dark:bg-green-900/20';
  }
  if (!grupo) return 'bg-gray-50 dark:bg-gray-900';
  switch (grupo) {
    case 1: return 'border-l-4 border-indigo-500 bg-indigo-50/20 dark:bg-indigo-950/20';
    case 2: return 'border-l-4 border-emerald-500 bg-emerald-50/20 dark:bg-emerald-950/20';
    case 3: return 'border-l-4 border-pink-500 bg-pink-50/20 dark:bg-pink-950/20';
    case 4: return 'border-l-4 border-amber-500 bg-amber-50/20 dark:bg-amber-950/20';
    default: return 'border-l-4 border-gray-500 bg-gray-50/20 dark:bg-gray-950/20';
  }
};

const getSuperserieRowBgClass = (fila) => {
  if (fila.completado) {
    return 'bg-green-50 dark:bg-green-900/20';
  }
  const grupo = fila.superserie_grupo;
  if (!grupo) return 'bg-white dark:bg-gray-800';
  switch (grupo) {
    case 1: return 'border-l-2 border-indigo-500 bg-indigo-50/10 dark:bg-indigo-950/20';
    case 2: return 'border-l-2 border-emerald-500 bg-emerald-50/10 dark:bg-emerald-950/20';
    case 3: return 'border-l-2 border-pink-500 bg-pink-50/10 dark:bg-pink-950/20';
    case 4: return 'border-l-2 border-amber-500 bg-amber-50/10 dark:bg-indigo-950/20';
    default: return 'border-l-2 border-gray-500 bg-gray-50/10 dark:bg-gray-950/20';
  }
};

const iniciarTemporizador = (fila) => {
  if (timer.value.timerId) {
    clearInterval(timer.value.timerId);
  }

  const descansoMinutos = parseFloat(fila.descanso_min) || 1.5;
  const totalSegundos = Math.round(descansoMinutos * 60);

  const nombreLabel = fila.superserie_grupo 
    ? `Descanso Superserie ${fila.superserie_grupo}` 
    : fila.ejercicio_nombre;

  timer.value = {
    activo: true,
    totalSegundos,
    segundosRestantes: totalSegundos,
    ejercicioNombre: nombreLabel,
    pausado: false,
    timerId: null,
  };

  runTimer();
};

const runTimer = () => {
  timer.value.timerId = setInterval(() => {
    if (!timer.value.pausado) {
      if (timer.value.segundosRestantes > 0) {
        timer.value.segundosRestantes--;
      } else {
        finalizarTemporizador();
      }
    }
  }, 1000);
};

const pausarReanudarTemporizador = () => {
  timer.value.pausado = !timer.value.pausado;
};

const agregarTiempoTemporizador = () => {
  timer.value.segundosRestantes += 30;
  timer.value.totalSegundos += 30;
};

const saltarTemporizador = () => {
  if (timer.value.timerId) {
    clearInterval(timer.value.timerId);
  }
  timer.value.activo = false;
};

const finalizarTemporizador = () => {
  if (timer.value.timerId) {
    clearInterval(timer.value.timerId);
  }
  timer.value.activo = false;
  reproducirBeep();
};

const reproducirBeep = () => {
  try {
    const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
    
    const playBeep = (time, frequency, duration) => {
      const osc = audioCtx.createOscillator();
      const gainNode = audioCtx.createGain();
      
      osc.type = 'sine';
      osc.frequency.value = frequency;
      
      gainNode.gain.setValueAtTime(0, time);
      gainNode.gain.linearRampToValueAtTime(0.3, time + 0.05);
      gainNode.gain.exponentialRampToValueAtTime(0.0001, time + duration);
      
      osc.connect(gainNode);
      gainNode.connect(audioCtx.destination);
      
      osc.start(time);
      osc.stop(time + duration);
    };

    const now = audioCtx.currentTime;
    playBeep(now, 880, 0.4);
    playBeep(now + 0.5, 880, 0.4);
  } catch (e) {
    console.error('AudioContext no soportado o bloqueado:', e);
  }
};

onUnmounted(() => {
  if (timer.value.timerId) {
    clearInterval(timer.value.timerId);
  }
});
</script>

<style scoped>
.slide-fade-enter-active {
  transition: all 0.3s ease-out;
}
.slide-fade-leave-active {
  transition: all 0.3s cubic-bezier(1, 0.5, 0.8, 1);
}
.slide-fade-enter-from,
.slide-fade-leave-to {
  transform: translateY(20px) scale(0.95);
  opacity: 0;
}
</style>