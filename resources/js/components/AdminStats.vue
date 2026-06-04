<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8">
    <ToastNotification ref="toastRef" />
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
        <div>
          <h2 class="text-3xl font-bold text-gray-900 dark:text-white">📊 Panel de Estadísticas</h2>
          <p class="text-gray-500 dark:text-gray-400 mt-1">Métricas generales del gimnasio</p>
        </div>
        <a
          href="/dashboard"
          class="px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg font-medium transition-all flex items-center gap-2"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          Volver
        </a>
      </div>

      <div v-if="cargando" class="flex justify-center py-20">
        <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-indigo-600"></div>
      </div>

      <div v-else>
        <!-- Resumen Cards -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
          <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 border border-gray-200 dark:border-gray-700">
            <p class="text-xs text-gray-500 dark:text-gray-400">Total Usuarios</p>
            <p class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ stats.resumen.total_usuarios }}</p>
          </div>
          <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 border border-gray-200 dark:border-gray-700">
            <p class="text-xs text-gray-500 dark:text-gray-400">Activos</p>
            <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ stats.resumen.usuarios_activos }}</p>
          </div>
          <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 border border-gray-200 dark:border-gray-700">
            <p class="text-xs text-gray-500 dark:text-gray-400">Trainers</p>
            <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ stats.resumen.total_trainers }}</p>
          </div>
          <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 border border-gray-200 dark:border-gray-700">
            <p class="text-xs text-gray-500 dark:text-gray-400">Entrenamientos</p>
            <p class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ stats.resumen.total_entrenamientos }}</p>
          </div>
          <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 border border-gray-200 dark:border-gray-700">
            <p class="text-xs text-gray-500 dark:text-gray-400">Hoy</p>
            <p class="text-2xl font-bold text-cyan-600 dark:text-cyan-400">{{ stats.resumen.entrenamientos_hoy }}</p>
          </div>
          <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 border border-gray-200 dark:border-gray-700">
            <p class="text-xs text-gray-500 dark:text-gray-400">Nuevos (Mes)</p>
            <p class="text-2xl font-bold text-pink-600 dark:text-pink-400">{{ stats.resumen.usuarios_nuevos_mes }}</p>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
          <!-- Gráfico de Usuarios por Mes -->
          <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">📈 Usuarios Registrados por Mes</h3>
            <div class="space-y-3">
              <div v-for="mes in stats.usuarios_por_mes" :key="mes.mes" class="flex items-center gap-3">
                <span class="text-sm text-gray-500 dark:text-gray-400 w-20">{{ mes.mes }}</span>
                <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-6 overflow-hidden">
                  <div 
                    class="h-full bg-gradient-to-r from-indigo-500 to-purple-500 rounded-full transition-all duration-500"
                    :style="{ width: `${(mes.total / maxUsuarios) * 100}%` }"
                  ></div>
                </div>
                <span class="text-sm font-semibold text-gray-700 dark:text-gray-300 w-10 text-right">{{ mes.total }}</span>
              </div>
            </div>
          </div>

          <!-- Horas Pico -->
          <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">⏰ Horas Pico de Entrenamiento</h3>
            <div class="grid grid-cols-6 gap-2">
              <div v-for="hora in stats.horas_pico" :key="hora.hora" class="text-center">
                <div 
                  class="rounded-lg p-2 mb-1 transition-all duration-300"
                  :class="hora.total > maxHorasPico * 0.7 ? 'bg-red-100 dark:bg-red-900/30' : 
                         hora.total > maxHorasPico * 0.4 ? 'bg-yellow-100 dark:bg-yellow-900/30' : 
                         'bg-gray-100 dark:bg-gray-700'"
                >
                  <span class="text-xs font-bold">{{ hora.total }}</span>
                </div>
                <span class="text-[10px] text-gray-500 dark:text-gray-400">{{ hora.label }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Ejercicios Populares -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden border border-gray-200 dark:border-gray-700">
          <div class="p-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">🏋️ Ejercicios Más Populares</h3>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="bg-gray-50 dark:bg-gray-900">
                  <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">#</th>
                  <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">Ejercicio</th>
                  <th class="px-4 py-3 text-center font-semibold text-gray-600 dark:text-gray-400">Veces Usado</th>
                  <th class="px-4 py-3 text-center font-semibold text-gray-600 dark:text-gray-400">Peso Prom.</th>
                  <th class="px-4 py-3 text-center font-semibold text-gray-600 dark:text-gray-400">Reps Totales</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                <tr v-for="(ej, index) in stats.ejercicios_populares" :key="ej.ejercicio" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                  <td class="px-4 py-3 text-gray-400">{{ index + 1 }}</td>
                  <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ ej.ejercicio }}</td>
                  <td class="px-4 py-3 text-center">
                    <span class="px-2 py-1 bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400 rounded-full text-xs font-semibold">{{ ej.veces_usado }}</span>
                  </td>
                  <td class="px-4 py-3 text-center text-gray-600 dark:text-gray-300">{{ ej.peso_promedio }} kg</td>
                  <td class="px-4 py-3 text-center text-gray-600 dark:text-gray-300">{{ formatNumber(ej.reps_totales) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <div v-if="stats.ejercicios_populares.length === 0" class="p-8 text-center text-gray-500 dark:text-gray-400">
            No hay datos de ejercicios populares aún
          </div>
        </div>
      </div>
    </div>

    <transition name="fade">
      <div v-if="toast.show" :class="['fixed bottom-4 right-4 px-6 py-3 rounded-lg shadow-lg text-white z-50', toast.type === 'success' ? 'bg-green-600' : 'bg-red-600']">
        {{ toast.message }}
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import ToastNotification from './ToastNotification.vue';

const toastRef = ref(null);
const cargando = ref(true);
const stats = ref({
  usuarios_por_mes: [],
  horas_pico: [],
  ejercicios_populares: [],
  resumen: {
    total_usuarios: 0,
    usuarios_activos: 0,
    total_trainers: 0,
    total_entrenamientos: 0,
    ejercicios_totales: 0,
    entrenamientos_hoy: 0,
    usuarios_nuevos_mes: 0,
  },
});

const toast = ref({ show: false, message: '', type: 'success' });

const maxUsuarios = computed(() => {
  return Math.max(...stats.value.usuarios_por_mes.map(m => m.total), 1);
});

const maxHorasPico = computed(() => {
  return Math.max(...stats.value.horas_pico.map(h => h.total), 1);
});

const showToast = (message, type = 'success') => {
  toast.value = { show: true, message, type };
  setTimeout(() => {
    toast.value.show = false;
  }, 3000);
};

const formatNumber = (num) => {
  return num?.toLocaleString() || '0';
};

const fetchStats = async () => {
  try {
    const response = await axios.get('/api/admin/estadisticas');
    stats.value = response.data;
  } catch (error) {
    console.error('Error al cargar estadísticas:', error);
    showToast('Error al cargar estadísticas', 'error');
  } finally {
    cargando.value = false;
  }
};

onMounted(() => {
  fetchStats();
});
</script>

<style scoped>
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
</style>