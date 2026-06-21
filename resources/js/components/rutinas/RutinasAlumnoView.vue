<template>
  <div>
    <!-- Alumno CON rutina asignada -->
    <div v-if="userRutina" class="mb-8">
      <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-2xl shadow-2xl p-6 md:p-8 mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
          <div>
            <p class="text-xs md:text-sm uppercase tracking-[0.2em] text-indigo-200 mb-2">Tu rutina asignada</p>
            <h2 class="text-2xl md:text-3xl font-bold text-white">
              {{ userRutina.nivel }} {{ userRutina.modalidad }}
            </h2>
            <p class="mt-2 text-indigo-200">Día actual: {{ userRutina.dia_actual }}</p>
          </div>
          <a
            href="/dashboard"
            class="inline-flex items-center justify-center bg-white text-indigo-600 px-6 py-3 rounded-xl font-semibold hover:bg-indigo-50 transition-colors shadow-lg"
          >
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Comenzar Entrenamiento
          </a>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Información de tu rutina</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
            <p class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ userRutina.nivel }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">Nivel</p>
          </div>
          <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
            <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ userRutina.modalidad }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">Frecuencia</p>
          </div>
          <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
            <p class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ userRutina.dia_actual }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">Progreso</p>
          </div>
          <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
            <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">Personal</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">Asignada por</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Alumno SIN rutina -->
    <div v-else class="text-center py-16">
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8 max-w-md mx-auto border border-gray-200 dark:border-gray-700">
        <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-2">Rutina Pendiente</h3>
        <p class="text-gray-500 dark:text-gray-400 mb-6">Tu trainer aún no te ha asignado una rutina de entrenamiento.</p>
        <p class="text-sm text-gray-400 dark:text-gray-500">Contacta a tu trainer para que configure tu plan de entrenamiento.</p>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
    userRutina: { type: Object, default: null },
});
</script>