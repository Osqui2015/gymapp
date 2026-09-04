<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8">
    <Breadcrumbs :items="[{ label: 'Inicio', href: '/dashboard' }, { label: 'Membresías' }]" class="px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto" />
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
        <div>
          <h2 class="text-3xl font-bold text-gray-900 dark:text-white">💳 Membresías</h2>
          <p class="text-gray-500 dark:text-gray-400 mt-1">Gestión de suscripciones y pagos</p>
        </div>
        <div class="flex gap-3">
          <a
            href="/dashboard"
            class="px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg font-medium transition-all flex items-center gap-2"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Volver
          </a>
          <button
            @click="mostrarModalCrear = true"
            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition-all flex items-center gap-2"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            Nueva Membresía
          </button>
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 border border-gray-200 dark:border-gray-700">
          <p class="text-xs text-gray-500 dark:text-gray-400">Total</p>
          <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.total }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 border border-green-200 dark:border-green-800">
          <p class="text-xs text-green-600 dark:text-green-400">Activas</p>
          <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ stats.activas }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 border border-yellow-200 dark:border-yellow-800">
          <p class="text-xs text-yellow-600 dark:text-yellow-400">Por Vencer</p>
          <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ stats.por_vencer }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 border border-red-200 dark:border-red-800">
          <p class="text-xs text-red-600 dark:text-red-400">Vencidas</p>
          <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ stats.vencidas }}</p>
        </div>
      </div>

      <!-- Filtros -->
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 mb-6 border border-gray-200 dark:border-gray-700">
        <div class="flex flex-wrap gap-4">
          <input
            v-model="filtros.buscar"
            type="text"
            placeholder="Buscar usuario..."
            class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500"
            @change="fetchMembresias"
          />
          <select
            v-model="filtros.estado"
            class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500"
            @change="fetchMembresias"
          >
            <option value="">Todos los estados</option>
            <option value="activo">Activo</option>
            <option value="por_vencer">Por Vencer</option>
            <option value="vencido">Vencido</option>
            <option value="cancelado">Cancelado</option>
          </select>
        </div>
      </div>

      <div v-if="cargando" class="space-y-4">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <BaseSkeleton variant="stat-card" :count="4" />
        </div>
        <BaseSkeleton variant="card" :count="5" />
      </div>

      <div v-else>
        <!-- Tabla de Membresías (responsive: tabla en md+, cards en mobile) -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden border border-gray-200 dark:border-gray-700">
          <!-- Desktop: tabla tradicional -->
          <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="bg-gray-50 dark:bg-gray-900">
                  <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">Usuario</th>
                  <th class="px-4 py-3 text-center font-semibold text-gray-600 dark:text-gray-400">Plan</th>
                  <th class="px-4 py-3 text-center font-semibold text-gray-600 dark:text-gray-400">Precio</th>
                  <th class="px-4 py-3 text-center font-semibold text-gray-600 dark:text-gray-400">Inicio</th>
                  <th class="px-4 py-3 text-center font-semibold text-gray-600 dark:text-gray-400">Vencimiento</th>
                  <th class="px-4 py-3 text-center font-semibold text-gray-600 dark:text-gray-400">Estado</th>
                  <th class="px-4 py-3 text-center font-semibold text-gray-600 dark:text-gray-400">Acciones</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                <tr v-for="m in membresias" :key="m.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                  <td class="px-4 py-3">
                    <div class="flex items-center gap-3">
                      <div :class="['w-10 h-10 rounded-full flex items-center justify-center font-bold', getEstadoColor(m.estado)]">
                        {{ m.user?.name?.charAt(0) || '?' }}
                      </div>
                      <div>
                        <p class="font-medium text-gray-900 dark:text-white">{{ m.user?.name }}</p>
                        <p class="text-xs text-gray-500">{{ m.user?.email }}</p>
                      </div>
                    </div>
                  </td>
                  <td class="px-4 py-3 text-center capitalize">{{ m.tipo_plan }}</td>
                  <td class="px-4 py-3 text-center font-semibold">${{ m.precio }}</td>
                  <td class="px-4 py-3 text-center text-gray-600 dark:text-gray-300">{{ formatDate(m.fecha_inicio) }}</td>
                  <td class="px-4 py-3 text-center">
                    <span :class="getDiasClass(m)">
                      {{ formatDate(m.fecha_vencimiento) }}
                      <span v-if="m.estado !== 'cancelado'" class="text-xs">({{ getDiasRestantes(m) }} días)</span>
                    </span>
                  </td>
                  <td class="px-4 py-3 text-center">
                    <span :class="['px-3 py-1 rounded-full text-xs font-semibold', getEstadoBadge(m.estado)]">
                      {{ m.estado.replace('_', ' ') }}
                    </span>
                  </td>
                  <td class="px-4 py-3 text-center">
                    <div class="flex justify-center gap-2">
                      <button @click="editarMembresia(m)" class="p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg" title="Editar" aria-label="Editar membresía">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                      </button>
                      <button @click="renovarMembresia(m)" class="p-2 text-green-600 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-lg" title="Renovar" aria-label="Renovar membresía">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Mobile: cards -->
          <div class="md:hidden divide-y divide-gray-100 dark:divide-gray-700">
            <div
              v-for="m in membresias"
              :key="m.id"
              class="p-4 space-y-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
            >
              <!-- Header: avatar + nombre + badge estado -->
              <div class="flex items-start justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0 flex-1">
                  <div :class="['w-11 h-11 rounded-full flex items-center justify-center font-bold flex-shrink-0', getEstadoColor(m.estado)]">
                    {{ m.user?.name?.charAt(0) || '?' }}
                  </div>
                  <div class="min-w-0 flex-1">
                    <p class="font-semibold text-gray-900 dark:text-white truncate">{{ m.user?.name }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ m.user?.email }}</p>
                  </div>
                </div>
                <span :class="['px-2.5 py-1 rounded-full text-xs font-semibold whitespace-nowrap', getEstadoBadge(m.estado)]">
                  {{ m.estado.replace('_', ' ') }}
                </span>
              </div>

              <!-- Detalle: plan + precio -->
              <div class="flex items-center justify-between text-sm">
                <span class="text-gray-500 dark:text-gray-400">Plan</span>
                <span class="font-medium text-gray-900 dark:text-white capitalize">{{ m.tipo_plan }} · <span class="font-bold">${{ m.precio }}</span></span>
              </div>

              <!-- Fechas -->
              <div class="flex items-center justify-between text-sm">
                <span class="text-gray-500 dark:text-gray-400">Inicio</span>
                <span class="text-gray-700 dark:text-gray-300">{{ formatDate(m.fecha_inicio) }}</span>
              </div>
              <div class="flex items-center justify-between text-sm">
                <span class="text-gray-500 dark:text-gray-400">Vencimiento</span>
                <span :class="getDiasClass(m)" class="text-right">
                  {{ formatDate(m.fecha_vencimiento) }}
                  <span v-if="m.estado !== 'cancelado'" class="text-xs block">{{ getDiasRestantes(m) }} días restantes</span>
                </span>
              </div>

              <!-- Acciones -->
              <div class="flex gap-2 pt-2 border-t border-gray-100 dark:border-gray-700">
                <button
                  @click="editarMembresia(m)"
                  class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 rounded-lg text-sm font-medium hover:bg-blue-100 dark:hover:bg-blue-900/30 transition-colors"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                  Editar
                </button>
                <button
                  @click="renovarMembresia(m)"
                  class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 rounded-lg text-sm font-medium hover:bg-green-100 dark:hover:bg-green-900/30 transition-colors"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                  </svg>
                  Renovar
                </button>
              </div>
            </div>
          </div>

          <EmptyState
            v-if="membresias.length === 0"
            emoji="💳"
            title="No hay membresías registradas"
            description="Cuando un usuario compre una membresía, va a aparecer acá para su gestión."
            variant="compact"
          />
        </div>

        <!-- Paginación -->
        <div class="mt-4 flex justify-center">
          <button
            v-if="pagination.current_page > 1"
            @click="fetchMembresias(pagination.current_page - 1)"
            class="px-4 py-2 mx-1 bg-gray-200 dark:bg-gray-700 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600"
          >
            Anterior
          </button>
          <span class="px-4 py-2 mx-1">Página {{ pagination.current_page }} de {{ pagination.last_page }}</span>
          <button
            v-if="pagination.current_page < pagination.last_page"
            @click="fetchMembresias(pagination.current_page + 1)"
            class="px-4 py-2 mx-1 bg-gray-200 dark:bg-gray-700 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600"
          >
            Siguiente
          </button>
        </div>
      </div>
    </div>

    <!-- Modal Crear/Editar -->
    <transition name="fade">
      <div v-if="mostrarModalCrear" class="fixed inset-0 z-50 overflow-y-auto" @click.self="mostrarModalCrear = false">
        <div class="flex items-center justify-center min-h-screen px-4">
          <div class="fixed inset-0 transition-opacity bg-gray-900 bg-opacity-75" @click="mostrarModalCrear = false"></div>
          <div ref="modalRef" class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-2xl text-left overflow-hidden shadow-xl transform sm:align-middle sm:max-w-lg w-full" role="dialog" aria-modal="true">
            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4 flex items-center justify-between">
              <h3 class="text-xl font-bold text-white">{{ editingId ? 'Editar' : 'Nueva' }} Membresía</h3>
              <button @click="mostrarModalCrear = false" class="text-white/80 hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <form @submit.prevent="guardarMembresia" class="p-6 space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Usuario *</label>
                <select v-model="form.user_id" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-gray-200">
                  <option value="">-- Seleccionar usuario --</option>
                  <option v-for="u in usuariosSinMembresia" :key="u.id" :value="u.id">{{ u.name }} ({{ u.email }})</option>
                </select>
              </div>

              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tipo de Plan *</label>
                  <select v-model="form.tipo_plan" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-gray-200">
                    <option value="mensual">Mensual</option>
                    <option value="trimestral">Trimestral</option>
                    <option value="semestral">Semestral</option>
                    <option value="anual">Anual</option>
                  </select>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Precio *</label>
                  <input v-model.number="form.precio" type="number" step="0.01" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-gray-200" />
                </div>
              </div>

              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fecha Inicio *</label>
                  <input v-model="form.fecha_inicio" type="date" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-gray-200" />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fecha Vencimiento *</label>
                  <input v-model="form.fecha_vencimiento" type="date" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-gray-200" />
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Método de Pago</label>
                <input v-model="form.metodo_pago" type="text" placeholder="Efectivo, Transferencia, Tarjeta..." class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-gray-200" />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notas</label>
                <textarea v-model="form.notas" rows="2" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-gray-200"></textarea>
              </div>

              <div class="flex justify-end gap-3 pt-4">
                <button type="button" @click="mostrarModalCrear = false" class="px-6 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-lg font-medium">Cancelar</button>
                <button type="submit" :disabled="guardando" class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium disabled:opacity-50">
                  {{ guardando ? 'Guardando...' : 'Guardar' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';
import EmptyState from './EmptyState.vue';
import { useToast } from '../composables/useToast';
import { useDebounce } from '../composables/useDebounce';
import { cachedAxiosGet, invalidateApiCache } from '../composables/useApiCache';
import Breadcrumbs from './Breadcrumbs.vue';
import { useFocusTrap } from '../composables/useFocusTrap';
import BaseSkeleton from './BaseSkeleton.vue';
import { useFormatters } from '@/composables/useFormatters';

const { formatDateMedium } = useFormatters();

const toast = useToast();
const showToast = (message, type = 'success') => toast.add(message, type);
const cargando = ref(true);
const guardando = ref(false);
const membresias = ref([]);
const usuariosSinMembresia = ref([]);
const mostrarModalCrear = ref(false);
const modalRef = ref(null);
useFocusTrap(modalRef, { when: mostrarModalCrear });
const editingId = ref(null);
const pagination = ref({ current_page: 1, last_page: 1 });

const stats = ref({ total: 0, activas: 0, por_vencer: 0, vencidas: 0 });

const filtros = ref({ buscar: '', estado: '' });

const form = ref({
  user_id: '',
  tipo_plan: 'mensual',
  precio: 0,
  fecha_inicio: '',
  fecha_vencimiento: '',
  metodo_pago: '',
  notas: '',
});

const formatDate = (date) => {
  if (!date) return '';
  return formatDateMedium(date);
};

const getDiasRestantes = (m) => {
  const hoy = new Date();
  const venc = new Date(m.fecha_vencimiento);
  return Math.ceil((venc - hoy) / (1000 * 60 * 60 * 24));
};

const getDiasClass = (m) => {
  const dias = getDiasRestantes(m);
  if (dias < 0) return 'text-red-600 dark:text-red-400 font-semibold';
  if (dias <= 7) return 'text-yellow-600 dark:text-yellow-400 font-semibold';
  return 'text-gray-600 dark:text-gray-300';
};

const getEstadoColor = (estado) => {
  return { activo: 'bg-green-100 text-green-600', por_vencer: 'bg-yellow-100 text-yellow-600', vencido: 'bg-red-100 text-red-600', cancelado: 'bg-gray-100 text-gray-600' }[estado] || 'bg-gray-100 text-gray-600';
};

const getEstadoBadge = (estado) => {
  return { activo: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300', por_vencer: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300', vencido: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300', cancelado: 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300' }[estado] || '';
};

// Debounce para la búsqueda (no triggear request en cada keystroke)
const debouncedSearch = useDebounce(() => fetchMembresias(1), 300);
watch(() => filtros.value.buscar, () => debouncedSearch());

const fetchMembresias = async (page = 1) => {
  try {
    const params = new URLSearchParams({ page, estado: filtros.value.estado, buscar: filtros.value.buscar });
    const response = await cachedAxiosGet(`/api/admin/membresias?${params}`, {}, { ttl: 30_000 });
    membresias.value = response.data.membresias.data;
    stats.value = response.data.stats;
    pagination.value = { current_page: response.data.membresias.current_page, last_page: response.data.membresias.last_page };
  } catch (error) {
    console.error('Error:', error);
    showToast('Error al cargar membresías', 'error');
  } finally {
    cargando.value = false;
  }
};

const fetchUsuariosSinMembresia = async () => {
  try {
    const response = await cachedAxiosGet('/api/admin/usuarios-sin-membresia', {}, { ttl: 60_000 });
    usuariosSinMembresia.value = response.data;
  } catch (error) {
    console.error('Error:', error);
  }
};

const guardarMembresia = async () => {
  guardando.value = true;
  try {
    if (editingId.value) {
      await axios.put(`/api/admin/membresias/${editingId.value}`, form.value);
      showToast('Membresía actualizada');
    } else {
      await axios.post('/api/admin/membresias', form.value);
      showToast('Membresía creada');
    }
    invalidateApiCache('/api/admin/membresias');
    mostrarModalCrear.value = false;
    editingId.value = null;
    form.value = { user_id: '', tipo_plan: 'mensual', precio: 0, fecha_inicio: '', fecha_vencimiento: '', metodo_pago: '', notas: '' };
    await fetchMembresias();
    await fetchUsuariosSinMembresia();
  } catch (error) {
    console.error('Error:', error);
    showToast('Error al guardar', 'error');
  } finally {
    guardando.value = false;
  }
};

const editarMembresia = (m) => {
  editingId.value = m.id;
  form.value = { user_id: m.user_id, tipo_plan: m.tipo_plan, precio: m.precio, fecha_inicio: m.fecha_inicio, fecha_vencimiento: m.fecha_vencimiento, metodo_pago: m.metodo_pago || '', notas: m.notas || '' };
  mostrarModalCrear.value = true;
};

const renovarMembresia = async (m) => {
  if (!confirm('¿Renovar esta membresía?')) return;
  try {
    await axios.post(`/api/admin/membresias/${m.id}/renew`);
    invalidateApiCache('/api/admin/membresias');
    showToast('Membresía renovada');
    fetchMembresias();
  } catch (error) {
    showToast('Error al renovar', 'error');
  }
};

onMounted(() => {
  fetchMembresias();
  fetchUsuariosSinMembresia();
});
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>