<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8 px-4">
    <div class="max-w-3xl mx-auto">
      <!-- Header público -->
      <div class="text-center mb-8">
        <a href="/" class="inline-flex items-center gap-2 mb-4">
          <div class="w-10 h-10 bg-gradient-to-br from-indigo-600 to-purple-600 rounded-lg flex items-center justify-center">
            <span class="text-white font-bold">G</span>
          </div>
          <span class="text-2xl font-bold text-gray-900 dark:text-white">GymApp</span>
        </a>
        <p class="text-sm text-gray-500 dark:text-gray-400">Rutina compartida públicamente</p>
      </div>

      <div v-if="loading" class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-12 text-center">
        <svg class="animate-spin w-10 h-10 mx-auto text-indigo-600 mb-3" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
        </svg>
        <p class="text-gray-600 dark:text-gray-400">Cargando...</p>
      </div>

      <div v-else-if="error" class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-8 text-center border border-red-200 dark:border-red-800">
        <div class="text-5xl mb-3">😔</div>
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Rutina no encontrada</h2>
        <p class="text-gray-600 dark:text-gray-400 mb-4">{{ error }}</p>
        <a href="/" class="inline-block px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium">
          Volver al inicio
        </a>
      </div>

      <div v-else-if="rutina">
        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-2xl shadow-2xl p-8 mb-6 text-center text-white">
          <div class="text-5xl mb-3">📋</div>
          <h1 class="text-3xl md:text-4xl font-bold mb-2">{{ rutina.nivel }} {{ rutina.modalidad }}</h1>
          <p v-if="rutina.creador" class="text-indigo-100">
            Compartida por <strong>{{ rutina.creador.name }}</strong> (@{{ rutina.creador.nick }})
          </p>
        </div>

        <div v-for="(ejercicios, dia) in rutina.dias" :key="dia" class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 mb-4 border border-gray-200 dark:border-gray-700">
          <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z" />
            </svg>
            {{ dia }}
            <span class="ml-auto text-xs font-medium text-gray-500 bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded-full">
              {{ ejercicios.length }} ejercicios
            </span>
          </h2>
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="border-b-2 border-gray-200 dark:border-gray-700">
                  <th class="px-4 py-2 text-left font-semibold text-gray-600 dark:text-gray-400">Ejercicio</th>
                  <th class="px-4 py-2 text-center font-semibold text-gray-600 dark:text-gray-400">Series</th>
                  <th class="px-4 py-2 text-center font-semibold text-gray-600 dark:text-gray-400">Reps</th>
                  <th class="px-4 py-2 text-center font-semibold text-gray-600 dark:text-gray-400">Descanso</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                <tr v-for="ej in ejercicios" :key="ej.id">
                  <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                    {{ ej.ejercicio_nombre }}
                    <span v-if="ej.superserie_grupo" class="ml-2 text-xs font-semibold bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 px-2 py-0.5 rounded-full">
                      SS {{ ej.superserie_grupo }}
                    </span>
                  </td>
                  <td class="px-4 py-3 text-center">
                    <span class="px-2 py-1 bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300 rounded font-semibold">{{ ej.series }}</span>
                  </td>
                  <td class="px-4 py-3 text-center text-gray-700 dark:text-gray-300">{{ ej.reps_min }} - {{ ej.reps_max }}</td>
                  <td class="px-4 py-3 text-center text-orange-600 dark:text-orange-400 font-medium">{{ ej.descanso_min }} min</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="bg-indigo-50 dark:bg-indigo-950/30 rounded-2xl p-6 text-center border border-indigo-200 dark:border-indigo-800">
          <p class="text-sm text-indigo-700 dark:text-indigo-300 mb-3">¿Querés esta rutina en tu cuenta?</p>
          <a
            v-if="authed"
            href="/rutinas"
            class="inline-block px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium shadow-md transition-colors"
          >
            Ir a Mis Rutinas
          </a>
          <a
            v-else
            href="/login"
            class="inline-block px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium shadow-md transition-colors"
          >
            Iniciar sesión para guardar
          </a>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const loading = ref(true);
const error = ref(null);
const rutina = ref(null);
const authed = ref(false);

onMounted(async () => {
    const token = window.__rutinaToken;
    authed.value = document.querySelector('meta[name="csrf-token"]') && document.cookie.includes('gymapp_session');
    if (!token) {
        error.value = 'Token inválido.';
        loading.value = false;
        return;
    }
    try {
        const response = await axios.get(`/api/rutinas/publica/${token}`);
        rutina.value = response.data;
    } catch (err) {
        error.value = err.response?.data?.error || 'No se pudo cargar la rutina.';
    } finally {
        loading.value = false;
    }
});
</script>