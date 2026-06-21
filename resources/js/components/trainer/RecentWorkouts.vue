<template>
  <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden border border-gray-200 dark:border-gray-700">
    <div class="p-4 border-b border-gray-200 dark:border-gray-700">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Últimos Entrenamientos</h3>
    </div>

    <div v-if="entrenos?.length > 0" class="divide-y divide-gray-100 dark:divide-gray-700">
      <div
        v-for="(entreno, index) in entrenos"
        :key="index"
        class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
      >
        <div class="flex items-center justify-between gap-3">
          <div class="flex items-center gap-3 min-w-0 flex-1">
            <div class="bg-blue-100 dark:bg-blue-900/30 w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0">
              <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
              </svg>
            </div>
            <div class="min-w-0 flex-1">
              <p class="font-medium text-gray-900 dark:text-white truncate">{{ entreno.user_name }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-400 truncate">
                {{ entreno.rutina }} - {{ entreno.dia }}
              </p>
            </div>
          </div>
          <span class="text-sm text-gray-400 dark:text-gray-500 whitespace-nowrap flex-shrink-0">{{ entreno.fecha }}</span>
        </div>
      </div>
    </div>

    <EmptyState
      v-else
      emoji="⚡"
      title="Sin entrenamientos recientes"
      description="Cuando tus alumnos registren entrenamientos van a aparecer acá con la fecha, rutina y día."
      variant="compact"
    />
  </div>
</template>

<script setup>
import EmptyState from '../EmptyState.vue';

defineProps({
    entrenos: { type: Array, default: () => [] },
});
</script>