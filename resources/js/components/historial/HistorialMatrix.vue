<template>
  <div class="space-y-6">
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-lg overflow-hidden">
      <div class="px-6 py-4 bg-gradient-to-r from-slate-900 to-indigo-900 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
        <h2 class="text-lg font-bold text-white flex items-center gap-2">
          <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
          </svg>
          Matriz de Cargas por Fecha
        </h2>
        <button
          @click="$emit('toggle-sort')"
          class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white/10 hover:bg-white/15 border border-white/10 text-xs font-semibold text-white transition-colors"
        >
          <svg class="w-4 h-4 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
          </svg>
          Orden: {{ dateSortAsc ? 'Cronológico' : 'Últimos primero' }}
        </button>
      </div>

      <div class="p-6">
        <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
          <table class="w-full text-sm text-left border-collapse">
            <thead class="bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-300 uppercase text-xs">
              <tr>
                <th class="sticky left-0 z-20 bg-gray-100 dark:bg-gray-600 px-4 py-3.5 font-bold border-r border-gray-200 dark:border-gray-700 min-w-[200px]">
                  Ejercicio
                </th>
                <th v-for="date in pivotData.dates" :key="date.raw" class="px-4 py-3.5 font-bold text-center border-r border-gray-200 dark:border-gray-700 min-w-[100px]">
                  {{ date.formatted }}
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-for="row in pivotData.rows" :key="row.name" class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors bg-white dark:bg-gray-800">
                <td class="sticky left-0 z-10 bg-white dark:bg-gray-800 px-4 py-3.5 font-semibold text-gray-900 dark:text-white border-r border-gray-200 dark:border-gray-700 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)]">
                  {{ row.name }}
                  <span v-if="row.superserie_grupo" class="ml-2 inline-flex items-center rounded-md bg-indigo-50 px-2 py-0.5 text-xs font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-700/10 dark:bg-indigo-900/40 dark:text-indigo-400">
                    Superserie {{ row.superserie_grupo }}
                  </span>
                </td>
                <td v-for="date in pivotData.dates" :key="date.raw" class="px-4 py-3.5 text-center border-r border-gray-200 dark:border-gray-700 font-medium">
                  <span v-if="row.weights[date.raw] !== '-'" class="inline-flex items-center justify-center rounded-md bg-indigo-50 dark:bg-indigo-950/40 px-2 py-1 text-sm font-bold text-indigo-600 dark:text-indigo-400">
                    {{ row.weights[date.raw] }}
                  </span>
                  <span v-else class="text-gray-400 dark:text-gray-600 font-normal">
                    -
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
    pivotData: { type: Object, required: true }, // { dates: [], rows: [] }
    dateSortAsc: { type: Boolean, required: true },
});

defineEmits(['toggle-sort']);
</script>
