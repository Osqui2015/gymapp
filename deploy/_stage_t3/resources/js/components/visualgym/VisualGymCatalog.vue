<!--
  VisualGymCatalog — vista principal del catálogo VisualGym.

  Comportamiento:
    - Carga facets al montar (1 sola vez)
    - Carga la primera página de ejercicios
    - Filtros re-disparan fetchList (vuelve a página 1)
    - Paginación Laravel (links de páginas)
    - Click en card abre el modal de detalle

  Sin vue-router: el padre lo monta con <visualgym-catalog></visualgym-catalog>.
-->
<template>
    <div class="min-h-screen bg-gray-50 dark:bg-[var(--color-obsidian-base)] py-6 md:py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <header class="mb-6">
                <div class="flex items-center gap-2 mb-1">
                    <span
                        class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 bg-gradient-to-r from-indigo-500 to-purple-500 text-white rounded-full"
                    >
                        VisualGym
                    </span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        exercises-dataset
                    </span>
                </div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Catálogo de Ejercicios</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    {{ store.pagination.total.toLocaleString() }} ejercicios con animación GIF,
                    instrucciones en español y metadata completa.
                </p>
            </header>

            <!-- Filtros -->
            <div class="mb-6">
                <VisualGymFilters
                    :filters="store.filters"
                    :facets="store.facets"
                    :total="store.pagination.total"
                    @update="onFilterUpdate"
                    @clear="store.clearFilters()"
                />
            </div>

            <!-- Loading inicial -->
            <div
                v-if="store.loading && !store.exercises.length"
                class="flex items-center justify-center py-16 text-gray-500"
            >
                <svg class="animate-spin w-6 h-6 mr-3" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span class="text-sm">Cargando ejercicios…</span>
            </div>

            <!-- Empty state -->
            <div
                v-else-if="!store.loading && !store.hasResults"
                class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-12 text-center"
            >
                <svg
                    class="w-12 h-12 mx-auto text-gray-400 mb-3"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.5"
                        d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                    />
                </svg>
                <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-1">
                    No hay ejercicios con esos filtros
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    Probá limpiar los filtros o cambiar la búsqueda.
                </p>
                <button
                    @click="store.clearFilters()"
                    class="text-sm font-semibold text-indigo-600 dark:text-indigo-400 hover:underline"
                >
                    Limpiar filtros
                </button>
            </div>

            <!-- Grid de cards -->
            <div
                v-else
                class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3"
            >
                <VisualGymCard
                    v-for="e in store.exercises"
                    :key="e.external_id"
                    :exercise="e"
                    @open="openDetail"
                    @toggle-favorite="onToggleFavorite"
                />
            </div>

            <!-- Paginación -->
            <nav
                v-if="store.totalPages > 1"
                class="mt-8 flex items-center justify-between gap-3 flex-wrap"
                aria-label="Paginación"
            >
                <p class="text-xs text-gray-600 dark:text-gray-400">
                    Página
                    <span class="font-semibold">{{ store.pagination.current_page }}</span>
                    de
                    <span class="font-semibold">{{ store.totalPages }}</span>
                </p>
                <div class="flex items-center gap-1">
                    <button
                        type="button"
                        :disabled="store.pagination.current_page <= 1 || store.loading"
                        @click="store.goToPage(store.pagination.current_page - 1)"
                        class="px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm font-semibold text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-40 disabled:cursor-not-allowed"
                    >
                        ← Anterior
                    </button>
                    <button
                        v-for="p in visiblePages"
                        :key="p"
                        type="button"
                        @click="store.goToPage(p)"
                        :class="[
                            'px-3 py-1.5 rounded-lg border text-sm font-semibold',
                            p === store.pagination.current_page
                                ? 'bg-indigo-600 text-white border-indigo-600'
                                : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700',
                        ]"
                    >
                        {{ p }}
                    </button>
                    <button
                        type="button"
                        :disabled="store.pagination.current_page >= store.totalPages || store.loading"
                        @click="store.goToPage(store.pagination.current_page + 1)"
                        class="px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm font-semibold text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-40 disabled:cursor-not-allowed"
                    >
                        Siguiente →
                    </button>
                </div>
            </nav>
        </div>

        <!-- Modal de detalle -->
        <VisualGymDetailModal
            v-model:open="modalOpen"
            :external-id="currentExternalId"
            lang="es"
        />
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useVisualGymStore } from '../../stores/visualgym';
import VisualGymCard from './VisualGymCard.vue';
import VisualGymFilters from './VisualGymFilters.vue';
import VisualGymDetailModal from './VisualGymDetailModal.vue';

const store = useVisualGymStore();

const modalOpen = ref(false);
const currentExternalId = ref(null);

function openDetail(externalId) {
    currentExternalId.value = externalId;
    modalOpen.value = true;
}

async function onToggleFavorite(ejercicio) {
    try {
        await store.toggleFavorite(ejercicio);
    } catch (e) {
        // El toast global de axios ya muestra el error; no duplicamos.
    }
}

async function onFilterUpdate(key, value) {
    // Debounceo liviano para el campo de búsqueda (300ms)
    if (key === 'busqueda') {
        clearTimeout(onFilterUpdate._t);
        onFilterUpdate._t = setTimeout(() => {
            store.setFilter(key, value);
        }, 300);
    } else {
        await store.setFilter(key, value);
    }
}

/**
 * Páginas visibles en la barra de paginación.
 * Si hay muchas páginas, mostramos un rango alrededor de la actual
 * más la primera y la última.
 */
const visiblePages = computed(() => {
    const total = store.totalPages;
    const current = store.pagination.current_page;
    if (total <= 7) {
        return Array.from({ length: total }, (_, i) => i + 1);
    }
    const set = new Set([1, total, current - 1, current, current + 1]);
    return Array.from(set)
        .filter((p) => p >= 1 && p <= total)
        .sort((a, b) => a - b);
});

onMounted(async () => {
    await store.loadFacets();
    await store.fetchList({ page: 1 });
});
</script>
