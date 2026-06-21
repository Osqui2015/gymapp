<template>
  <div>
    <!-- Stats grid -->
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

    <!-- Progress del día -->
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm mb-6">
      <div class="flex items-center justify-between mb-3">
        <div>
          <p class="text-sm text-gray-500 dark:text-gray-400">Progreso del día</p>
          <p class="text-lg font-semibold text-gray-900 dark:text-white">
            {{ seriesCompletadas }} de {{ seriesTotales }} series
          </p>
        </div>
        <span class="text-sm font-medium text-indigo-600 dark:text-indigo-400">{{ progresoDia }}%</span>
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
  </div>
</template>

<script setup>
defineProps({
    seriesTotales: { type: Number, required: true },
    seriesCompletadas: { type: Number, required: true },
    seriesPendientes: { type: Number, required: true },
    pesoRegistrado: { type: [String, Number], required: true },
    pesoPromedio: { type: [String, Number], required: true },
    repsRegistradas: { type: Number, required: true },
    progresoDia: { type: Number, required: true },
});
</script>