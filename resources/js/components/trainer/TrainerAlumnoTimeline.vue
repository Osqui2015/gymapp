<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
        <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        Timeline de {{ alumno?.name || 'alumno' }}
      </h3>
      <span class="text-xs text-gray-500 dark:text-gray-400">{{ totalEventos }} eventos</span>
    </div>

    <div v-if="loading" class="space-y-3">
      <div v-for="n in 4" :key="n" class="animate-pulse flex gap-3">
        <div class="w-8 h-8 bg-gray-200 dark:bg-gray-700 rounded-full flex-shrink-0"></div>
        <div class="flex-1 space-y-2 py-1">
          <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-3/4"></div>
          <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded w-1/2"></div>
        </div>
      </div>
    </div>

    <div v-else-if="eventos.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400 text-sm">
      Aún no hay eventos registrados para este alumno.
    </div>

    <div v-else class="relative pl-6 border-l-2 border-indigo-200 dark:border-indigo-800 space-y-4">
      <div v-for="(ev, i) in eventosPaginados" :key="i" class="relative">
        <!-- Dot -->
        <div :class="['absolute -left-[33px] top-1 w-4 h-4 rounded-full ring-4 ring-white dark:ring-gray-900 flex items-center justify-center text-xs', ev.color]">
          <span class="text-white">{{ ev.icono }}</span>
        </div>
        <!-- Card -->
        <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-3 border border-gray-200 dark:border-gray-700">
          <div class="flex items-start justify-between gap-2">
            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ ev.titulo }}</p>
            <span class="text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ formatFecha(ev.fecha) }}</span>
          </div>
          <p v-if="ev.descripcion" class="mt-1 text-xs text-gray-600 dark:text-gray-400">{{ ev.descripcion }}</p>
          <div v-if="ev.meta" class="mt-2 flex flex-wrap gap-2 text-xs">
            <span v-for="(val, k) in ev.meta" :key="k" class="px-2 py-0.5 bg-white dark:bg-gray-800 rounded border border-gray-200 dark:border-gray-700">
              <span class="text-gray-500 dark:text-gray-400">{{ k }}:</span> <strong>{{ val }}</strong>
            </span>
          </div>
        </div>
      </div>

      <div v-if="eventos.length > pageSize" class="text-center pt-2">
        <button
          @click="cargarMas"
          class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:underline"
        >
          {{ mostrarTodos ? 'Ver menos' : `Ver más (${eventos.length - pageSize})` }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import axios from 'axios';
import { useFormatters } from '@/composables/useFormatters';

const { formatDateMedium } = useFormatters();

const props = defineProps({
    alumno: { type: Object, required: true },
});

const eventos = ref([]);
const loading = ref(false);
const mostrarTodos = ref(false);
const pageSize = 10;

const eventosPaginados = computed(() => mostrarTodos.value ? eventos.value : eventos.value.slice(0, pageSize));
const totalEventos = computed(() => eventos.value.length);

const cargarTimeline = async () => {
    if (!props.alumno?.id) return;
    loading.value = true;
    try {
        // Combina hitos de distintas fuentes: entrenamientos, PRs, medidas, metas, medallas
        const response = await axios.get(`/api/trainer/alumnos/${props.alumno.id}/timeline`);
        eventos.value = response.data.eventos || [];
    } catch (err) {
        console.warn('No se pudo cargar timeline, fallback vacío:', err.message);
        eventos.value = [];
    } finally {
        loading.value = false;
    }
};

const cargarMas = () => { mostrarTodos.value = !mostrarTodos.value; };

const formatFecha = (iso) => {
    if (!iso) return '';
    const d = new Date(iso);
    const ahora = new Date();
    const diffDias = Math.floor((ahora - d) / (1000 * 60 * 60 * 24));
    if (diffDias === 0) return 'Hoy';
    if (diffDias === 1) return 'Ayer';
    if (diffDias < 7) return `Hace ${diffDias} días`;
    return formatDateMedium(d);
};

watch(() => props.alumno?.id, () => {
    mostrarTodos.value = false;
    cargarTimeline();
}, { immediate: false });

onMounted(() => cargarTimeline());
</script>