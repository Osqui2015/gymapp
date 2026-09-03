<template>
  <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden border border-gray-200 dark:border-gray-700">
    <button
      @click="$emit('toggle')"
      class="w-full px-6 py-5 text-left flex justify-between items-center bg-gradient-to-r from-gray-50 to-white hover:from-gray-100 hover:to-gray-50 dark:from-gray-800 dark:to-gray-700 dark:hover:from-gray-700/60 dark:hover:to-gray-600/60 transition-all duration-200"
    >
      <div class="flex items-center flex-wrap gap-2">
        <span :class="titleClass" class="text-lg font-bold">{{ modalidad.nombre }}</span>
        <span class="px-3 py-1 text-sm font-medium bg-indigo-100 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-300 rounded-full">
          {{ modalidad.dias.length }} días
        </span>
        <!-- Botón favorito (no se expande al click del header) -->
        <button
          type="button"
          @click.stop="$emit('toggle-favorite', { nivel, modalidad: modalidad.nombre })"
          :title="esFavorita ? 'Quitar de favoritos' : 'Marcar como favorita'"
          :aria-label="esFavorita ? 'Quitar de favoritos' : 'Marcar como favorita'"
          :aria-pressed="esFavorita"
          class="ml-1 p-1.5 rounded-lg hover:bg-amber-100 dark:hover:bg-amber-900/30 transition-colors"
        >
          <svg
            class="w-5 h-5 transition-colors"
            :class="esFavorita ? 'text-amber-500 fill-amber-500' : 'text-gray-400 dark:text-gray-500'"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="1.8"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              :fill="esFavorita ? 'currentColor' : 'none'"
              d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"
            />
          </svg>
        </button>
        <slot name="header-extra" :modalidad="modalidad" />
      </div>
      <svg
        :class="{ 'rotate-180': open }"
        class="w-6 h-6 text-gray-600 dark:text-gray-400 transition-transform duration-200"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
      </svg>
    </button>

    <div v-if="open" class="border-t border-gray-200 dark:border-gray-700">
      <div
        v-for="dia in modalidad.dias"
        :key="dia.nombre"
        class="border-b last:border-b-0 border-gray-100 dark:border-gray-700"
      >
        <button
          @click="$emit('toggle-dia', dia.nombre)"
          class="w-full px-6 py-4 text-left flex justify-between items-center bg-blue-50/50 hover:bg-blue-100/50 dark:bg-gray-900/40 dark:hover:bg-gray-900/60 transition-colors"
        >
          <div class="flex items-center">
            <svg class="w-5 h-5 text-indigo-500 dark:text-indigo-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z" />
            </svg>
            <span class="font-semibold text-indigo-950 dark:text-indigo-300">{{ dia.nombre }}</span>
            <span class="ml-2 text-xs text-indigo-500 dark:text-indigo-400">({{ dia.ejercicios.length }} ejercicios)</span>
          </div>
          <svg
            :class="{ 'rotate-180': openDias.includes(dia.nombre) }"
            class="w-5 h-5 text-indigo-500 dark:text-indigo-400 transition-transform"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
          </svg>
        </button>

        <div v-if="openDias.includes(dia.nombre)" class="p-5 bg-gray-50/30 dark:bg-gray-900/30 overflow-x-auto">
          <!-- Slot por-dia: botones extra (ej. Importar este dia) -->
          <slot name="dia-footer" :dia="dia" :modalidad="modalidad" />

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
              <tr
                v-for="ejercicio in dia.ejercicios"
                :key="ejercicio.id"
                class="bg-white hover:bg-gray-50 dark:bg-gray-800 dark:hover:bg-gray-700 transition-all"
                :class="getSuperserieClass(ejercicio)"
              >
                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                  {{ ejercicio.ejercicio_nombre }}
                  <span
                    v-if="ejercicio.superserie_grupo"
                    class="ml-2 inline-flex items-center rounded-md bg-indigo-50 px-2 py-0.5 text-xs font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-700/10 dark:bg-indigo-900/40 dark:text-indigo-400"
                  >
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

          <!-- Mobile quick input button -->
          <button
            v-if="showQuickInput"
            type="button"
            @click="$emit('quick-input', dia)"
            class="md:hidden mt-3 w-full inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-emerald-500 to-emerald-600 text-white px-4 py-3 text-sm font-bold shadow-md hover:from-emerald-600 hover:to-emerald-700 active:scale-[0.98] transition-all"
            :aria-label="`Carga rápida de series para ${dia.nombre}`"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
            ⚡ Carga rápida de {{ dia.nombre }}
          </button>
        </div>
      </div>

      <div
        v-if="showSelectButton"
        class="p-5 bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-950/20 dark:to-purple-950/20 border-t border-gray-200 dark:border-gray-700"
      >
        <button
          @click="$emit('select')"
          class="w-full bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white px-6 py-4 rounded-xl font-bold transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5"
        >
          Seleccionar {{ selectLabel || 'Rutina' }}
        </button>
      </div>

      <!-- Footer slot (botones extra como Compartir/Eliminar) -->
      <slot name="footer" :modalidad="modalidad" />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    modalidad: { type: Object, required: true },
    nivel: { type: String, required: true },
    open: { type: Boolean, required: true },
    openDias: { type: Array, required: true },
    titleClass: { type: String, default: 'text-gray-800 dark:text-white' },
    showSelectButton: { type: Boolean, default: false },
    selectLabel: { type: String, default: '' },
    showQuickInput: { type: Boolean, default: false },
});

defineEmits(['toggle', 'toggle-dia', 'select', 'quick-input', 'toggle-favorite']);

// El flag is_favorita se computa en el backend y se propaga a TODAS las
// filas (rutinas) de la misma modalidad. Leemos de cualquiera de los
// ejercicios del primer día como source of truth.
const esFavorita = computed(() => {
    const primerEj = props.modalidad?.dias?.[0]?.ejercicios?.[0];
    return primerEj?.is_favorita === true;
});

const getSuperserieClass = (ejercicio) => {
    const grupo = ejercicio.superserie_grupo;
    if (!grupo) return '';
    switch (grupo) {
        case 1: return 'border-l-4 border-indigo-500 dark:border-indigo-400 bg-indigo-50/10 dark:bg-indigo-950/20';
        case 2: return 'border-l-4 border-emerald-500 bg-emerald-50/10 dark:bg-emerald-950/20';
        case 3: return 'border-l-4 border-pink-500 bg-pink-50/10 dark:bg-pink-950/20';
        case 4: return 'border-l-4 border-amber-500 bg-amber-50/10 dark:bg-amber-950/20';
        default: return 'border-l-4 border-gray-500 bg-gray-50/10 dark:bg-gray-950/20';
    }
};
</script>