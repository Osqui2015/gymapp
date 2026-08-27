<template>
  <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden border border-gray-200 dark:border-gray-700">
    <!-- Mobile: cards -->
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
              <span
                v-if="fila.superserie_grupo"
                class="ml-2 inline-flex items-center rounded-md bg-indigo-100 dark:bg-indigo-950/60 dark:text-indigo-300 px-2 py-0.5 text-xs font-semibold ring-1 ring-inset ring-indigo-500/30"
              >
                Superserie {{ fila.superserie_grupo }}
              </span>
            </h3>
            <p class="mt-1 text-xs uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Serie {{ fila.series_numero }}</p>
          </div>
          <span class="inline-flex items-center rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300 px-2.5 py-1 text-[11px] font-semibold whitespace-nowrap">
            {{ fila.reps_min }} - {{ fila.reps_max }} reps
          </span>
        </div>

        <div class="grid grid-cols-2 gap-3 text-sm mb-3">
          <label class="block">
            <span class="mb-1 block text-gray-500 dark:text-gray-400">Reps hechas</span>
            <input
              v-model.number="fila.reps_realizadas"
              @change="$emit('guardar', fila)"
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
              @change="$emit('guardar', fila)"
              type="number"
              min="0"
              step="0.5"
              placeholder="Kg"
              class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-950 px-3 py-2 text-center text-gray-900 dark:text-white focus:border-indigo-500 focus:ring-indigo-500"
            />
          </label>
        </div>

        <!-- Esfuerzo RIR/RPE (Fase 3) -->
        <div class="mb-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50/60 dark:bg-gray-900/40 p-2">
          <div class="mb-1.5 flex items-center justify-between">
            <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Esfuerzo</span>
            <div class="inline-flex rounded-md border border-gray-200 dark:border-gray-700 overflow-hidden text-[11px]">
              <button
                type="button"
                :class="[
                  'px-2 py-0.5 transition-colors',
                  fila.esfuerzo_tipo === 'rir' ? 'bg-emerald-500 text-white' : 'bg-white dark:bg-gray-800 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700'
                ]"
                @click="toggleEsfuerzoTipo(fila, 'rir')"
              >RIR</button>
              <button
                type="button"
                :class="[
                  'px-2 py-0.5 transition-colors',
                  fila.esfuerzo_tipo === 'rpe' ? 'bg-amber-500 text-white' : 'bg-white dark:bg-gray-800 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700'
                ]"
                @click="toggleEsfuerzoTipo(fila, 'rpe')"
              >RPE</button>
            </div>
          </div>
          <div class="flex flex-wrap gap-1.5">
            <button
              v-for="opt in esfuerzoOptions(fila.esfuerzo_tipo)"
              :key="opt"
              type="button"
              :class="[
                'min-w-[36px] rounded-md border px-2 py-1 text-xs font-semibold transition-colors',
                fila.esfuerzo_valor === opt
                  ? (fila.esfuerzo_tipo === 'rir' ? 'bg-emerald-500 text-white border-emerald-500' : 'bg-amber-500 text-white border-amber-500')
                  : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:border-gray-400'
              ]"
              @click="setEsfuerzoValor(fila, opt)"
            >{{ opt }}</button>
          </div>
        </div>

        <div class="flex items-center justify-between gap-3">
          <div class="text-sm text-orange-600 dark:text-orange-400 font-medium">
            Descanso: {{ fila.descanso_min }} min
          </div>
          <label class="inline-flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300">
            <input
              v-model="fila.completado"
              @change="$emit('guardar', fila)"
              type="checkbox"
              class="w-5 h-5 rounded cursor-pointer text-indigo-600 focus:ring-indigo-500"
            />
            Hecho
          </label>
        </div>
      </div>

      <div
        v-if="!filasSerie.length"
        class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 p-6 text-center text-gray-500 dark:text-gray-400"
      >
        No hay ejercicios para este día.
      </div>
    </div>

    <!-- Desktop: tabla -->
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
            <th class="px-4 py-4 text-center font-bold text-gray-700 dark:text-gray-300">Esfuerzo</th>
            <th class="px-4 py-4 text-center font-bold text-gray-700 dark:text-gray-300">Completado</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
          <tr
            v-for="fila in filasSerie"
            :key="fila.uid"
            class="transition-all"
            :class="getSuperserieRowBgClass(fila)"
          >
            <td class="px-4 py-4 font-medium text-gray-900 dark:text-white">
              {{ fila.ejercicio_nombre }}
              <span
                v-if="fila.superserie_grupo"
                class="ml-2 inline-flex items-center rounded-md bg-indigo-50 dark:bg-indigo-950/40 dark:text-indigo-400 px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset ring-indigo-500/20"
              >
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
                  @change="$emit('guardar', fila)"
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
                  @change="$emit('guardar', fila)"
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
              <div class="inline-flex flex-col items-center gap-1">
                <div class="inline-flex rounded-md border border-gray-200 dark:border-gray-700 overflow-hidden text-[10px]">
                  <button
                    type="button"
                    :class="[
                      'px-1.5 py-0.5 transition-colors',
                      fila.esfuerzo_tipo === 'rir' ? 'bg-emerald-500 text-white' : 'bg-white dark:bg-gray-800 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700'
                    ]"
                    @click="toggleEsfuerzoTipo(fila, 'rir')"
                  >RIR</button>
                  <button
                    type="button"
                    :class="[
                      'px-1.5 py-0.5 transition-colors',
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
                      'min-w-[22px] rounded text-[10px] font-bold transition-colors',
                      fila.esfuerzo_valor === opt
                        ? (fila.esfuerzo_tipo === 'rir' ? 'bg-emerald-500 text-white' : 'bg-amber-500 text-white')
                        : 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
                    ]"
                    @click="setEsfuerzoValor(fila, opt)"
                  >{{ opt }}</button>
                </div>
              </div>
            </td>
            <td class="px-4 py-4 text-center">
              <input
                v-model="fila.completado"
                @change="$emit('guardar', fila)"
                type="checkbox"
                class="w-6 h-6 rounded cursor-pointer text-indigo-600 focus:ring-indigo-500"
              />
            </td>
          </tr>
        </tbody>
      </table>
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
defineProps({
    filasSerie: { type: Array, required: true },
    diaIndex: { type: Number, required: true },
    textoBotonSiguiente: { type: String, required: true },
    botonSiguienteClass: { type: String, required: true },
});

const emit = defineEmits(['guardar', 'dia-anterior', 'guardar-sesion', 'siguiente-dia']);

// === Esfuerzo (Fase 3): RIR 0..5 / RPE 6..10 ===
const esfuerzoOptions = (tipo) => (tipo === 'rpe' ? [6, 7, 8, 9, 10] : [0, 1, 2, 3, 4, 5]);

const setEsfuerzoValor = (fila, valor) => {
    const nuevo = fila.esfuerzo_valor === valor ? null : valor;
    fila.esfuerzo_valor = nuevo;
    if (nuevo === null) fila.esfuerzo_tipo = null;
    emit('guardar', fila);
};

const toggleEsfuerzoTipo = (fila, tipo) => {
    // Si clickea el mismo botón y ya hay valor, limpia todo
    if (fila.esfuerzo_tipo === tipo && fila.esfuerzo_valor !== null) {
        fila.esfuerzo_tipo = null;
        fila.esfuerzo_valor = null;
    } else {
        fila.esfuerzo_tipo = tipo;
        // Si el valor actual no es válido para el nuevo tipo, resetear
        const valid = tipo === 'rpe' ? [6, 7, 8, 9, 10] : [0, 1, 2, 3, 4, 5];
        if (fila.esfuerzo_valor === null || !valid.includes(fila.esfuerzo_valor)) {
            fila.esfuerzo_valor = tipo === 'rpe' ? 8 : 2;
        }
    }
    emit('guardar', fila);
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
</script>