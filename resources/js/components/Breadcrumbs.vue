<template>
  <nav class="flex items-center text-sm mb-4" aria-label="Breadcrumb">
    <ol class="flex items-center gap-1 flex-wrap">
      <li v-for="(crumb, i) in items" :key="i" class="flex items-center gap-1">
        <!-- Separador (excepto en el primero) -->
        <svg
          v-if="i > 0"
          class="w-3.5 h-3.5 text-gray-400 dark:text-gray-500 mx-0.5"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
        </svg>

        <!-- Item -->
        <a
          v-if="crumb.href && i < items.length - 1"
          :href="crumb.href"
          class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
        >
          <span v-if="crumb.icon" v-html="crumb.icon"></span>
          {{ crumb.label }}
        </a>
        <span
          v-else
          class="inline-flex items-center gap-1.5 px-2 py-1 font-semibold text-gray-900 dark:text-white"
          aria-current="page"
        >
          <span v-if="crumb.icon" v-html="crumb.icon"></span>
          {{ crumb.label }}
        </span>
      </li>
    </ol>
  </nav>
</template>

<script setup>
defineProps({
    items: {
        type: Array,
        required: true,
        // [{ label: 'Inicio', href: '/', icon: '<svg>...</svg>' }, { label: 'Historial' }]
    },
});
</script>
