<template>
    <div
        class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-4 sm:p-5 shadow-sm mb-6 transition-all"
    >
        <div class="flex items-center justify-between gap-3 mb-4 flex-wrap">
            <div class="flex items-center gap-2">
                <span class="text-lg">🔍</span>
                <h3
                    class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider"
                >
                    Filtros y Búsqueda
                </h3>
                <span
                    v-if="hayFiltrosActivos"
                    class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-100 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300"
                >
                    Filtros activos ({{ totalFiltrados }} de {{ totalOriginal }})
                </span>
            </div>

            <button
                v-if="hayFiltrosActivos"
                type="button"
                @click="limpiarFiltros"
                class="text-xs font-semibold text-rose-600 dark:text-rose-400 hover:underline flex items-center gap-1"
            >
                <span>✕</span> Limpiar filtros
            </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <!-- Búsqueda por ejercicio -->
            <div>
                <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">
                    Buscar ejercicio
                </label>
                <div class="relative">
                    <input
                        type="text"
                        :value="modelValue.search"
                        @input="actualizar('search', $event.target.value)"
                        placeholder="Ej: Press banca, Sentadilla..."
                        class="w-full rounded-xl border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 px-3 py-2 text-xs text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    />
                    <button
                        v-if="modelValue.search"
                        type="button"
                        @click="actualizar('search', '')"
                        class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                        aria-label="Borrar búsqueda"
                    >
                        ✕
                    </button>
                </div>
            </div>

            <!-- Filtro por Rutina -->
            <div>
                <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">
                    Rutina
                </label>
                <select
                    :value="modelValue.rutina"
                    @change="actualizar('rutina', $event.target.value)"
                    class="w-full rounded-xl border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 px-3 py-2 text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
                >
                    <option value="">Todas las rutinas</option>
                    <option v-for="r in rutinasDisponibles" :key="r" :value="r">
                        {{ r }}
                    </option>
                </select>
            </div>

            <!-- Filtro por Período de Tiempo -->
            <div>
                <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">
                    Período
                </label>
                <select
                    :value="modelValue.periodo"
                    @change="actualizar('periodo', $event.target.value)"
                    class="w-full rounded-xl border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 px-3 py-2 text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
                >
                    <option value="30">Últimos 30 días</option>
                    <option value="90">Últimos 90 días</option>
                    <option value="365">Último año</option>
                    <option value="all">Todo el historial</option>
                </select>
            </div>

            <!-- Ordenamiento -->
            <div>
                <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">
                    Ordenar por
                </label>
                <select
                    :value="modelValue.orden"
                    @change="actualizar('orden', $event.target.value)"
                    class="w-full rounded-xl border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 px-3 py-2 text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
                >
                    <option value="fecha_desc">Fecha (más reciente primero)</option>
                    <option value="fecha_asc">Fecha (más antigua primero)</option>
                    <option value="peso_desc">Mayor peso levantado</option>
                    <option value="reps_desc">Más repeticiones</option>
                </select>
            </div>
        </div>

        <!-- Filtros secundarios rápidos -->
        <div
            class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700/60 flex items-center justify-between gap-4 flex-wrap text-xs"
        >
            <label
                class="inline-flex items-center gap-2 cursor-pointer select-none text-gray-700 dark:text-gray-300"
            >
                <input
                    type="checkbox"
                    :checked="modelValue.soloCompletados"
                    @change="actualizar('soloCompletados', $event.target.checked)"
                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 w-4 h-4"
                />
                <span>Solo series completadas</span>
            </label>

            <div v-if="diasDisponibles.length > 1" class="flex items-center gap-1.5 flex-wrap">
                <span class="text-gray-500 dark:text-gray-400">Día:</span>
                <button
                    type="button"
                    @click="actualizar('dia', '')"
                    :class="[
                        'px-2 py-0.5 rounded-lg transition-colors font-medium',
                        !modelValue.dia
                            ? 'bg-indigo-600 text-white'
                            : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200',
                    ]"
                >
                    Todos
                </button>
                <button
                    v-for="d in diasDisponibles"
                    :key="d"
                    type="button"
                    @click="actualizar('dia', d)"
                    :class="[
                        'px-2 py-0.5 rounded-lg transition-colors font-medium',
                        modelValue.dia === d
                            ? 'bg-indigo-600 text-white'
                            : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200',
                    ]"
                >
                    {{ d }}
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    modelValue: {
        type: Object,
        required: true,
    },
    rutinasDisponibles: {
        type: Array,
        default: () => [],
    },
    diasDisponibles: {
        type: Array,
        default: () => [],
    },
    totalFiltrados: {
        type: Number,
        default: 0,
    },
    totalOriginal: {
        type: Number,
        default: 0,
    },
});

const emit = defineEmits(['update:modelValue', 'limpiar']);

const hayFiltrosActivos = computed(() => {
    return (
        !!props.modelValue.search ||
        !!props.modelValue.rutina ||
        !!props.modelValue.dia ||
        props.modelValue.periodo !== 'all' ||
        props.modelValue.soloCompletados ||
        props.modelValue.orden !== 'fecha_desc'
    );
});

const actualizar = (campo, valor) => {
    emit('update:modelValue', {
        ...props.modelValue,
        [campo]: valor,
    });
};

const limpiarFiltros = () => {
    emit('limpiar');
};
</script>
