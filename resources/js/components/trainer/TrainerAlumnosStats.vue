<template>
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm text-gray-500 dark:text-gray-400">Total Alumnos</p>
          <p class="mt-2 text-3xl font-bold text-indigo-600 dark:text-indigo-400">{{ totalAlumnos }}</p>
        </div>
        <div class="bg-indigo-100 dark:bg-indigo-900/30 p-3 rounded-full">
          <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
          </svg>
        </div>
      </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm text-gray-500 dark:text-gray-400">Con Rutina</p>
          <p class="mt-2 text-3xl font-bold text-green-600 dark:text-green-400">{{ alumnosConRutina }}</p>
        </div>
        <div class="bg-green-100 dark:bg-green-900/30 p-3 rounded-full">
          <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
      </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm text-gray-500 dark:text-gray-400">Sin Rutina</p>
          <p class="mt-2 text-3xl font-bold text-amber-600 dark:text-amber-400">{{ totalAlumnos - alumnosConRutina }}</p>
        </div>
        <div class="bg-amber-100 dark:bg-amber-900/30 p-3 rounded-full">
          <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
    totalAlumnos: { type: Number, required: true },
    alumnosConRutina: { type: Number, required: true },
});
</script>