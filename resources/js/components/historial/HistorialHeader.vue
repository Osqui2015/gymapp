<template>
  <div>
    <!-- Hero / header card -->
    <div class="rounded-2xl bg-gradient-to-r from-slate-950 via-slate-900 to-indigo-950 border border-slate-800 shadow-2xl p-6 md:p-8 mb-8">
      <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
        <div>
          <p class="text-sm uppercase tracking-[0.2em] text-indigo-300 mb-2">Seguimiento de progreso</p>
          <h1 class="text-3xl md:text-4xl font-bold text-white">Historial de entrenamiento</h1>
          <p class="mt-3 text-sm md:text-base text-slate-300 max-w-2xl">
            Revisa la evolución de tus ejercicios, el promedio de peso utilizado y cómo cambia el rendimiento por día.
          </p>

          <!-- Selector de alumno para entrenadores/admins -->
          <div v-if="isTrainerOrAdmin && alumnos.length" class="mt-6 flex flex-col sm:flex-row sm:items-center gap-3 bg-white/5 border border-white/10 rounded-xl p-4 max-w-xl">
            <div class="flex items-center gap-2 text-indigo-300">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
              </svg>
              <span class="text-sm font-semibold">Ver historial de:</span>
            </div>
            <select
              v-model="localAlumnoId"
              @change="$emit('alumno-change', localAlumnoId)"
              class="rounded-lg border border-slate-700 bg-slate-900 px-3 py-1.5 text-sm text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 w-full sm:w-auto min-w-[200px]"
            >
              <option v-for="alumno in alumnos" :key="alumno.id" :value="alumno.id">
                {{ alumno.name }} ({{ alumno.nick }})
              </option>
            </select>
          </div>
        </div>
        <div class="flex items-center gap-3">
          <button
            v-if="canExport"
            @click="$emit('export-csv')"
            class="inline-flex items-center justify-center rounded-xl bg-emerald-600 hover:bg-emerald-700 px-5 py-3 text-sm font-semibold text-white transition-colors shadow-md"
            aria-label="Exportar historial a CSV"
          >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Exportar CSV
          </button>
          <button
            v-if="canExport"
            @click="$emit('export-pdf')"
            class="inline-flex items-center justify-center rounded-xl bg-rose-600 hover:bg-rose-700 px-5 py-3 text-sm font-semibold text-white transition-colors shadow-md"
            aria-label="Exportar historial a PDF"
          >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m-6-8h2" />
            </svg>
            Exportar PDF
          </button>
          <a
            href="/dashboard"
            class="inline-flex items-center justify-center rounded-xl bg-white/10 px-5 py-3 text-sm font-semibold text-white transition-colors hover:bg-white/15 border border-white/10"
          >
            Volver al dashboard
          </a>
        </div>
      </div>
    </div>

    <!-- Stats grid -->
    <div class="grid gap-4 md:grid-cols-4 mb-8">
      <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm">
        <p class="text-sm text-gray-500 dark:text-gray-400">Ejercicios rastreados</p>
        <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ stats.ejercicios }}</p>
      </div>
      <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm">
        <p class="text-sm text-gray-500 dark:text-gray-400">Series registradas</p>
        <p class="mt-2 text-3xl font-bold text-emerald-600 dark:text-emerald-400">{{ stats.totalSeries }}</p>
      </div>
      <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm">
        <p class="text-sm text-gray-500 dark:text-gray-400">Peso promedio global</p>
        <p class="mt-2 text-3xl font-bold text-indigo-600 dark:text-indigo-400">{{ stats.pesoPromedio }} kg</p>
      </div>
      <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm">
        <p class="text-sm text-gray-500 dark:text-gray-400">Reps promedio global</p>
        <p class="mt-2 text-3xl font-bold text-orange-600 dark:text-orange-400">{{ stats.repsPromedio }}</p>
      </div>
    </div>

    <!-- Tabs -->
    <div class="flex border-b border-gray-200 dark:border-gray-700 mb-6 gap-6 overflow-x-auto scrollbar-hide">
      <button
        v-for="tab in tabs"
        :key="tab.id"
        @click="$emit('tab-change', tab.id)"
        :class="activeTab === tab.id
          ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 dark:border-indigo-400'
          : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
        class="pb-4 text-sm font-semibold border-b-2 transition-all flex items-center gap-2 whitespace-nowrap"
      >
        <span v-if="tab.emoji">{{ tab.emoji }}</span>
        <svg v-else-if="tab.icon === 'matrix'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
        </svg>
        <svg v-else-if="tab.icon === 'evolution'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
        </svg>
        <svg v-else-if="tab.icon === 'calendar'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
        <svg v-else-if="tab.icon === 'body'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4a2 2 0 100 4 2 2 0 000-4zM8 12c0-2 2-3 4-3s4 1 4 3v6a1 1 0 11-2 0v-2H10v2a1 1 0 11-2 0v-6z" />
        </svg>
        <svg v-else-if="tab.icon === 'key'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
        </svg>
        {{ tab.label }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    isTrainerOrAdmin: { type: Boolean, required: true },
    alumnos: { type: Array, required: true },
    selectedAlumnoId: { type: [Number, null], default: null },
    activeTab: { type: String, required: true },
    showKeyExercisesTab: { type: Boolean, default: false },
    canExport: { type: Boolean, default: false },
    stats: {
        type: Object,
        required: true,
        // { ejercicios, totalSeries, pesoPromedio, repsPromedio }
    },
});

defineEmits(['alumno-change', 'tab-change', 'export-csv', 'export-pdf']);

const localAlumnoId = ref(props.selectedAlumnoId);

watch(() => props.selectedAlumnoId, (val) => {
    localAlumnoId.value = val;
});

const tabs = [
    { id: 'matrix',        label: 'Matriz de Progreso',   icon: 'matrix' },
    { id: 'evolution',     label: 'Evolución Detallada',  icon: 'evolution' },
    { id: 'comparison',    label: 'Comparación',          emoji: '⚖️' },
    { id: 'body_map',      label: 'Mapa Corporal',        icon: 'body' },
    { id: 'calendar',      label: 'Calendario',           icon: 'calendar' },
    { id: 'rm_calculator', label: 'Estimador 1RM',        emoji: '💪' },
];
if (props.showKeyExercisesTab) {
    tabs.push({ id: 'key_exercises', label: 'Ejercicios Clave', icon: 'key' });
}
</script>

<style scoped>
.scrollbar-hide::-webkit-scrollbar {
  display: none;
}
.scrollbar-hide {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>
