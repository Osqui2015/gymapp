<!--
  VisualGymFilters — barra de filtros para el catálogo.

  Filtros:
    - busqueda (texto libre)
    - body_part (dropdown)
    - target (dropdown)
    - equipamiento (dropdown)
    - botón limpiar

  Emite 'update' cuando cualquier filtro cambia para que el padre recargue.
-->
<template>
    <div
        class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-4"
    >
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-3 items-end">
            <!-- Búsqueda -->
            <div class="lg:col-span-2">
                <label
                    class="block text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1"
                >
                    Buscar
                </label>
                <div class="relative">
                    <span
                        class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z"
                            />
                        </svg>
                    </span>
                    <input
                        :value="filters.busqueda"
                        @input="$emit('update', 'busqueda', $event.target.value)"
                        type="search"
                        placeholder="Ej: bench, sentadilla, biceps..."
                        class="w-full pl-10 pr-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    />
                </div>
            </div>

            <!-- body_part -->
            <div>
                <label
                    class="block text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1"
                >
                    Grupo
                </label>
                <select
                    :value="filters.body_part"
                    @change="$emit('update', 'body_part', $event.target.value)"
                    class="w-full pl-3 pr-8 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                >
                    <option value="">Todos</option>
                    <option v-for="b in facets.body_parts" :key="b.value" :value="b.value">
                        {{ b.label_es }}
                    </option>
                </select>
            </div>

            <!-- equipamiento -->
            <div>
                <label
                    class="block text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1"
                >
                    Equipamiento
                </label>
                <select
                    :value="filters.equipamiento"
                    @change="$emit('update', 'equipamiento', $event.target.value)"
                    class="w-full pl-3 pr-8 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                >
                    <option value="">Todos</option>
                    <option v-for="e in facets.equipamientos" :key="e.value" :value="e.value">
                        {{ e.label_es }}
                    </option>
                </select>
            </div>

            <!-- target -->
            <div>
                <label
                    class="block text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1"
                >
                    Músculo target
                </label>
                <select
                    :value="filters.target"
                    @change="$emit('update', 'target', $event.target.value)"
                    class="w-full pl-3 pr-8 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                >
                    <option value="">Todos</option>
                    <option v-for="t in facets.targets" :key="t.value" :value="t.value">
                        {{ t.label_es }}
                    </option>
                </select>
            </div>
        </div>

        <!-- Limpiar + contador -->
        <div class="mt-3 flex items-center justify-between gap-3">
            <button
                v-if="hasActiveFilters"
                @click="$emit('clear')"
                type="button"
                class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline"
            >
                Limpiar filtros
            </button>
            <span v-else class="text-xs text-gray-400">&nbsp;</span>
            <p v-if="total !== null" class="text-xs text-gray-600 dark:text-gray-400">
                <span class="font-semibold">{{ total }}</span> ejercicio{{ total === 1 ? '' : 's' }}
            </p>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    filters: { type: Object, required: true },
    facets: { type: Object, required: true },
    total: { type: Number, default: null },
});

defineEmits(['update', 'clear']);

const hasActiveFilters = computed(() => {
    return Object.values(props.filters).some((v) => v !== '' && v !== null);
});
</script>
