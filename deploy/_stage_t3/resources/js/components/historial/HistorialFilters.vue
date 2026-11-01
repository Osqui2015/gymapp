<template>
    <section class="p-space-lg rounded-2xl bg-surface-container-low shadow-sm flex flex-col gap-space-md">
        <!-- Filter Header -->
        <div class="flex items-center justify-between flex-wrap gap-2">
            <div class="flex items-center gap-space-sm flex-wrap">
                <span class="material-symbols-outlined text-secondary text-[20px]">filter_alt</span>
                <span class="font-headline-md text-headline-md text-on-surface">Filtros y Búsqueda</span>
                <span
                    v-if="isFilterActive"
                    class="ml-1 inline-flex items-center gap-1 rounded-full bg-secondary-container/40 px-2.5 py-0.5 text-label-sm font-label-sm text-on-secondary-container"
                >
                    Filtros activos ({{ totalFiltrados }} de {{ totalOriginal }})
                </span>
            </div>
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    @click="mobileAdvancedOpen = !mobileAdvancedOpen"
                    class="sm:hidden px-2.5 py-1 rounded-lg bg-surface-container text-on-surface-variant hover:text-on-surface text-label-sm font-label-sm flex items-center gap-1 cursor-pointer"
                >
                    <span class="material-symbols-outlined text-[15px]">tune</span>
                    <span>{{ mobileAdvancedOpen ? 'Ocultar opciones' : 'Más filtros' }}</span>
                </button>
                <button
                    v-if="isFilterActive"
                    type="button"
                    @click="onClear"
                    class="text-label-md text-error text-rose-600 hover:underline transition-colors flex items-center gap-1 cursor-pointer"
                >
                    <span class="material-symbols-outlined text-[16px]">close</span>
                    <span>Limpiar filtros</span>
                </button>
            </div>
        </div>

        <!-- 4-Column Controls Row -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-space-md">
            <!-- Search Input -->
            <div class="flex flex-col gap-1">
                <label class="font-label-sm text-label-sm uppercase tracking-wider text-outline">
                    Buscar ejercicio
                </label>
                <div class="relative flex items-center">
                    <span class="material-symbols-outlined absolute left-3 text-outline text-[18px]">search</span>
                    <input
                        type="text"
                        :value="modelValue.search"
                        @input="updateField('search', $event.target.value)"
                        placeholder="Ej: Press Banca, Sentadilla..."
                        class="w-full h-11 pl-10 pr-4 rounded-xl bg-surface-container-lowest text-on-surface placeholder:text-outline/60 text-body-md focus:outline-none focus:ring-2 focus:ring-secondary transition-all border-0"
                    />
                    <button
                        v-if="modelValue.search"
                        type="button"
                        @click="updateField('search', '')"
                        class="absolute right-2.5 text-outline hover:text-on-surface cursor-pointer"
                    >
                        <span class="material-symbols-outlined text-[16px]">close</span>
                    </button>
                </div>
            </div>

            <!-- Routine Select -->
            <div :class="[mobileAdvancedOpen ? 'flex' : 'hidden sm:flex', 'flex-col gap-1']">
                <label class="font-label-sm text-label-sm uppercase tracking-wider text-outline">
                    Rutina
                </label>
                <div class="relative flex items-center">
                    <select
                        :value="modelValue.rutina || ''"
                        @change="updateField('rutina', $event.target.value)"
                        class="w-full h-11 pl-3 pr-9 rounded-xl bg-surface-container-lowest text-on-surface text-body-md appearance-none focus:outline-none focus:ring-2 focus:ring-secondary transition-all cursor-pointer border-0"
                    >
                        <option value="">Todas las rutinas</option>
                        <option
                            v-for="(r, idx) in rutinasDisponibles"
                            :key="typeof r === 'object' ? r.id : idx"
                            :value="typeof r === 'object' ? String(r.id ?? r.nombre) : r"
                        >
                            {{ typeof r === 'object' ? r.nombre : r }}
                        </option>
                    </select>
                    <span class="material-symbols-outlined absolute right-3 text-outline pointer-events-none text-[18px]">expand_more</span>
                </div>
            </div>

            <!-- Period Select -->
            <div :class="[mobileAdvancedOpen ? 'flex' : 'hidden sm:flex', 'flex-col gap-1']">
                <label class="font-label-sm text-label-sm uppercase tracking-wider text-outline">
                    Periodo
                </label>
                <div class="relative flex items-center">
                    <select
                        :value="modelValue.periodo || 'all'"
                        @change="updateField('periodo', $event.target.value)"
                        class="w-full h-11 pl-3 pr-9 rounded-xl bg-surface-container-lowest text-on-surface text-body-md appearance-none focus:outline-none focus:ring-2 focus:ring-secondary transition-all cursor-pointer border-0"
                    >
                        <option value="all">Todo el historial</option>
                        <option value="7d">Últimos 7 días</option>
                        <option value="30d">Últimos 30 días</option>
                        <option value="90d">Últimos 90 días</option>
                        <option value="365d">Último año</option>
                        <option value="year">Este año</option>
                        <option value="custom">Rango personalizado...</option>
                    </select>
                    <span class="material-symbols-outlined absolute right-3 text-outline pointer-events-none text-[18px]">calendar_today</span>
                </div>
            </div>

            <!-- Sort Select -->
            <div :class="[mobileAdvancedOpen ? 'flex' : 'hidden sm:flex', 'flex-col gap-1']">
                <label class="font-label-sm text-label-sm uppercase tracking-wider text-outline">
                    Ordenar por
                </label>
                <div class="relative flex items-center">
                    <select
                        :value="modelValue.orden || modelValue.sortBy || 'fecha_desc'"
                        @change="updateSort($event.target.value)"
                        class="w-full h-11 pl-3 pr-9 rounded-xl bg-surface-container-lowest text-on-surface text-body-md appearance-none focus:outline-none focus:ring-2 focus:ring-secondary transition-all cursor-pointer border-0"
                    >
                        <option value="fecha_desc">Orden de rutina</option>
                        <option value="fecha_asc">Más antiguos primero</option>
                        <option value="peso_desc">Mayor peso</option>
                        <option value="volumen_desc">Mayor volumen</option>
                        <option value="ejercicio_asc">Ejercicio (A-Z)</option>
                    </select>
                    <span class="material-symbols-outlined absolute right-3 text-outline pointer-events-none text-[18px]">sort</span>
                </div>
            </div>
        </div>

        <!-- Custom Date Range Inputs -->
        <div
            v-if="modelValue.periodo === 'custom'"
            class="grid grid-cols-1 sm:grid-cols-2 gap-space-md pt-1"
        >
            <div class="flex flex-col gap-1">
                <label class="font-label-sm text-label-sm uppercase tracking-wider text-outline">Desde</label>
                <input
                    type="date"
                    :value="modelValue.fechaDesde"
                    @input="updateField('fechaDesde', $event.target.value)"
                    class="w-full h-11 px-3 rounded-xl bg-surface-container-lowest text-on-surface text-body-md focus:outline-none focus:ring-2 focus:ring-secondary border-0"
                />
            </div>
            <div class="flex flex-col gap-1">
                <label class="font-label-sm text-label-sm uppercase tracking-wider text-outline">Hasta</label>
                <input
                    type="date"
                    :value="modelValue.fechaHasta"
                    @input="updateField('fechaHasta', $event.target.value)"
                    class="w-full h-11 px-3 rounded-xl bg-surface-container-lowest text-on-surface text-body-md focus:outline-none focus:ring-2 focus:ring-secondary border-0"
                />
            </div>
        </div>

        <!-- Bottom Row: Day Quick Pills & Completed Checkbox -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-space-md pt-space-xs">
            <div class="flex items-center gap-1.5 flex-wrap">
                <span class="font-label-sm text-label-sm uppercase tracking-wider text-outline mr-1">Día:</span>
                <button
                    type="button"
                    @click="updateField('dia', '')"
                    :class="[
                        'px-3 py-1.5 rounded-lg font-label-sm text-label-sm transition-all cursor-pointer',
                        !modelValue.dia || modelValue.dia === 'all'
                            ? 'bg-secondary-container text-on-secondary-container shadow-sm'
                            : 'bg-surface-container text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high',
                    ]"
                >
                    Todos
                </button>
                <button
                    v-for="dia in diasDisponibles"
                    :key="dia"
                    type="button"
                    @click="updateField('dia', dia)"
                    :class="[
                        'px-3 py-1.5 rounded-lg font-label-sm text-label-sm transition-all cursor-pointer',
                        modelValue.dia === dia
                            ? 'bg-secondary-container text-on-secondary-container shadow-sm'
                            : 'bg-surface-container text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high',
                    ]"
                >
                    {{ dia }}
                </button>
            </div>

            <label class="inline-flex items-center gap-space-sm cursor-pointer select-none group">
                <input
                    type="checkbox"
                    :checked="Boolean(modelValue.soloCompletados ?? modelValue.soloCompletadas)"
                    @change="updateCompleted($event.target.checked)"
                    class="w-4 h-4 rounded bg-surface-container-lowest text-secondary focus:ring-secondary focus:ring-offset-0 accent-secondary cursor-pointer"
                />
                <span class="text-body-sm text-on-surface-variant group-hover:text-on-surface transition-colors">
                    Solo series completadas
                </span>
            </label>
        </div>
    </section>
</template>

<script setup>
import { computed, ref } from 'vue';

const mobileAdvancedOpen = ref(false);

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
    hayFiltrosActivos: {
        type: Boolean,
        default: false,
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

const emit = defineEmits(['update:modelValue', 'clear', 'limpiar']);

const isFilterActive = computed(() => {
    if (props.hayFiltrosActivos) return true;
    const m = props.modelValue || {};
    return Boolean(
        (m.search && m.search.trim() !== '') ||
            (m.rutina && m.rutina !== 'all' && m.rutina !== '') ||
            (m.dia && m.dia !== 'all' && m.dia !== '') ||
            (m.periodo && m.periodo !== 'all') ||
            m.soloCompletados ||
            m.soloCompletadas ||
            (m.orden && m.orden !== 'fecha_desc')
    );
});

const onClear = () => {
    emit('clear');
    emit('limpiar');
};

const updateField = (key, value) => {
    emit('update:modelValue', {
        ...props.modelValue,
        [key]: value,
    });
};

const updateSort = (value) => {
    emit('update:modelValue', {
        ...props.modelValue,
        orden: value,
        sortBy: value,
    });
};

const updateCompleted = (checked) => {
    emit('update:modelValue', {
        ...props.modelValue,
        soloCompletados: checked,
        soloCompletadas: checked,
    });
};
</script>
