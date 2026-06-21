<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8">
    <Breadcrumbs :items="[{ label: 'Inicio', href: '/dashboard' }, { label: 'Duplicar Rutina' }]" class="px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto" />
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
        <div>
          <h2 class="text-3xl font-bold text-gray-900 dark:text-white">Duplicar y Plantillas</h2>
          <p class="text-gray-500 dark:text-gray-400 mt-1">Crea nuevas rutinas basadas en las existentes</p>
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

      <div v-if="cargando" class="space-y-4">
        <BaseSkeleton variant="card" :count="1" />
        <BaseSkeleton variant="table" :count="4" />
      </div>

      <div v-else>
        <!-- Formulario de Duplicación -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700 mb-8">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
            </svg>
            Duplicar Rutina
          </h3>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Seleccionar Rutina Original</label>
              <select
                v-model="form.rutina_id"
                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500"
              >
                <option value="">-- Seleccionar rutina --</option>
                <option v-for="rutina in rutinas" :key="rutina.id" :value="rutina.id">
                  {{ rutina.nombre }} ({{ rutina.dias }} días)
                </option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Nombre para la Nueva Rutina</label>
              <input
                v-model="form.nombre_nuevo"
                type="text"
                placeholder="Ej: Mi Rutina Personalizada"
                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500"
              />
            </div>
          </div>

          <div v-if="rutinaSeleccionada" class="mt-4 p-4 bg-indigo-50 dark:bg-indigo-900/20 rounded-lg border border-indigo-200 dark:border-indigo-800">
            <h4 class="font-semibold text-indigo-700 dark:text-indigo-300 mb-2">Detalles de la Rutina Original:</h4>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-sm">
              <div>
                <span class="text-gray-500">Nivel:</span>
                <span class="font-medium ml-1 text-gray-900 dark:text-white">{{ rutinaSeleccionada.modalidad }}</span>
              </div>
              <div>
                <span class="text-gray-500">Modalidad:</span>
                <span class="font-medium ml-1 text-gray-900 dark:text-white">{{ rutinaSeleccionada.modalidad }}</span>
              </div>
              <div>
                <span class="text-gray-500">Días:</span>
                <span class="font-medium ml-1 text-gray-900 dark:text-white">{{ rutinaSeleccionada.dias }}</span>
              </div>
              <div>
                <span class="text-gray-500">ID:</span>
                <span class="font-medium ml-1 text-gray-900 dark:text-white">{{ rutinaSeleccionada.id }}</span>
              </div>
            </div>
          </div>

          <div class="mt-6 flex justify-end">
            <button
              @click="duplicarRutina"
              :disabled="!form.rutina_id || !form.nombre_nuevo.trim() || guardando"
              class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition-all flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed shadow-md hover:shadow-lg"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
              </svg>
              {{ guardando ? 'Duplicando...' : 'Duplicar Rutina' }}
            </button>
          </div>
        </div>

        <!-- Mis Rutinas Actuales -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden border border-gray-200 dark:border-gray-700">
          <div class="p-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Mis Rutinas Creadas</h3>
          </div>

          <div v-if="rutinas.length > 0" class="divide-y divide-gray-100 dark:divide-gray-700">
            <div v-for="rutina in rutinas" :key="rutina.id" class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors flex items-center justify-between">
              <div class="flex items-center gap-4">
                <div class="bg-indigo-100 dark:bg-indigo-900/30 w-12 h-12 rounded-lg flex items-center justify-center">
                  <span class="text-xl">🏋️</span>
                </div>
                <div>
                  <h4 class="font-semibold text-gray-900 dark:text-white">{{ rutina.nombre }}</h4>
                  <p class="text-sm text-gray-500 dark:text-gray-400">{{ rutina.dias }} días • {{ rutina.modalidad }}</p>
                </div>
              </div>
              <div class="flex gap-2">
                <button
                  @click="irARutina(rutina)"
                  class="px-3 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-medium transition-colors flex items-center gap-1"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                  Ver
                </button>
              </div>
            </div>
          </div>

          <div v-else>
            <EmptyState
              emoji="📋"
              title="Todavía no creaste rutinas"
              description="Cuando crees rutinas van a aparecer acá para que puedas duplicarlas como base para nuevas."
              cta-text="Crear mi primera rutina"
              cta-icon="M12 6v6m0 0v6m0-6h6m-6 0H6"
              @cta="$event && (window.location.href = '/rutinas/crear')"
              variant="compact"
            />
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import { useToast } from '../composables/useToast';
import Breadcrumbs from './Breadcrumbs.vue';
import EmptyState from './EmptyState.vue';
import BaseSkeleton from './BaseSkeleton.vue';

const toast = useToast();
const showToast = (message, type = 'success') => toast.add(message, type);
const cargando = ref(true);
const guardando = ref(false);
const rutinas = ref([]);

const form = ref({
  rutina_id: '',
  nombre_nuevo: '',
});

const rutinaSeleccionada = computed(() => {
  if (!form.value.rutina_id) return null;
  return rutinas.value.find(r => r.id === parseInt(form.value.rutina_id));
});

const fetchRutinas = async () => {
  try {
    const response = await axios.get('/api/trainer/mis-rutinas-complete');
    rutinas.value = response.data;
  } catch (error) {
    console.error('Error al cargar rutinas:', error);
  } finally {
    cargando.value = false;
  }
};

const duplicarRutina = async () => {
  if (!form.value.rutina_id || !form.value.nombre_nuevo.trim()) return;

  guardando.value = true;
  try {
    await axios.post('/api/trainer/duplicar-rutina', {
      rutina_id: form.value.rutina_id,
      nombre_nuevo: form.value.nombre_nuevo,
    });

    form.value = { rutina_id: '', nombre_nuevo: '' };
    await fetchRutinas();
    showToast('Rutina duplicada correctamente');
  } catch (error) {
    console.error('Error al duplicar:', error);
    showToast('No se pudo duplicar la rutina', 'error');
  } finally {
    guardando.value = false;
  }
};

const irARutina = (rutina) => {
  window.location.href = `/rutinas?rutina=${encodeURIComponent(rutina.nombre)}`;
};

onMounted(() => {
  fetchRutinas();
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