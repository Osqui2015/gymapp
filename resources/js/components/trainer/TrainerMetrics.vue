<template>
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Alumnos Activos -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm text-gray-500 dark:text-gray-400">Alumnos Activos</p>
          <p class="mt-2 text-4xl font-bold text-green-600 dark:text-green-400">{{ metricas.alumnos_activos }}</p>
          <p class="text-xs text-gray-400 mt-1">esta semana</p>
        </div>
        <div class="bg-green-100 dark:bg-green-900/30 p-4 rounded-full">
          <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
      </div>
    </div>

    <!-- Alumnos Inactivos -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm text-gray-500 dark:text-gray-400">Alumnos Inactivos</p>
          <p class="mt-2 text-4xl font-bold text-red-600 dark:text-red-400">{{ metricas.alumnos_inactivos }}</p>
          <p class="text-xs text-gray-400 mt-1">sin actividad</p>
        </div>
        <div class="bg-red-100 dark:bg-red-900/30 p-4 rounded-full">
          <svg class="w-8 h-8 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
        </div>
      </div>
    </div>

    <!-- Alertas 7+ días -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm text-gray-500 dark:text-gray-400">Alertas 7+ días</p>
          <p class="mt-2 text-4xl font-bold text-orange-600 dark:text-orange-400">{{ metricas.alumnos_inactivos_7dias?.length || 0 }}</p>
          <p class="text-xs text-gray-400 mt-1">sin entrenar</p>
        </div>
        <div class="bg-orange-100 dark:bg-orange-900/30 p-4 rounded-full">
          <svg class="w-8 h-8 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
          </svg>
        </div>
      </div>
    </div>

    <!-- Total Alumnos -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm text-gray-500 dark:text-gray-400">Total Alumnos</p>
          <p class="mt-2 text-4xl font-bold text-indigo-600 dark:text-indigo-400">{{ metricas.total_alumnos }}</p>
          <p class="text-xs text-gray-400 mt-1">asignados</p>
        </div>
        <div class="bg-indigo-100 dark:bg-indigo-900/30 p-4 rounded-full">
          <svg class="w-8 h-8 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
          </svg>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
    metricas: { type: Object, required: true },
});
</script>