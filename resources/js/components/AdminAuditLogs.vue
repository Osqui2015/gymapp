<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8">
    <ToastNotification ref="toastRef" />
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
        <div>
          <h2 class="text-3xl font-bold text-gray-900 dark:text-white">📋 Historial de Auditoría</h2>
          <p class="text-gray-500 dark:text-gray-400 mt-1">Registro de acciones administrativas</p>
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

      <!-- Filtros -->
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 mb-6 border border-gray-200 dark:border-gray-700">
        <div class="flex flex-wrap gap-4">
          <input v-model="filtros.usuario" type="text" placeholder="Buscar por usuario..." class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-gray-200" @change="fetchLogs" />
          <select v-model="filtros.accion" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-gray-200" @change="fetchLogs">
            <option value="">Todas las acciones</option>
            <option v-for="a in acciones" :key="a" :value="a">{{ a }}</option>
          </select>
          <input v-model="filtros.fecha_desde" type="date" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-gray-200" @change="fetchLogs" />
          <input v-model="filtros.fecha_hasta" type="date" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-gray-200" @change="fetchLogs" />
        </div>
      </div>

      <div v-if="cargando" class="flex justify-center py-20">
        <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-indigo-600"></div>
      </div>

      <div v-else>
        <!-- Lista de Logs -->
        <div class="space-y-3">
          <div v-for="log in logs" :key="log.id" class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 border border-gray-200 dark:border-gray-700 hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between gap-4">
              <div class="flex items-start gap-3">
                <div :class="['w-10 h-10 rounded-full flex items-center justify-center', getActionColor(log.action)]">
                  <span class="text-lg">{{ getActionIcon(log.action) }}</span>
                </div>
                <div>
                  <p class="font-medium text-gray-900 dark:text-white">{{ log.description }}</p>
                  <p class="text-sm text-gray-500 dark:text-gray-400">
                    <span v-if="log.user">Por {{ log.user.name }}</span>
                    <span v-else class="text-gray-400">Sistema</span>
                    • {{ formatDate(log.created_at) }}
                  </p>
                  <p v-if="log.model_type" class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                    Modelo: {{ getModelName(log.model_type) }} #{{ log.model_id }}
                  </p>
                </div>
              </div>

              <button @click="verDetalle(log)" class="px-3 py-1 text-sm bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors">
                Ver detalles
              </button>
            </div>

            <!-- Detalle expandido -->
            <div v-if="selectedLog?.id === log.id" class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
              <div v-if="log.old_values || log.new_values" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div v-if="log.old_values">
                  <h4 class="text-sm font-semibold text-red-600 dark:text-red-400 mb-2">Valores Anteriores</h4>
                  <pre class="text-xs bg-red-50 dark:bg-red-900/20 p-3 rounded-lg overflow-x-auto">{{ JSON.stringify(log.old_values, null, 2) }}</pre>
                </div>
                <div v-if="log.new_values">
                  <h4 class="text-sm font-semibold text-green-600 dark:text-green-400 mb-2">Nuevos Valores</h4>
                  <pre class="text-xs bg-green-50 dark:bg-green-900/20 p-3 rounded-lg overflow-x-auto">{{ JSON.stringify(log.new_values, null, 2) }}</pre>
                </div>
              </div>
              <div class="mt-3 text-xs text-gray-400">
                IP: {{ log.ip_address || 'N/A' }} | User Agent: {{ log.user_agent || 'N/A' }}
              </div>
            </div>
          </div>
        </div>

        <div v-if="logs.length === 0" class="bg-white dark:bg-gray-800 rounded-xl shadow p-12 text-center border border-gray-200 dark:border-gray-700">
          <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
          </div>
          <p class="text-gray-500 dark:text-gray-400">No hay registros de auditoría</p>
        </div>

        <!-- Paginación -->
        <div class="mt-6 flex justify-center gap-2">
          <button v-if="pagination.current_page > 1" @click="fetchLogs(pagination.current_page - 1)" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600">Anterior</button>
          <span class="px-4 py-2">Página {{ pagination.current_page }} de {{ pagination.last_page }}</span>
          <button v-if="pagination.current_page < pagination.last_page" @click="fetchLogs(pagination.current_page + 1)" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600">Siguiente</button>
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
import { ref, onMounted } from 'vue';
import axios from 'axios';
import ToastNotification from './ToastNotification.vue';

const toastRef = ref(null);
const cargando = ref(true);
const logs = ref([]);
const acciones = ref([]);
const selectedLog = ref(null);
const pagination = ref({ current_page: 1, last_page: 1 });

const filtros = ref({ usuario: '', accion: '', modelo: '', fecha_desde: '', fecha_hasta: '' });
const toast = ref({ show: false, message: '', type: 'success' });

const showToast = (message, type = 'success') => {
  toast.value = { show: true, message, type };
  setTimeout(() => { toast.value.show = false; }, 3000);
};

const formatDate = (date) => new Date(date).toLocaleString('es-ES');

const getActionIcon = (action) => {
  const icons = { created: '➕', updated: '✏️', deleted: '🗑️', suspended: '🚫', role_changed: '👤', imported: '📥', exported: '📤', renewed: '🔄' };
  return icons[action] || '📋';
};

const getActionColor = (action) => {
  const colors = { created: 'bg-green-100 text-green-600', updated: 'bg-blue-100 text-blue-600', deleted: 'bg-red-100 text-red-600', suspended: 'bg-orange-100 text-orange-600', role_changed: 'bg-purple-100 text-purple-600', imported: 'bg-indigo-100 text-indigo-600', exported: 'bg-cyan-100 text-cyan-600', renewed: 'bg-emerald-100 text-emerald-600' };
  return colors[action] || 'bg-gray-100 text-gray-600';
};

const getModelName = (modelType) => {
  if (!modelType) return '';
  return modelType.split('\\').pop();
};

const verDetalle = (log) => {
  selectedLog.value = selectedLog.value?.id === log.id ? null : log;
};

const fetchLogs = async (page = 1) => {
  cargando.value = true;
  try {
    const params = new URLSearchParams({ page, ...filtros.value });
    const response = await axios.get(`/api/admin/audit-logs?${params}`);
    logs.value = response.data.logs.data;
    acciones.value = response.data.acciones;
    pagination.value = { current_page: response.data.logs.current_page, last_page: response.data.logs.last_page };
  } catch (error) {
    console.error('Error:', error);
    showToast('Error al cargar logs', 'error');
  } finally {
    cargando.value = false;
  }
};

onMounted(() => fetchLogs());
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
pre { font-family: monospace; }
</style>