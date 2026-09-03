<template>
  <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden border border-gray-200 dark:border-gray-700">
    <!-- Header resumen y control de acordeones -->
    <div class="px-5 py-4 bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-750 border-b border-gray-200 dark:border-gray-700 flex flex-wrap items-center justify-between gap-3">
      <div class="flex items-center gap-3">
        <span class="text-sm font-bold text-gray-800 dark:text-white">
          {{ ejerciciosAgrupados.length }} {{ ejerciciosAgrupados.length === 1 ? 'ejercicio' : 'ejercicios' }}
        </span>
        <span class="text-xs text-gray-500 dark:text-gray-400">·</span>
        <span
          class="text-xs font-semibold"
          :class="totalCompletadas === totalSeries && totalSeries > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-indigo-600 dark:text-indigo-400'"
        >
          {{ totalCompletadas }}/{{ totalSeries }} series completadas
        </span>
        <!-- Mini barra de progreso -->
        <div class="w-20 sm:w-28 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden hidden sm:block">
          <div
            class="h-full bg-gradient-to-r from-indigo-500 to-emerald-500 transition-all duration-300"
            :style="{ width: `${porcentajeCompletado}%` }"
          ></div>
        </div>
      </div>

      <button
        type="button"
        @click="toggleTodos"
        class="text-xs font-semibold px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 shadow-sm transition-all cursor-pointer"
      >
        {{ todosAbiertos ? 'Colapsar todos' : 'Expandir todos' }}
      </button>
    </div>

    <!-- Lista de ejercicios en acordeón -->
    <div class="p-2 sm:p-4 space-y-3 sm:space-y-4">
      <div
        v-for="ejercicio in ejerciciosAgrupados"
        :key="ejercicio.nombre"
        class="rounded-2xl border overflow-hidden shadow-sm transition-all"
        :class="getEjercicioCardClass(ejercicio)"
      >
        <!-- Header del Acordeón por Ejercicio -->
        <button
          type="button"
          @click="toggleEjercicio(ejercicio.nombre)"
          class="w-full px-5 py-4 text-left flex items-center justify-between gap-3 transition-colors select-none cursor-pointer"
          :class="getEjercicioHeaderBg(ejercicio)"
          :aria-expanded="estaAbierto(ejercicio.nombre)"
        >
          <div class="flex items-center gap-3 min-w-0">
            <!-- Icono chevron desplegable -->
            <svg
              :class="{ 'rotate-180': estaAbierto(ejercicio.nombre) }"
              class="w-5 h-5 text-gray-500 dark:text-gray-400 transition-transform duration-200 shrink-0"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>

            <div class="min-w-0">
              <div class="flex items-center flex-wrap gap-2">
                <h3 class="text-base sm:text-lg font-bold text-gray-900 dark:text-white truncate">
                  {{ ejercicio.nombre }}
                </h3>
                <span
                  v-if="ejercicio.superserie_grupo"
                  :class="getSuperserieBadgeClass(ejercicio.superserie_grupo)"
                  class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-semibold ring-1 ring-inset"
                >
                  Superserie {{ ejercicio.superserie_grupo }}
                </span>
              </div>

              <!-- Nota técnica si existe (RIR, Rest-pause, etc.) -->
              <div v-if="ejercicio.notas" class="flex items-center gap-1.5 mt-1 text-xs text-indigo-700 dark:text-indigo-300">
                <span>💡</span>
                <span class="italic font-medium truncate">{{ ejercicio.notas }}</span>
              </div>
            </div>
          </div>

          <!-- Badges a la derecha -->
          <div class="flex items-center gap-2 shrink-0">
            <span class="hidden sm:inline-flex items-center gap-1 text-xs text-orange-600 dark:text-orange-400 font-medium px-2 py-1 rounded bg-orange-50 dark:bg-orange-950/40">
              ⏱ {{ ejercicio.descanso_min }} min
            </span>

            <span
              :class="[
                'text-xs font-bold px-2.5 py-1 rounded-full transition-colors whitespace-nowrap',
                ejercicioCompleto(ejercicio)
                  ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 ring-1 ring-emerald-600/20'
                  : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'
              ]"
            >
              {{ ejercicioCompleto(ejercicio) ? '✓ ' : '' }}{{ completadasPorEjercicio(ejercicio) }}/{{ ejercicio.series.length }} series
            </span>
          </div>
        </button>

        <!-- Contenido desplegable (Series del Ejercicio) -->
        <div v-if="estaAbierto(ejercicio.nombre)" class="border-t border-gray-100 dark:border-gray-700/60">
          <!-- Desktop: Tabla de series dentro del acordeón -->
          <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="bg-gray-50/70 dark:bg-gray-900/30 border-b border-gray-100 dark:border-gray-700 text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400">
                  <th class="px-4 py-2.5 text-center font-bold w-16">Serie</th>
                  <th class="px-4 py-2.5 text-center font-bold w-28">Reps Obj.</th>
                  <th class="px-4 py-2.5 text-center font-bold w-32">Reps hechas</th>
                  <th class="px-4 py-2.5 text-center font-bold w-32">Peso (kg)</th>
                  <th class="px-4 py-2.5 text-center font-bold w-24">Descanso</th>
                  <th class="px-4 py-2.5 text-center font-bold">Esfuerzo</th>
                  <th class="px-4 py-2.5 text-center font-bold w-24">Listo</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                <tr
                  v-for="fila in ejercicio.series"
                  :key="fila.uid"
                  class="transition-colors"
                  :class="fila.completado ? 'bg-emerald-50/40 dark:bg-emerald-950/20' : 'bg-white dark:bg-gray-800 hover:bg-gray-50/60 dark:hover:bg-gray-750'"
                >
                  <td class="px-4 py-3 text-center">
                    <span
                      :class="[
                        'inline-flex items-center justify-center w-7 h-7 rounded-full text-xs font-bold transition-all',
                        fila.completado
                          ? 'bg-emerald-500 text-white shadow-sm'
                          : 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300'
                      ]"
                    >
                      {{ fila.series_numero }}
                    </span>
                  </td>
                  <td class="px-4 py-3 text-center font-medium text-gray-700 dark:text-gray-300">
                    {{ fila.reps_min }} - {{ fila.reps_max }}
                  </td>
                  <td class="px-4 py-3 text-center">
                    <div class="max-w-[100px] mx-auto">
                      <input
                        v-model.number="fila.reps_realizadas"
                        @change="$emit('guardar', fila)"
                        type="number"
                        min="0"
                        step="1"
                        placeholder="0"
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-1.5 text-center text-gray-900 dark:text-white font-semibold focus:border-indigo-500 focus:ring-indigo-500"
                      />
                    </div>
                  </td>
                  <td class="px-4 py-3 text-center">
                    <div class="max-w-[110px] mx-auto">
                      <input
                        v-model.number="fila.peso"
                        @change="$emit('guardar', fila)"
                        type="number"
                        min="0"
                        step="0.5"
                        placeholder="Kg"
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-1.5 text-center text-gray-900 dark:text-white font-semibold focus:border-indigo-500 focus:ring-indigo-500"
                      />
                    </div>
                  </td>
                  <td class="px-4 py-3 text-center">
                    <span class="text-orange-600 dark:text-orange-400 font-medium text-xs">
                      {{ fila.descanso_min }} min
                    </span>
                  </td>
                  <td class="px-4 py-3 text-center">
                    <div class="inline-flex flex-col items-center gap-1">
                      <div class="inline-flex rounded-md border border-gray-200 dark:border-gray-700 overflow-hidden text-[10px]">
                        <button
                          type="button"
                          :class="[
                            'px-1.5 py-0.5 transition-colors cursor-pointer',
                            fila.esfuerzo_tipo === 'rir' ? 'bg-emerald-500 text-white' : 'bg-white dark:bg-gray-800 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700'
                          ]"
                          @click="toggleEsfuerzoTipo(fila, 'rir')"
                        >RIR</button>
                        <button
                          type="button"
                          :class="[
                            'px-1.5 py-0.5 transition-colors cursor-pointer',
                            fila.esfuerzo_tipo === 'rpe' ? 'bg-amber-500 text-white' : 'bg-white dark:bg-gray-800 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700'
                          ]"
                          @click="toggleEsfuerzoTipo(fila, 'rpe')"
                        >RPE</button>
                      </div>
                      <div class="flex gap-0.5">
                        <button
                          v-for="opt in esfuerzoOptions(fila.esfuerzo_tipo)"
                          :key="opt"
                          type="button"
                          :class="[
                            'min-w-[22px] rounded text-[10px] font-bold transition-colors cursor-pointer',
                            fila.esfuerzo_valor === opt
                              ? (fila.esfuerzo_tipo === 'rir' ? 'bg-emerald-500 text-white' : 'bg-amber-500 text-white')
                              : 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
                          ]"
                          @click="setEsfuerzoValor(fila, opt)"
                        >{{ opt }}</button>
                      </div>
                    </div>
                  </td>
                  <td class="px-4 py-3 text-center">
                    <input
                      v-model="fila.completado"
                      @change="$emit('guardar', fila)"
                      type="checkbox"
                      class="w-5 h-5 rounded cursor-pointer text-indigo-600 focus:ring-indigo-500"
                    />
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Mobile: Series dentro del acordeón (Diseño limpio, sin cards anidadas) -->
          <div class="md:hidden divide-y divide-gray-100 dark:divide-gray-700/60 bg-white dark:bg-gray-800">
            <div
              v-for="fila in ejercicio.series"
              :key="`${fila.uid}-mobile`"
              class="p-4 transition-colors"
              :class="fila.completado ? 'bg-emerald-50/30 dark:bg-emerald-950/15' : ''"
            >
              <!-- Cabecera de la serie: Número, Reps objetivo y Checkbox completado -->
              <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                  <span
                    :class="[
                      'inline-flex items-center justify-center w-7 h-7 rounded-full text-xs font-bold transition-all shadow-xs',
                      fila.completado
                        ? 'bg-emerald-500 text-white'
                        : 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300'
                    ]"
                  >
                    {{ fila.series_numero }}
                  </span>
                  <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">
                    Serie {{ fila.series_numero }}
                  </span>
                  <span class="text-xs text-gray-400">·</span>
                  <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
                    Obj: <strong class="text-gray-700 dark:text-gray-300">{{ fila.reps_min }} - {{ fila.reps_max }} reps</strong>
                  </span>
                </div>

                <label
                  class="inline-flex items-center gap-2 px-3 py-1 rounded-lg text-xs font-bold transition-all cursor-pointer select-none"
                  :class="fila.completado
                    ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 ring-1 ring-emerald-500/30'
                    : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300 hover:bg-gray-200'"
                >
                  <input
                    v-model="fila.completado"
                    @change="$emit('guardar', fila)"
                    type="checkbox"
                    class="w-4 h-4 rounded text-emerald-600 focus:ring-emerald-500 cursor-pointer"
                  />
                  {{ fila.completado ? 'Hecha ✓' : 'Marcar' }}
                </label>
              </div>

              <!-- Inputs de Reps y Peso (Más amplios y cómodos) -->
              <div class="grid grid-cols-2 gap-3 mb-3">
                <div>
                  <label class="block text-[11px] font-semibold text-gray-500 dark:text-gray-400 mb-1">
                    Reps hechas
                  </label>
                  <div class="relative rounded-xl border border-gray-300 dark:border-gray-600 bg-gray-50/50 dark:bg-gray-900/60 focus-within:ring-2 focus-within:ring-indigo-500 focus-within:border-indigo-500">
                    <input
                      v-model.number="fila.reps_realizadas"
                      @change="$emit('guardar', fila)"
                      type="number"
                      min="0"
                      step="1"
                      placeholder="0"
                      class="w-full bg-transparent px-3 py-2 text-center text-base font-bold text-gray-900 dark:text-white outline-none"
                    />
                  </div>
                </div>

                <div>
                  <label class="block text-[11px] font-semibold text-gray-500 dark:text-gray-400 mb-1">
                    Peso (Kg)
                  </label>
                  <div class="relative rounded-xl border border-gray-300 dark:border-gray-600 bg-gray-50/50 dark:bg-gray-900/60 focus-within:ring-2 focus-within:ring-indigo-500 focus-within:border-indigo-500">
                    <input
                      v-model.number="fila.peso"
                      @change="$emit('guardar', fila)"
                      type="number"
                      min="0"
                      step="0.5"
                      placeholder="0"
                      class="w-full bg-transparent px-3 py-2 text-center text-base font-bold text-gray-900 dark:text-white outline-none"
                    />
                  </div>
                </div>
              </div>

              <!-- Selector de Esfuerzo (RIR / RPE): integrado limpio sin card anidada -->
              <div class="flex items-center justify-between gap-2 pt-1">
                <div class="flex items-center gap-2">
                  <span class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Esfuerzo</span>
                  <div class="inline-flex rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden text-[10px]">
                    <button
                      type="button"
                      :class="[
                        'px-2 py-0.5 font-bold transition-colors cursor-pointer',
                        fila.esfuerzo_tipo === 'rir' ? 'bg-emerald-500 text-white' : 'bg-gray-50 dark:bg-gray-900 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-750'
                      ]"
                      @click="toggleEsfuerzoTipo(fila, 'rir')"
                    >RIR</button>
                    <button
                      type="button"
                      :class="[
                        'px-2 py-0.5 font-bold transition-colors cursor-pointer',
                        fila.esfuerzo_tipo === 'rpe' ? 'bg-amber-500 text-white' : 'bg-gray-50 dark:bg-gray-900 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-750'
                      ]"
                      @click="toggleEsfuerzoTipo(fila, 'rpe')"
                    >RPE</button>
                  </div>
                </div>

                <div class="flex items-center gap-1 overflow-x-auto">
                  <button
                    v-for="opt in esfuerzoOptions(fila.esfuerzo_tipo)"
                    :key="opt"
                    type="button"
                    :class="[
                      'w-7 h-7 rounded-lg text-xs font-bold transition-colors cursor-pointer flex items-center justify-center',
                      fila.esfuerzo_valor === opt
                        ? (fila.esfuerzo_tipo === 'rir' ? 'bg-emerald-500 text-white shadow-xs' : 'bg-amber-500 text-white shadow-xs')
                        : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
                    ]"
                    @click="setEsfuerzoValor(fila, opt)"
                  >{{ opt }}</button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty state si no hay ejercicios -->
      <div
        v-if="!ejerciciosAgrupados.length"
        class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 p-8 text-center text-gray-500 dark:text-gray-400"
      >
        No hay ejercicios para este día.
      </div>
    </div>

    <!-- Botonera inferior -->
    <div class="p-5 bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-700 border-t border-gray-200 dark:border-gray-700">
      <div class="flex flex-wrap justify-center gap-4">
        <button
          v-if="diaIndex > 0"
          @click="$emit('dia-anterior')"
          class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold transition-all shadow-md hover:shadow-lg"
        >
          ← Día Anterior
        </button>
        <button
          @click="$emit('guardar-sesion')"
          class="hidden md:inline-flex bg-slate-700 hover:bg-slate-800 text-white px-6 py-3 rounded-lg font-semibold transition-all shadow-md hover:shadow-lg"
        >
          Guardar sesión
        </button>
        <button
          @click="$emit('siguiente-dia')"
          :class="['text-white px-8 py-3 rounded-lg font-semibold transition-all shadow-md hover:shadow-lg', botonSiguienteClass]"
        >
          {{ textoBotonSiguiente }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    filasSerie: { type: Array, required: true },
    diaIndex: { type: Number, required: true },
    textoBotonSiguiente: { type: String, required: true },
    botonSiguienteClass: { type: String, required: true },
});

const emit = defineEmits(['guardar', 'dia-anterior', 'guardar-sesion', 'siguiente-dia']);

// === Agrupación de series por ejercicio ===
const ejerciciosAgrupados = computed(() => {
    const map = new Map();
    const list = [];

    props.filasSerie.forEach((fila) => {
        const key = fila.ejercicio_nombre;
        if (!map.has(key)) {
            const grupo = {
                nombre: fila.ejercicio_nombre,
                superserie_grupo: fila.superserie_grupo,
                descanso_min: fila.descanso_min,
                reps_min: fila.reps_min,
                reps_max: fila.reps_max,
                notas: fila.notas || '',
                series: [],
            };
            map.set(key, grupo);
            list.push(grupo);
        }
        const grupo = map.get(key);
        if (!grupo.notas && fila.notas) grupo.notas = fila.notas;
        if (!grupo.superserie_grupo && fila.superserie_grupo) grupo.superserie_grupo = fila.superserie_grupo;
        grupo.series.push(fila);
    });

    return list;
});

// === Estado de acordeón por ejercicio (abiertos por defecto) ===
const ejerciciosColapsados = ref({});

const estaAbierto = (nombre) => !ejerciciosColapsados.value[nombre];

const toggleEjercicio = (nombre) => {
    ejerciciosColapsados.value[nombre] = !ejerciciosColapsados.value[nombre];
};

const todosAbiertos = computed(() => {
    if (!ejerciciosAgrupados.value.length) return false;
    return ejerciciosAgrupados.value.every((e) => estaAbierto(e.nombre));
});

const toggleTodos = () => {
    const colapsar = todosAbiertos.value;
    const newState = {};
    ejerciciosAgrupados.value.forEach((e) => {
        newState[e.nombre] = colapsar;
    });
    ejerciciosColapsados.value = newState;
};

// === Estadísticas y progreso ===
const totalSeries = computed(() => props.filasSerie.length);

const totalCompletadas = computed(() => props.filasSerie.filter((f) => f.completado).length);

const porcentajeCompletado = computed(() => {
    if (!totalSeries.value) return 0;
    return Math.round((totalCompletadas.value / totalSeries.value) * 100);
});

const completadasPorEjercicio = (ejercicio) => {
    return ejercicio.series.filter((s) => s.completado).length;
};

const ejercicioCompleto = (ejercicio) => {
    return ejercicio.series.length > 0 && completadasPorEjercicio(ejercicio) === ejercicio.series.length;
};

// === Esfuerzo (Fase 3): RIR 0..5 / RPE 6..10 ===
const esfuerzoOptions = (tipo) => (tipo === 'rpe' ? [6, 7, 8, 9, 10] : [0, 1, 2, 3, 4, 5]);

const setEsfuerzoValor = (fila, valor) => {
    const nuevo = fila.esfuerzo_valor === valor ? null : valor;
    fila.esfuerzo_valor = nuevo;
    if (nuevo === null) fila.esfuerzo_tipo = null;
    emit('guardar', fila);
};

const toggleEsfuerzoTipo = (fila, tipo) => {
    if (fila.esfuerzo_tipo === tipo && fila.esfuerzo_valor !== null) {
        fila.esfuerzo_tipo = null;
        fila.esfuerzo_valor = null;
    } else {
        fila.esfuerzo_tipo = tipo;
        const valid = tipo === 'rpe' ? [6, 7, 8, 9, 10] : [0, 1, 2, 3, 4, 5];
        if (fila.esfuerzo_valor === null || !valid.includes(fila.esfuerzo_valor)) {
            fila.esfuerzo_valor = tipo === 'rpe' ? 8 : 2;
        }
    }
    emit('guardar', fila);
};

// === Clases de estilo visual ===
const getSuperserieBadgeClass = (grupo) => {
    switch (grupo) {
        case 1:
            return 'bg-indigo-100 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 ring-indigo-500/30';
        case 2:
            return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 ring-emerald-500/30';
        case 3:
            return 'bg-pink-100 text-pink-700 dark:bg-pink-950/60 dark:text-pink-300 ring-pink-500/30';
        case 4:
            return 'bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 ring-amber-500/30';
        default:
            return 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300 ring-gray-500/30';
    }
};

const getEjercicioCardClass = (ejercicio) => {
    if (ejercicioCompleto(ejercicio)) {
        return 'border-emerald-200 dark:border-emerald-800/60 bg-white dark:bg-gray-800 ring-1 ring-emerald-500/20';
    }
    const grupo = ejercicio.superserie_grupo;
    if (!grupo) return 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800';
    switch (grupo) {
        case 1:
            return 'border-indigo-200 dark:border-indigo-800/60 bg-white dark:bg-gray-800 border-l-4 border-l-indigo-500';
        case 2:
            return 'border-emerald-200 dark:border-emerald-800/60 bg-white dark:bg-gray-800 border-l-4 border-l-emerald-500';
        case 3:
            return 'border-pink-200 dark:border-pink-800/60 bg-white dark:bg-gray-800 border-l-4 border-l-pink-500';
        case 4:
            return 'border-amber-200 dark:border-amber-800/60 bg-white dark:bg-gray-800 border-l-4 border-l-amber-500';
        default:
            return 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 border-l-4 border-l-gray-500';
    }
};

const getEjercicioHeaderBg = (ejercicio) => {
    if (ejercicioCompleto(ejercicio)) {
        return 'bg-emerald-50/60 hover:bg-emerald-100/60 dark:bg-emerald-950/20 dark:hover:bg-emerald-950/30';
    }
    return 'bg-gray-50/70 hover:bg-gray-100/70 dark:bg-gray-800 dark:hover:bg-gray-750';
};
</script>