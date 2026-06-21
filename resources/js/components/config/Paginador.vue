<template>
  <div class="flex items-center justify-between mt-4 text-sm">
    <button
      @click="$emit('prev')"
      :disabled="currentPage <= 1"
      class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed font-semibold"
    >
      ← Anterior
    </button>
    <span class="text-gray-600 dark:text-gray-400 font-medium">Página {{ currentPage }} de {{ totalPages }}</span>
    <button
      @click="$emit('next')"
      :disabled="currentPage >= totalPages"
      class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed font-semibold"
    >
      Siguiente →
    </button>
  </div>
</template>

<script setup>
defineProps({
    currentPage: { type: Number, required: true },
    totalPages: { type: Number, required: true },
});

defineEmits(['prev', 'next']);
</script>
