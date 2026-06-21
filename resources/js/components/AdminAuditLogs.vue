<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8">
    <Breadcrumbs :items="[{ label: 'Inicio', href: '/dashboard' }, { label: 'Auditoría' }]" class="px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto" />
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

      <div v-if="cargando" class="space-y-4">
        <BaseSkeleton variant="text" :count="4" class="max-w-2xl" />
        <BaseSkeleton variant="card" :count="3" />
      </div>

      <div v-else>
        <!-- Lista de Logs (con virtual scrolling para manejar miles de registros) -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow border border-gray-200 dark:border-gray-700 overflow-hidden">
          <VirtualList
            v-if="logs.length > 0"
            :items="logs"
            :item-height="96"
            :height="640"
            key-field="id"
          >
            <template #default="{ item: log }">
              <div class="bg-white dark:bg-gray-800 p-4 border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                <div class="flex items-start justify-between gap-4">
                  <div class="flex items-start gap-3 flex-1 min-w-0">
                    <div :class="['w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0', getActionColor(log.action)]">
                      <span class="text-lg">{{ getActionIcon(log.action) }}</span>
                    </div>
                    <div class="min-w-0 flex-1">
                      <p class="font-medium text-gray-900 dark:text-white truncate">{{ log.description }}</p>
                      <p class="text-sm text-gray-500 dark:text-gray-400 truncate">
                        <span v-if="log.user">Por {{ log.user.name }}</span>
                        <span v-else class="text-gray-400">Sistema</span>
                        • {{ formatDate(log.created_at) }}
                      </p>
                      <p v-if="log.model_type" class="text-xs text-gray-400 dark:text-gray-500 mt-1 truncate">
                        Modelo: {{ getModelName(log.model_type) }} #{{ log.model_id }}
                      </p>
                    </div>
                  </div>

                  <button @click="verDetalle(log)" class="px-3 py-1 text-sm bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors flex-shrink-0">
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
            </template>
          </VirtualList>
        </div>

        <div v-if="logs.length === 0">
          <EmptyState
            emoji="📋"
            title="No hay registros de auditoría"
            :description="Object.values(filtros).some(v => v) ? 'Probá limpiando los filtros para ver todos los registros.' : 'Cuando alguien haga cambios importantes en el sistema, van a quedar registrados acá.'"
            variant="compact"
          />
        </div>

        <!-- Paginación -->
        <div class="mt-6 flex justify-center gap-2">
          <button v-if="pagination.current_page > 1" @click="fetchLogs(pagination.current_page - 1)" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600">Anterior</button>
          <span class="px-4 py-2">Página {{ pagination.current_page }} de {{ pagination.last_page }}</span>
          <button v-if="pagination.current_page < pagination.last_page" @click="fetchLogs(pagination.current_page + 1)" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600">Siguiente</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useToast } from '../composables/useToast';
import Breadcrumbs from './Breadcrumbs.vue';
import EmptyState from './EmptyState.vue';
import BaseSkeleton from './BaseSkeleton.vue';
import VirtualList from './VirtualList.vue';

const toast = useToast();
const showToast = (message, type = 'success') => toast.add(message, type);
const cargando = ref(true);
const logs = ref([]);
const acciones = ref([]);
const selectedLog = ref(null);
const pagination = ref({ current_page: 1, last_page: 1 });

const filtros = ref({ usuario: '', accion: '', modelo: '', fecha_desde: '', fecha_hasta: '' });

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