<script setup>
import PlanSeriesChips from '@/components/training/PlanSeriesChips.vue';
import SkeletonLoader from '@/components/common/SkeletonLoader.vue';
import { tienePlan } from '@/utils/rutinaPrescripcion';

const props = defineProps({
    /** Ejercicios agrupados del día actual. */
    ejerciciosDelDia: {
        type: Array,
        default: () => [],
    },
    /** Cantidad de ejercicios completados. */
    ejerciciosCompletadosCount: {
        type: Number,
        default: 0,
    },
    /** Bloques informativos del día (movilidad / calentamiento). */
    bloquesDelDia: {
        type: Array,
        default: () => [],
    },
    /** Si la lista de ejercicios está en estado de carga inicial. */
    isInitialLoading: {
        type: Boolean,
        default: false,
    },
    /** Si las series están desplegadas. */
    expandirTodos: {
        type: Boolean,
        default: true,
    },
    /** Serie abierta en el editor inline. */
    activeInlineSet: {
        type: Object,
        default: null,
    },
    /** Indica si una serie tiene valor histórico disponible. */
    esSetHistorico: {
        type: Function,
        required: true,
    },
    /** Devuelve el label formateado de una serie (reps × peso). */
    formatSetLabel: {
        type: Function,
        required: true,
    },
    /** Devuelve el registro histórico de una serie (o null). */
    valorHistoricoSet: {
        type: Function,
        required: true,
    },
});

const emit = defineEmits([
    'toggle-expand',
    'toggle-inline-set',
    'close-inline-set',
    'copy-from-previous',
    'step-weight',
    'step-reps',
    'complete-set',
    'update-field',
]);

/**
 * Escribe en el set abierto y avisa al padre.
 * El set es un prop: no lo mutamos acá, delegamos la escritura al dueño
 * del estado para no romper la regla de props inmutables.
 *
 * `toNumber` replica el modificador `.number` de `v-model`: los valores
 * vacíos o no numéricos se conservan tal cual (el padre valida `''`).
 */
const toNumber = (valor) => {
    const n = parseFloat(valor);
    return isNaN(n) ? valor : n;
};

const updateField = (campo, valor) => {
    emit('update-field', props.activeInlineSet, campo, toNumber(valor));
};
</script>

<template>
    <section
        class="flex flex-col gap-space-md mb-space-xl"
        data-purpose="todays-exercise-list"
        data-tour="series-list"
    >
        <!-- Section Header with Controls -->
        <div class="flex items-center justify-between pb-space-xs">
            <div class="flex items-center gap-space-sm">
                <span class="w-2 h-2 rounded-full bg-tertiary"></span>
                <h3
                    class="font-headline-md text-headline-md text-on-surface font-bold tracking-tight"
                >
                    EJERCICIOS DE HOY
                </h3>
                <span
                    class="px-2 py-0.5 rounded-full bg-surface-container text-tertiary font-label-sm text-label-sm font-semibold"
                >
                    {{ ejerciciosCompletadosCount }}/{{ ejerciciosDelDia.length }}
                    completados
                </span>
            </div>
            <button
                type="button"
                @click="$emit('toggle-expand')"
                class="flex items-center gap-1.5 px-space-md py-1 rounded-lg bg-surface-container hover:bg-surface-container-high text-on-surface-variant hover:text-on-surface font-label-sm text-label-sm transition-colors"
            >
                <span class="material-symbols-outlined text-[16px]">
                    {{ expandirTodos ? 'unfold_less' : 'unfold_more' }}
                </span>
                <span>{{ expandirTodos ? 'Colapsar' : 'Expandir' }}</span>
            </button>
        </div>

        <!-- Bloques del día (movilidad / calentamiento): no son
             series, por eso no aparecen como ejercicio, pero
             hay que hacerlos antes de la barra. -->
        <div
            v-if="bloquesDelDia.length"
            class="flex flex-col gap-2 rounded-xl border border-amber-500/30 bg-amber-500/10 px-space-md py-space-sm"
            data-testid="bloques-del-dia"
        >
            <div
                v-for="bloque in bloquesDelDia"
                :key="bloque.id"
                class="flex items-start gap-2"
            >
                <span class="material-symbols-outlined text-[16px] text-amber-600 dark:text-amber-400 mt-px">timer</span>
                <div class="min-w-0">
                    <span class="text-label-sm text-label-sm font-bold text-amber-800 dark:text-amber-300 uppercase tracking-wide">
                        {{ bloque.nombre }}
                    </span>
                    <p class="text-[11px] leading-relaxed text-on-surface-variant mt-0.5">
                        {{ bloque.notas }}
                    </p>
                </div>
            </div>
        </div>

        <div v-if="isInitialLoading" class="space-y-3">
            <SkeletonLoader variant="card" />
            <SkeletonLoader variant="card" />
            <SkeletonLoader variant="card" />
        </div>

        <!-- Exercise Cards Container -->
        <div v-else class="flex flex-col gap-space-md transition-all">
            <article
                v-for="(ej, idx) in ejerciciosDelDia"
                :key="ej.nombre"
                class="p-space-md md:p-space-lg rounded-xl bg-surface-container-low hover:bg-surface-container border border-outline-variant/60 transition-colors shadow-xs"
            >
                <div
                    class="flex flex-col lg:flex-row lg:items-center justify-between gap-space-md"
                >
                    <div class="flex items-center gap-space-md">
                        <div
                            class="w-8 h-8 rounded-lg bg-surface-container-highest flex items-center justify-center font-label-md text-label-md text-on-surface font-bold shrink-0"
                        >
                            {{ idx + 1 }}
                        </div>
                        <div class="flex flex-col">
                            <div class="flex items-center gap-space-sm">
                                <h4
                                    class="font-headline-md text-headline-md text-on-surface font-bold"
                                >
                                    {{ ej.nombre }}
                                </h4>
                                <span
                                    v-if="ej.estaCompleto"
                                    class="material-symbols-outlined text-tertiary text-[18px]"
                                    >verified</span
                                >
                            </div>
                            <div
                                class="font-body-sm text-body-sm text-outline flex flex-wrap items-center gap-space-xs"
                            >
                                <span v-if="ej.repsLabel">{{ ej.repsLabel }}</span>
                                <span v-if="ej.repsLabel && ej.ultimoPesoLabel"
                                    >•</span
                                >
                                <span v-if="ej.ultimoPesoLabel">{{
                                    ej.ultimoPesoLabel
                                }}</span>
                                <template v-if="ej.musculoLabel">
                                    <span>•</span>
                                    <span class="text-tertiary">{{
                                        ej.musculoLabel
                                    }}</span>
                                </template>
                            </div>

                            <!-- Plan de series: bloques, rest-pause y progresión -->
                            <PlanSeriesChips :ejercicio="ej" tema="auto" mostrar-consejo />

                            <!-- Notas crudas cuando el ejercicio no tiene plan
                                 (ej. "3x8 (estocadas, sin RIR objetivo)") -->
                            <p
                                v-if="!tienePlan(ej) && ej.notas"
                                class="text-[11px] leading-relaxed text-gray-500 dark:text-gray-400 italic"
                                :title="ej.notas"
                            >
                                💡 {{ ej.notas }}
                            </p>
                        </div>
                    </div>
                    <div
                        class="flex items-center gap-space-sm justify-between lg:justify-end"
                    >
                        <!-- Indicador visual con dots ●●○○ -->
                        <div
                            class="flex items-center gap-1.5 px-3 py-1 rounded-full bg-surface-container-high font-mono text-xs"
                            :title="`${ej.completadas}/${ej.sets.length} series completadas`"
                        >
                            <span class="flex items-center gap-0.5 tracking-tight text-sm">
                                <span
                                    v-for="(s, sIdx) in ej.sets"
                                    :key="sIdx"
                                    :class="s.completado ? 'text-tertiary' : 'text-on-surface-variant/40'"
                                >
                                    {{ s.completado ? '●' : '○' }}
                                </span>
                            </span>
                            <span
                                class="text-[11px] font-bold ml-1 tabular-nums font-sans"
                                :class="ej.estaCompleto ? 'text-tertiary' : 'text-secondary'"
                            >
                                {{ ej.completadas }}/{{ ej.sets.length }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Sets Chips Grid (Interactive Tap-to-Edit) -->
                <div
                    v-show="expandirTodos"
                    class="mt-space-md pt-space-md bg-surface-container-lowest/40 -mx-space-md -mb-space-md md:-mx-space-lg md:-mb-space-lg p-space-md md:p-space-md rounded-b-xl space-y-3"
                >
                    <div
                        :class="[
                            'grid gap-space-sm',
                            ej.sets.length === 3
                                ? 'grid-cols-2 sm:grid-cols-3'
                                : 'grid-cols-2 sm:grid-cols-4',
                        ]"
                    >
                        <button
                            v-for="(set, sIdx) in ej.sets"
                            :key="sIdx"
                            type="button"
                            @click="$emit('toggle-inline-set', set)"
                            :title="
                                esSetHistorico(set)
                                    ? 'Tocá para editar o completar serie (valor previo disponible)'
                                    : 'Tocá para editar peso y repeticiones'
                            "
                            :class="[
                                'flex flex-col px-space-md py-2 rounded-lg text-left transition-all cursor-pointer relative group/set',
                                activeInlineSet === set
                                    ? 'ring-2 ring-secondary bg-surface-container-high'
                                    : set.completado
                                      ? 'bg-tertiary/10 hover:bg-tertiary/15'
                                      : esSetHistorico(set)
                                        ? 'bg-surface-container-high/50 border border-dashed border-outline-variant/50 hover:border-secondary/60'
                                        : 'bg-surface-container hover:bg-surface-container-high',
                            ]"
                        >
                            <div class="flex items-center justify-between w-full">
                                <span
                                    class="font-label-sm text-label-sm"
                                    :class="
                                        set.completado
                                            ? 'text-tertiary'
                                            : esSetHistorico(set)
                                              ? 'text-secondary'
                                              : 'text-outline'
                                    "
                                >
                                    SET {{ sIdx + 1 }}
                                </span>
                                <span
                                    v-if="set.completado"
                                    class="material-symbols-outlined text-[14px] text-tertiary"
                                >
                                    check_circle
                                </span>
                                <span
                                    v-else
                                    class="material-symbols-outlined text-[13px] text-outline/60 group-hover/set:text-secondary transition-colors"
                                >
                                    edit
                                </span>
                            </div>
                            <span
                                class="font-label-md text-label-md text-on-surface font-bold tabular-nums"
                            >
                                {{ formatSetLabel(set) }}
                            </span>
                        </button>
                    </div>

                    <!-- Inline Quick Set Telemetry Editor -->
                    <div
                        v-if="ej.sets.includes(activeInlineSet)"
                        class="p-3 rounded-xl bg-[#131924] border border-[#232D42] flex flex-col gap-3 shadow-lg"
                    >
                        <div class="flex items-center justify-between gap-2 flex-wrap">
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-indigo-400 animate-pulse"></span>
                                <span class="text-xs font-bold text-white uppercase tracking-wider">
                                    Editar Set #{{ activeInlineSet.series_numero }} · {{ ej.nombre }}
                                </span>
                            </div>
                            <div class="flex items-center gap-2">
                                <button
                                    v-if="valorHistoricoSet(activeInlineSet)"
                                    type="button"
                                    @click="$emit('copy-from-previous', activeInlineSet)"
                                    class="px-2.5 py-1 rounded-lg bg-indigo-500/20 hover:bg-indigo-500/30 border border-indigo-500/40 text-[11px] font-mono font-bold text-indigo-300 cursor-pointer"
                                >
                                    ↺ Copiar anterior ({{ valorHistoricoSet(activeInlineSet).peso ?? 0 }}kg × {{ valorHistoricoSet(activeInlineSet).reps ?? 0 }}r)
                                </button>
                                <button
                                    type="button"
                                    @click="$emit('close-inline-set')"
                                    class="p-1 rounded-lg text-slate-400 hover:text-white cursor-pointer"
                                    aria-label="Cerrar editor rápido"
                                >
                                    <span class="material-symbols-outlined text-[16px]">close</span>
                                </button>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 items-end">
                            <!-- Peso (kg) con botones +/- -->
                            <div>
                                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">
                                    Peso (kg)
                                </label>
                                <div class="flex items-center gap-1">
                                    <button
                                        type="button"
                                        @click="$emit('step-weight', activeInlineSet, -2.5)"
                                        class="h-10 px-2.5 rounded-lg bg-[#101521] border border-[#232D42] text-xs font-mono font-bold text-slate-300 hover:text-white active:scale-95 transition-all cursor-pointer"
                                    >
                                        -2.5
                                    </button>
                                    <input
                                        :value="activeInlineSet.peso"
                                        @input="updateField('peso', $event.target.value)"
                                        type="number"
                                        inputmode="decimal"
                                        step="0.5"
                                        min="0"
                                        placeholder="0"
                                        class="w-full h-10 rounded-lg bg-[#101521] border border-[#232D42] text-center font-mono text-sm font-bold text-white focus:outline-none focus:border-indigo-500"
                                    />
                                    <button
                                        type="button"
                                        @click="$emit('step-weight', activeInlineSet, 2.5)"
                                        class="h-10 px-2.5 rounded-lg bg-indigo-600/20 border border-indigo-500/40 text-xs font-mono font-bold text-indigo-300 hover:bg-indigo-600/30 active:scale-95 transition-all cursor-pointer"
                                    >
                                        +2.5
                                    </button>
                                </div>
                            </div>

                            <!-- Reps con botones +/- -->
                            <div>
                                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">
                                    Repeticiones
                                </label>
                                <div class="flex items-center gap-1">
                                    <button
                                        type="button"
                                        @click="$emit('step-reps', activeInlineSet, -1)"
                                        class="h-10 px-3 rounded-lg bg-[#101521] border border-[#232D42] text-xs font-mono font-bold text-slate-300 hover:text-white active:scale-95 transition-all cursor-pointer"
                                    >
                                        -1
                                    </button>
                                    <input
                                        :value="activeInlineSet.reps_realizadas"
                                        @input="updateField('reps_realizadas', $event.target.value)"
                                        type="number"
                                        inputmode="numeric"
                                        step="1"
                                        min="0"
                                        placeholder="8"
                                        class="w-full h-10 rounded-lg bg-[#101521] border border-[#232D42] text-center font-mono text-sm font-bold text-white focus:outline-none focus:border-indigo-500"
                                    />
                                    <button
                                        type="button"
                                        @click="$emit('step-reps', activeInlineSet, 1)"
                                        class="h-10 px-3 rounded-lg bg-emerald-500/20 border border-emerald-500/40 text-xs font-mono font-bold text-emerald-300 hover:bg-emerald-500/30 active:scale-95 transition-all cursor-pointer"
                                    >
                                        +1
                                    </button>
                                </div>
                            </div>

                            <!-- Botón Completar Set -->
                            <button
                                type="button"
                                @click="$emit('complete-set', activeInlineSet, ej.sets)"
                                class="h-10 px-4 rounded-xl bg-tertiary hover:bg-tertiary-fixed-dim text-on-tertiary font-label-md text-label-md font-bold flex items-center justify-center gap-1.5 shadow-sm active:scale-95 transition-all cursor-pointer"
                            >
                                <span class="material-symbols-outlined text-[18px]">check_circle</span>
                                <span>{{ activeInlineSet.completado ? 'Actualizar serie' : 'Completar serie' }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </article>
        </div>
    </section>
</template>
