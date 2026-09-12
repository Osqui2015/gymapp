<template>
    <div class="space-y-6">
        <div
            class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-lg overflow-hidden"
        >
            <button
                type="button"
                @click="isExpanded = !isExpanded"
                class="w-full flex flex-col gap-3 bg-gradient-to-r from-slate-900 to-indigo-900 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6 cursor-pointer select-none transition-opacity hover:opacity-95"
                :aria-expanded="isExpanded"
                :aria-controls="matrixContentId"
            >
                <h2 class="text-lg font-bold text-white flex items-center gap-2">
                    <svg
                        :class="{ 'rotate-180': isExpanded }"
                        class="w-5 h-5 text-white/80 transition-transform duration-200 shrink-0"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                        aria-hidden="true"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M19 9l-7 7-7-7"
                        />
                    </svg>
                    <svg
                        class="w-5 h-5 text-indigo-400"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                        aria-hidden="true"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"
                        />
                    </svg>
                    Matriz de Cargas por Fecha
                </h2>
                <span
                    @click.stop="$emit('toggle-sort')"
                    :class="[
                        'flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-white transition-colors cursor-pointer',
                        dateSortAsc
                            ? 'bg-white/10 hover:bg-white/15 border border-white/10'
                            : 'bg-amber-400/20 hover:bg-amber-400/30 border border-amber-300/30',
                    ]"
                    role="button"
                    tabindex="0"
                    @keydown.enter.stop="$emit('toggle-sort')"
                    @keydown.space.prevent.stop="$emit('toggle-sort')"
                >
                    <svg
                        class="w-4 h-4 text-indigo-300"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                        aria-hidden="true"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"
                        />
                    </svg>
                    {{ dateSortAsc ? 'Cronológico' : 'Últimos primero' }}
                </span>
            </button>

            <div
                v-show="isExpanded"
                :id="matrixContentId"
                class="p-4 sm:p-6 border-t border-gray-200 dark:border-gray-700"
            >
                <div
                    class="hidden overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700 md:block max-h-[70vh] overflow-y-auto"
                >
                    <table class="w-full text-sm text-left border-collapse min-w-[600px] relative">
                        <thead
                            class="sticky top-0 z-30 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 uppercase text-xs shadow-sm"
                        >
                            <tr>
                                <th
                                    class="sticky left-0 z-40 bg-gray-100 dark:bg-gray-600 px-4 py-3.5 font-bold border-r border-gray-200 dark:border-gray-700 min-w-[160px] max-w-[200px]"
                                >
                                    Ejercicio
                                </th>
                                <th
                                    v-for="date in pivotData.dates"
                                    :key="date.raw"
                                    class="px-3 py-3.5 font-bold text-center border-r border-gray-200 dark:border-gray-700 min-w-[80px]"
                                >
                                    {{ date.formatted }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr
                                v-for="row in pivotData.rows"
                                :key="row.name"
                                class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors bg-white dark:bg-gray-800"
                            >
                                <td
                                    class="sticky left-0 z-20 bg-white dark:bg-gray-800 px-4 py-3.5 font-semibold text-gray-900 dark:text-white border-r border-gray-200 dark:border-gray-700 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)] min-w-[160px] max-w-[200px]"
                                >
                                    {{ row.name }}
                                    <span
                                        v-if="row.superserie_grupo"
                                        class="ml-2 inline-flex items-center rounded-md bg-indigo-50 px-2 py-0.5 text-xs font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-700/10 dark:bg-indigo-900/40 dark:text-indigo-400"
                                    >
                                        Superserie {{ row.superserie_grupo }}
                                    </span>
                                </td>
                                <td
                                    v-for="date in pivotData.dates"
                                    :key="date.raw"
                                    class="px-3 py-3.5 text-center border-r border-gray-200 dark:border-gray-700 font-medium min-w-[80px]"
                                >
                                    <button
                                        v-if="row.weights[date.raw] !== '-'"
                                        type="button"
                                        @click="onCellClick(row, date)"
                                        class="group relative inline-flex items-center justify-center gap-1 rounded-md bg-indigo-50 dark:bg-indigo-950/40 px-2 py-1 text-sm font-bold text-indigo-600 dark:text-indigo-400 hover:bg-indigo-100 dark:hover:bg-indigo-900/60 hover:ring-2 hover:ring-indigo-300 dark:hover:ring-indigo-500 transition-all cursor-pointer"
                                        :title="`${row.name} · ${date.formatted} — click para editar`"
                                        :aria-label="`Editar ${row.name} del ${date.formatted}`"
                                    >
                                        <span>{{ row.weights[date.raw] }}</span>
                                        <span
                                            v-if="isPR(row.name, date.raw)"
                                            class="inline-block w-1.5 h-1.5 rounded-full bg-amber-500"
                                            title="Récord personal"
                                        ></span>
                                    </button>
                                    <span
                                        v-else
                                        class="text-gray-400 dark:text-gray-600 font-normal"
                                    >
                                        -
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="space-y-3 md:hidden">
                    <article
                        v-for="row in pivotData.rows"
                        :key="row.name"
                        class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800"
                    >
                        <div class="border-b border-gray-200 px-4 py-3 dark:border-gray-700">
                            <p
                                class="break-words text-sm font-bold leading-snug text-gray-900 dark:text-white"
                            >
                                {{ row.name }}
                            </p>
                            <span
                                v-if="row.superserie_grupo"
                                class="mt-2 inline-flex rounded-md bg-indigo-50 px-2 py-0.5 text-[10px] font-semibold text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-400"
                            >
                                Superserie {{ row.superserie_grupo }}
                            </span>
                        </div>
                        <div class="grid grid-cols-1 gap-px bg-gray-200 dark:bg-gray-700">
                            <template v-for="date in pivotData.dates" :key="date.raw">
                                <button
                                    v-if="row.weights[date.raw] !== '-'"
                                    type="button"
                                    @click="onCellClick(row, date)"
                                    class="flex min-w-0 items-center justify-between gap-3 bg-white px-3 py-2.5 dark:bg-gray-800 text-left hover:bg-indigo-50 dark:hover:bg-indigo-950/30 transition-colors"
                                >
                                    <p
                                        class="text-[11px] font-medium tabular-nums text-gray-500 dark:text-gray-400"
                                    >
                                        {{ date.formatted }}
                                    </p>
                                    <p
                                        class="mt-0.5 text-sm font-bold text-indigo-600 dark:text-indigo-400 flex items-center gap-1"
                                    >
                                        {{ row.weights[date.raw] }}
                                        <span
                                            v-if="isPR(row.name, date.raw)"
                                            class="inline-block w-1.5 h-1.5 rounded-full bg-amber-500"
                                            title="Récord personal"
                                        ></span>
                                    </p>
                                </button>
                            </template>
                        </div>
                    </article>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';

const props = defineProps({
    pivotData: { type: Object, required: true }, // { dates: [], rows: [] }
    dateSortAsc: { type: Boolean, required: true },
    /**
     * Set de claves "ejercicio|||YYYY-MM-DD" que corresponden a un récord
     * personal histórico (peso máximo para ese ejercicio). El padre
     * (HistorialContent) lo calcula mirando todo el array `historial`.
     * Si no se pasa, no se muestra el dot.
     */
    prDates: { type: Set, default: () => new Set() },
});

const emit = defineEmits(['toggle-sort', 'cell-click']);

const isExpanded = ref(false);
const matrixContentId = 'historial-matrix-content';

const prKey = (ejercicio, date) => `${ejercicio}|||${date}`;

const isPR = (ejercicio, date) => props.prDates.has(prKey(ejercicio, date));

const onCellClick = (row, date) => {
    emit('cell-click', { ejercicio: row.name, fecha: date.raw, weight: row.weights[date.raw] });
};
</script>
