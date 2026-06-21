<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8">
    <Breadcrumbs :items="[{ label: 'Inicio', href: '/dashboard' }, { label: 'Mis Ejercicios' }]" class="px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto" />
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
        <div>
          <h2 class="text-3xl font-bold text-gray-900 dark:text-white">Ejercicios Privados</h2>
          <p class="text-gray-500 dark:text-gray-400 mt-1">Crea ejercicios solo visibles para ti y tus alumnos</p>
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
            Nuevo Ejercicio
          </button>
        </div>
      </div>

      <div v-if="cargando" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <BaseSkeleton variant="card" :count="6" />
      </div>

      <div v-else>
        <!-- Lista de Ejercicios por Grupo Muscular -->
        <div v-if="ejerciciosAgrupados.length > 0" class="space-y-6">
          <div v-for="grupo in ejerciciosAgrupados" :key="grupo.nombre" class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden border border-gray-200 dark:border-gray-700">
            <div class="px-6 py-4 bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-800 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                <span class="text-2xl">{{ grupo.icono }}</span>
                {{ grupo.nombre }}
              </h3>
              <span class="px-3 py-1 bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300 rounded-full text-sm font-semibold">
                {{ grupo.ejercicios.length }} ejercicios
              </span>
            </div>

            <div class="divide-y divide-gray-100 dark:divide-gray-700">
              <div v-for="ejercicio in grupo.ejercicios" :key="ejercicio.id" class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors flex items-center justify-between">
                <div class="flex items-center gap-4">
                  <div class="bg-indigo-100 dark:bg-indigo-900/30 w-12 h-12 rounded-lg flex items-center justify-center">
                    <span class="text-xl">💪</span>
                  </div>
                  <div>
                    <h4 class="font-semibold text-gray-900 dark:text-white">{{ ejercicio.nombre }}</h4>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ ejercicio.equipamiento || 'Sin equipamiento' }}</p>
                    <p v-if="ejercicio.descripcion" class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ ejercicio.descripcion }}</p>
                  </div>
                </div>
                <button
                  @click="eliminarEjercicio(ejercicio)"
                  class="p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                  title="Eliminar ejercicio"
                  :aria-label="`Eliminar ejercicio ${ejercicio.nombre}`"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Estado vacío -->
        <div v-else>
          <EmptyState
            emoji="🏋️"
            title="No tenés ejercicios privados"
            description="Creá ejercicios personalizados para usar en tus rutinas. Van a ser privados para vos y tus alumnos."
            cta-text="Crear mi primer ejercicio"
            cta-icon="M12 6v6m0 0v6m0-6h6m-6 0H6"
            @cta="mostrarModalCrear = true"
            variant="large"
          />
        </div>
      </div>
    </div>

    <!-- Modal Crear Ejercicio -->
    <transition name="fade">
      <div v-if="mostrarModalCrear" class="fixed inset-0 z-50 overflow-y-auto" @click.self="mostrarModalCrear = false">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
          <div class="fixed inset-0 transition-opacity bg-gray-900 bg-opacity-75" @click="mostrarModalCrear = false"></div>

          <div ref="modalRef" class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full" role="dialog" aria-modal="true">
            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4 flex items-center justify-between">
              <h3 class="text-xl font-bold text-white">Nuevo Ejercicio Privado</h3>
              <button @click="mostrarModalCrear = false" class="text-white/80 hover:text-white transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <form @submit.prevent="crearEjercicio" class="p-6 space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nombre del Ejercicio *</label>
                <input
                  v-model="form.nombre"
                  type="text"
                  required
                  placeholder="Ej: Press de Hombros con Mancuernas"
                  class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Grupo Muscular *</label>
                <select
                  v-model="form.grupo_muscular"
                  required
                  class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500"
                >
                  <option value="">-- Seleccionar --</option>
                  <option v-for="grupo in gruposMusculares" :key="grupo" :value="grupo">{{ grupo }}</option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Equipamiento</label>
                <input
                  v-model="form.equipamiento"
                  type="text"
                  placeholder="Ej: Mancuernas, Barra, Máquina..."
                  class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Descripción (opcional)</label>
                <textarea
                  v-model="form.descripcion"
                  rows="3"
                  placeholder="Notas sobre técnica,注意事项..."
                  class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500"
                ></textarea>
              </div>

              <div class="flex justify-end gap-3 pt-4">
                <button
                  type="button"
                  @click="mostrarModalCrear = false"
                  class="px-6 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-lg font-medium hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors"
                >
                  Cancelar
                </button>
                <button
                  type="submit"
                  :disabled="guardando"
                  class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition-colors disabled:opacity-50"
                >
                  {{ guardando ? 'Guardando...' : 'Crear Ejercicio' }}
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
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import { useToast } from '../composables/useToast';
import { useUndoable } from '../composables/useUndoable';
import { useFocusTrap } from '../composables/useFocusTrap';
import Breadcrumbs from './Breadcrumbs.vue';
import EmptyState from './EmptyState.vue';
import BaseSkeleton from './BaseSkeleton.vue';

const toast = useToast();
const showToast = (message, type = 'success') => toast.add(message, type);
const cargando = ref(true);
const ejercicios = ref([]);
const mostrarModalCrear = ref(false);
const modalRef = ref(null);
useFocusTrap(modalRef, { when: mostrarModalCrear });
const guardando = ref(false);

const form = ref({
  nombre: '',
  grupo_muscular: '',
  equipamiento: '',
  descripcion: '',
});

const gruposMusculares = [
  'Pecho',
  'Espalda',
  'Hombros',
  'Bíceps',
  'Tríceps',
  'Antebrazo',
  'Cuádriceps',
  'Isquiotibiales',
  'Glúteos',
  'Pantorrillas',
  'Abdomen',
  'Core',
  'Full Body',
];

const ejerciciosAgrupados = computed(() => {
  const grupos = {};
  const iconoMap = {
    'Pecho': '💪',
    'Espalda': '🏋️',
    'Hombros': '🎯',
    'Bíceps': '💪',
    'Tríceps': '💪',
    'Antebrazo': '🤜',
    'Cuádriceps': '🦵',
    'Isquiotibiales': '🦵',
    'Glúteos': '🍑',
    'Pantorrillas': '🦶',
    'Abdomen': '🔥',
    'Core': '⚡',
    'Full Body': '🏃',
  };

  ejercicios.value.forEach(ej => {
    if (!grupos[ej.grupo_muscular]) {
      grupos[ej.grupo_muscular] = {
        nombre: ej.grupo_muscular,
        icono: iconoMap[ej.grupo_muscular] || '💪',
        ejercicios: [],
      };
    }
    grupos[ej.grupo_muscular].ejercicios.push(ej);
  });

  return Object.values(grupos);
});

const fetchEjercicios = async () => {
  try {
    const response = await axios.get('/api/trainer/ejercicios-privados');
    ejercicios.value = response.data;
  } catch (error) {
    console.error('Error al cargar ejercicios:', error);
  } finally {
    cargando.value = false;
  }
};

const crearEjercicio = async () => {
  guardando.value = true;
  try {
    const response = await axios.post('/api/trainer/ejercicios-privados', form.value);
    ejercicios.value.push(response.data);
    mostrarModalCrear.value = false;
    form.value = { nombre: '', grupo_muscular: '', equipamiento: '', descripcion: '' };
    showToast('Ejercicio creado correctamente');
  } catch (error) {
    console.error('Error al crear ejercicio:', error);
    showToast('No se pudo crear el ejercicio', 'error');
  } finally {
    guardando.value = false;
  }
};

const eliminarEjercicio = async (ejercicio) => {
  // 1) Confirmación visual moderna
  const confirmed = await toast.confirm(
    `¿Eliminar el ejercicio "${ejercicio.nombre}"?`,
    { title: 'Eliminar ejercicio', confirmLabel: 'Sí, eliminar', type: 'error' }
  );
  if (!confirmed) return;

  // 2) Snapshot para posible undo (guardamos posición original para
  //    restaurar en el mismo lugar de la lista, no al final)
  const snapshot = { ...ejercicio };
  const originalIndex = ejercicios.value.findIndex(e => e.id === ejercicio.id);

  // 3) Patrón undo: optimistic + commit diferido + cancel
  await useUndoable({
    message: `Ejercicio "${ejercicio.nombre}" eliminado`,
    apply: () => {
      ejercicios.value = ejercicios.value.filter(e => e.id !== ejercicio.id);
    },
    undo: () => {
      // Restauramos en la posición original
      if (originalIndex >= 0 && originalIndex <= ejercicios.value.length) {
        ejercicios.value.splice(originalIndex, 0, snapshot);
      } else {
        ejercicios.value.push(snapshot);
      }
    },
    commit: () => axios.delete(`/api/trainer/ejercicios-privados/${ejercicio.id}`),
    onError: (err) => {
      console.error('Error al eliminar:', err);
    },
  });
};

onMounted(() => {
  fetchEjercicios();
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