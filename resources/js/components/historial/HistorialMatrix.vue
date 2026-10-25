<template>
    <section
        v-if="normalizedRows.length > 0"
        class="p-space-lg rounded-2xl bg-surface-container-low shadow-sm flex flex-col gap-space-md overflow-hidden"
    >
        <!-- Card Header -->
        <div
            @click="isExpanded = !isExpanded"
            class="flex flex-col sm:flex-row sm:items-center justify-between gap-space-sm cursor-pointer select-none"
        >
            <div class="flex items-center gap-space-sm">
                <button
                    type="button"
                    class="w-8 h-8 rounded-lg bg-surface-container hover:bg-surface-container-high flex items-center justify-center text-on-surface-variant transition-colors cursor-pointer"
                >
                    <span
                        class="material-symbols-outlined text-[18px] transition-transform duration-200"
                        :class="{ 'rotate-180': !isExpanded }"
                    >
                        keyboard_arrow_up
                    </span>
                </button>
                <div class="flex items-center gap-space-sm flex-wrap">
                    <h2 class="font-headline-md text-headline-md text-on-surface">
                        Matriz de Cargas por Fecha
                    </h2>
                    <span class="px-2.5 py-0.5 rounded-full bg-surface-container-high text-on-surface-variant font-label-sm text-label-sm">
                        {{ (pivotData.dates || []).length }} {{ (pivotData.dates || []).length === 1 ? 'sesión' : 'sesiones' }}
                    </span>
                </div>
            </div>
            <div class="flex items-center gap-space-sm">
                <span class="md:hidden text-[11px] font-mono text-outline flex items-center gap-1">
                    <span>Deslizá</span>
                    <span class="material-symbols-outlined text-[14px]">swipe_left</span>
                </span>
                <button
                    v-if="isExpanded"
                    type="button"
                    @click.stop="$emit('toggle-sort')"
                    class="px-3 py-1.5 rounded-xl bg-surface-container hover:bg-surface-container-high text-on-surface-variant hover:text-on-surface font-label-sm text-label-sm flex items-center gap-1 transition-all cursor-pointer"
                >
                    <span class="material-symbols-outlined text-[16px]">swap_vert</span>
                    <span>{{ dateSortAsc ? 'Cronológico' : 'Últimos primero' }}</span>
                </button>
            </div>
        </div>

        <!-- Scrollable Table Container with Mobile Scroll Shadow -->
        <div v-show="isExpanded" class="relative -mx-space-lg px-space-lg">
            <div class="pointer-events-none md:hidden absolute right-0 top-0 bottom-0 w-8 bg-gradient-to-l from-surface-container-low to-transparent z-20"></div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[600px]">
                    <thead>
                        <tr class="bg-surface-container-high/60 font-label-sm text-label-sm text-outline uppercase tracking-wider">
                            <th class="py-3 px-4 rounded-l-xl w-[35%] max-w-[180px] sm:max-w-none sticky left-0 z-10 bg-surface-container-high shadow-[2px_0_8px_rgba(0,0,0,0.35)]">
                                Ejercicio
                            </th>
                            <th
                                v-for="(date, idx) in pivotData.dates"
                                :key="date.raw"
                                class="py-3 px-4 text-center whitespace-nowrap"
                                :class="{ 'rounded-r-xl': idx === (pivotData.dates || []).length - 1 }"
                            >
                                {{ date.formatted }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="text-body-md">
                        <tr
                            v-for="(row, rowIdx) in normalizedRows"
                            :key="row.name"
                            :class="[
                                'hover:bg-surface-container/60 transition-colors',
                                rowIdx % 2 === 1 ? 'bg-surface-container-lowest/30' : '',
                            ]"
                        >
                            <td class="py-3.5 px-4 font-semibold text-on-surface sticky left-0 z-10 bg-surface-container-low max-w-[180px] sm:max-w-none shadow-[2px_0_8px_rgba(0,0,0,0.35)]">
                                <div class="flex flex-col">
                                    <span class="truncate sm:whitespace-normal">{{ row.name }}</span>
                                    <span
                                        v-if="row.superserie"
                                        class="text-[10px] text-secondary font-semibold uppercase tracking-wider mt-0.5"
                                    >
                                        Superserie {{ row.superserie }}
                                    </span>
                                </div>
                            </td>
                            <td
                                v-for="date in pivotData.dates"
                                :key="date.raw"
                                class="py-3.5 px-4 text-center whitespace-nowrap group/cell relative"
                            >
                                <template v-if="cellDisplay(row, date.raw) !== '-'">
                                    <button
                                        type="button"
                                        @click.stop="onCellClick(row.name, date.raw, date.formatted)"
                                        :title="`Editar series de ${row.name} (${date.formatted})`"
                                        :class="[
                                            'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-label-sm transition-transform hover:scale-105 cursor-pointer tabular-nums',
                                            isPR(row.name, date.raw)
                                                ? 'bg-secondary-container text-on-secondary-container font-bold shadow-sm'
                                                : 'bg-surface-container text-on-surface font-semibold',
                                        ]"
                                    >
                                        <span
                                            v-if="isPR(row.name, date.raw)"
                                            class="material-symbols-outlined text-[13px] text-tertiary"
                                        >
                                            star
                                        </span>
                                        <span
                                            v-else
                                            class="w-1.5 h-1.5 rounded-full"
                                            :class="row.superserie ? 'bg-tertiary' : 'bg-secondary'"
                                        ></span>
                                        <span>{{ cellDisplay(row, date.raw) }}</span>
                                    </button>
                                </template>
                                <span v-else class="text-outline/40 text-body-sm">-</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    pivotData: {
        type: Object,
        required: true,
        default: () => ({ dates: [], rows: [], exercises: [] }),
    },
    dateSortAsc: {
        type: Boolean,
        default: true,
    },
    prDates: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['edit-cell', 'cell-click', 'toggle-sort']);

const isExpanded = ref(true);

const normalizedRows = computed(() => {
    const rawList = props.pivotData.rows || props.pivotData.exercises || [];
    return rawList.map((r) => ({
        name: r.name,
        superserie: r.superserie_grupo || r.superserie_group || null,
        map: r.weights || r.values || {},
    }));
});

const cellDisplay = (row, dateRaw) => {
    const val = row.map?.[dateRaw];
    if (val === undefined || val === null || val === '-') return '-';
    const str = String(val).trim();
    if (!str || str === '-') return '-';
    return /kg|lb/i.test(str) ? str.replace(/\s+/g, '') : `${str}kg`;
};

const onCellClick = (exerciseName, dateRaw, dateLabel) => {
    const payload = {
        ejercicio: exerciseName,
        fecha: dateRaw,
        exerciseName,
        dateRaw,
        dateLabel,
    };
    emit('edit-cell', payload);
    emit('cell-click', payload);
};

const isPR = (exerciseName, dateRaw) => {
    if (props.prDates && typeof props.prDates.has === 'function') {
        return props.prDates.has(`${exerciseName}|${dateRaw}`);
    }
    return false;
};
</script>
