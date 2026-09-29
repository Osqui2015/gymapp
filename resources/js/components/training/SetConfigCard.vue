<!--
    SetConfigCard.vue

    Panel de configuración y registro de UNA serie para un ejercicio concreto.
    Se usa desde ActiveWorkoutModal tanto para ejercicios simples (1 panel) como
    para superseries (2 paneles side-by-side en desktop / stacked en mobile).

    Props:
      - ejercicio: objeto del ejercicio (nombre, series_objetivo, reps_min/max, sets[])
      - ejercicioIndex: índice en session.ejercicios (para que el padre registre contra el store)
      - label: pill superior ('CONFIGURAR SERIE #1', 'CALENTAMIENTO #1', etc.)
      - accentColor: 'violet' | 'emerald' | 'amber' para diferenciar visualmente los paneles
      - hideHeader: oculta el header del card (lo usa el padre cuando ya muestra titulos fuera)

    Emite:
      - complete({ ejercicioIndex, peso, reps, tipo_srie, esfuerzo_tipo, esfuerzo_valor, nota_user })
      - deshacer(ejercicioIndex)
-->
<template>
    <section
        class="obs-card-elevated p-3.5 sm:p-4 md:p-5 space-y-3 sm:space-y-3.5 shadow-[0_12px_40px_rgba(0,0,0,0.5)] relative"
        :data-testid="`set-config-card-${ejercicioIndex}`"
    >
        <!-- Header: label + selector de tipo de serie -->
        <div class="flex items-center justify-between border-b border-[var(--color-obsidian-border)] pb-2.5 flex-wrap gap-2">
            <span class="flex items-center gap-2 text-xs font-black uppercase tracking-wider text-gray-300 min-w-0">
                <span
                    class="w-1.5 h-1.5 rounded-full shrink-0"
                    :class="
                        form.tipo_serie === 'calentamiento'
                            ? 'bg-indigo-400'
                            : 'bg-emerald-400'
                    "
                ></span>
                <span class="truncate">
                    <template v-if="form.tipo_serie === 'calentamiento'">
                        Calentamiento #{{ calentamientoNumero }}
                    </template>
                    <template v-else>
                        Serie #{{ serieNumero }}
                    </template>
                </span>
                <span
                    v-if="ejercicio.superserie_grupo"
                    class="obs-pill obs-pill-emerald text-[9px] ml-1"
                >
                    SS {{ ejercicio.superserie_grupo }}
                </span>
            </span>

            <!-- Selector de Tipo de Serie -->
            <div class="inline-flex rounded-xl bg-[var(--color-obsidian-elevated)] p-0.5 sm:p-1 text-[10px] font-bold border border-[var(--color-obsidian-border)]">
                <button
                    v-for="tipo in tiposSerie"
                    :key="tipo.id"
                    type="button"
                    @click="form.tipo_serie = tipo.id"
                    :class="[
                        'px-2 sm:px-2.5 py-1 rounded-lg transition-all cursor-pointer whitespace-nowrap',
                        form.tipo_serie === tipo.id
                            ? tipo.activeClass
                            : 'text-gray-400 hover:text-white',
                    ]"
                >
                    {{ tipo.label }}
                </button>
            </div>
        </div>

        <!-- Nombre del ejercicio (header prominente cuando se muestra; clave
             en superseries para identificar cada card sin mirar la col izq) -->
        <div
            v-if="showExerciseName"
            class="flex items-center gap-2 -mt-1 mb-1 px-2.5 py-1.5 rounded-lg bg-violet-500/10 border border-violet-500/25"
            :data-testid="`set-card-name-${ejercicioIndex}`"
        >
            <span class="w-1.5 h-1.5 rounded-full bg-violet-400 shrink-0"></span>
            <span
                class="text-xs sm:text-sm font-black text-white truncate uppercase tracking-tight"
                :title="ejercicio.nombre"
            >
                {{ ejercicio.nombre }}
            </span>
        </div>

        <!-- CARGA / PESO -->
        <div class="space-y-1.5">
            <div class="flex items-center justify-between">
                <label class="text-[10px] font-black uppercase tracking-[0.16em] text-gray-300">
                    CARGA / PESO
                </label>
                <span class="text-[10px] text-violet-300 font-bold">Toques rápidos (kg)</span>
            </div>

            <div class="flex items-center gap-1 sm:gap-1.5">
                <div class="grid grid-cols-3 gap-1 shrink-0">
                    <button
                        type="button"
                        @click="ajustarPeso(-5)"
                        class="w-9 sm:w-10 h-11 sm:h-12 bg-[var(--color-obsidian-elevated)] hover:bg-rose-500/20 active:scale-95 rounded-xl font-bold text-xs text-rose-300 cursor-pointer border border-[var(--color-obsidian-border)] transition-colors"
                    >
                        -5
                    </button>
                    <button
                        type="button"
                        @click="ajustarPeso(-2.5)"
                        class="w-9 sm:w-10 h-11 sm:h-12 bg-[var(--color-obsidian-elevated)] hover:bg-rose-500/20 active:scale-95 rounded-xl font-bold text-xs text-rose-300 cursor-pointer border border-[var(--color-obsidian-border)] transition-colors"
                    >
                        -2.5
                    </button>
                    <button
                        type="button"
                        @click="ajustarPeso(-1)"
                        class="w-9 sm:w-10 h-11 sm:h-12 bg-[var(--color-obsidian-elevated)] hover:bg-rose-500/20 active:scale-95 rounded-xl font-bold text-xs text-rose-300 cursor-pointer border border-[var(--color-obsidian-border)] transition-colors"
                    >
                        -1
                    </button>
                </div>

                <div
                    class="flex-1 relative flex items-center justify-center bg-gradient-to-br from-[var(--color-obsidian-elevated)] to-[var(--color-obsidian-surface)] border-2 border-violet-500/30 rounded-2xl h-11 sm:h-12 shadow-[0_0_20px_rgba(139,92,246,0.15)]"
                >
                    <input
                        v-model.number="form.peso"
                        type="number"
                        inputmode="decimal"
                        step="0.5"
                        min="0"
                        class="w-full bg-transparent text-center text-2xl sm:text-3xl font-black text-white outline-none tabular-nums"
                        placeholder="0"
                    />
                    <span
                        class="absolute right-2.5 sm:right-3 text-[11px] font-black text-violet-300 uppercase tracking-wider"
                    >kg</span>
                </div>

                <div class="grid grid-cols-3 gap-1 shrink-0">
                    <button
                        type="button"
                        @click="ajustarPeso(1)"
                        class="w-9 sm:w-10 h-11 sm:h-12 bg-[var(--color-obsidian-elevated)] hover:bg-emerald-500/20 active:scale-95 rounded-xl font-bold text-xs text-emerald-300 cursor-pointer border border-[var(--color-obsidian-border)] transition-colors"
                    >
                        +1
                    </button>
                    <button
                        type="button"
                        @click="ajustarPeso(2.5)"
                        class="w-9 sm:w-10 h-11 sm:h-12 bg-[var(--color-obsidian-elevated)] hover:bg-emerald-500/20 active:scale-95 rounded-xl font-bold text-xs text-emerald-300 cursor-pointer border border-[var(--color-obsidian-border)] transition-colors"
                    >
                        +2.5
                    </button>
                    <button
                        type="button"
                        @click="ajustarPeso(5)"
                        class="w-9 sm:w-10 h-11 sm:h-12 bg-[var(--color-obsidian-elevated)] hover:bg-emerald-500/20 active:scale-95 rounded-xl font-bold text-xs text-emerald-300 cursor-pointer border border-[var(--color-obsidian-border)] transition-colors"
                    >
                        +5
                    </button>
                </div>
            </div>
        </div>

        <!-- REPETICIONES -->
        <div class="space-y-1.5">
            <div class="flex items-center justify-between">
                <label class="text-[10px] font-black uppercase tracking-[0.16em] text-gray-300">
                    REPETICIONES
                </label>
                <span class="text-[10px] text-emerald-300 font-bold tabular-nums">
                    Objetivo: {{ ejercicio.reps_min }}–{{ ejercicio.reps_max }}
                </span>
            </div>

            <div class="flex items-center gap-1 sm:gap-1.5">
                <div class="grid grid-cols-2 gap-1 shrink-0">
                    <button
                        type="button"
                        @click="ajustarReps(-2)"
                        class="w-10 sm:w-12 h-11 sm:h-12 bg-[var(--color-obsidian-elevated)] hover:bg-rose-500/20 active:scale-95 rounded-xl font-bold text-xs sm:text-sm text-rose-300 cursor-pointer border border-[var(--color-obsidian-border)] transition-colors"
                    >
                        -2
                    </button>
                    <button
                        type="button"
                        @click="ajustarReps(-1)"
                        class="w-10 sm:w-12 h-11 sm:h-12 bg-[var(--color-obsidian-elevated)] hover:bg-rose-500/20 active:scale-95 rounded-xl font-bold text-xs sm:text-sm text-rose-300 cursor-pointer border border-[var(--color-obsidian-border)] transition-colors"
                    >
                        -1
                    </button>
                </div>

                <div
                    class="flex-1 relative flex items-center justify-center bg-gradient-to-br from-[var(--color-obsidian-elevated)] to-[var(--color-obsidian-surface)] border-2 border-emerald-500/30 rounded-2xl h-11 sm:h-12 shadow-[0_0_20px_rgba(16,185,129,0.15)]"
                >
                    <input
                        v-model.number="form.reps"
                        type="number"
                        inputmode="numeric"
                        min="0"
                        class="w-full bg-transparent text-center text-2xl sm:text-3xl font-black text-white outline-none tabular-nums"
                        placeholder="0"
                    />
                    <span
                        class="absolute right-2.5 sm:right-3 text-[11px] font-black text-emerald-300 uppercase tracking-wider"
                    >reps</span>
                </div>

                <div class="grid grid-cols-2 gap-1 shrink-0">
                    <button
                        type="button"
                        @click="ajustarReps(1)"
                        class="w-10 sm:w-12 h-11 sm:h-12 bg-[var(--color-obsidian-elevated)] hover:bg-emerald-500/20 active:scale-95 rounded-xl font-bold text-xs sm:text-sm text-emerald-300 cursor-pointer border border-[var(--color-obsidian-border)] transition-colors"
                    >
                        +1
                    </button>
                    <button
                        type="button"
                        @click="ajustarReps(2)"
                        class="w-10 sm:w-12 h-11 sm:h-12 bg-[var(--color-obsidian-elevated)] hover:bg-emerald-500/20 active:scale-95 rounded-xl font-bold text-xs sm:text-sm text-emerald-300 cursor-pointer border border-[var(--color-obsidian-border)] transition-colors"
                    >
                        +2
                    </button>
                </div>
            </div>
        </div>

        <!-- Esfuerzo (RIR / RPE) -->
        <div class="bg-[var(--color-obsidian-surface)] rounded-xl p-2.5 sm:p-3 border border-[var(--color-obsidian-border)] space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-gray-300">Esfuerzo percibido:</span>
                <div class="inline-flex rounded-lg bg-[var(--color-obsidian-elevated)] p-0.5 text-[10px] font-black border border-[var(--color-obsidian-border)]">
                    <button
                        type="button"
                        @click="form.esfuerzo_tipo = 'rir'"
                        :class="
                            form.esfuerzo_tipo === 'rir'
                                ? 'bg-emerald-500 text-white shadow-[0_0_12px_rgba(16,185,129,0.4)]'
                                : 'text-gray-400'
                        "
                        class="px-2.5 py-1 rounded-md transition-colors cursor-pointer"
                    >
                        RIR
                    </button>
                    <button
                        type="button"
                        @click="form.esfuerzo_tipo = 'rpe'"
                        :class="
                            form.esfuerzo_tipo === 'rpe'
                                ? 'bg-amber-500 text-white shadow-[0_0_12px_rgba(245,158,11,0.4)]'
                                : 'text-gray-400'
                        "
                        class="px-2.5 py-1 rounded-md transition-colors cursor-pointer"
                    >
                        RPE
                    </button>
                </div>
            </div>

            <div class="flex items-center justify-between gap-1 overflow-x-auto py-0.5">
                <button
                    v-for="val in esfuerzoOptions"
                    :key="val"
                    type="button"
                    @click="form.esfuerzo_valor = form.esfuerzo_valor === val ? null : val"
                    :class="[
                        'min-w-8 sm:min-w-9 h-10 px-1.5 sm:px-2 rounded-xl font-black text-xs transition-all cursor-pointer flex flex-col items-center justify-center gap-0.5',
                        form.esfuerzo_valor === val
                            ? form.esfuerzo_tipo === 'rir'
                                ? 'bg-emerald-500 text-white shadow-[0_0_18px_rgba(16,185,129,0.5)] scale-105'
                                : 'bg-amber-500 text-white shadow-[0_0_18px_rgba(245,158,11,0.5)] scale-105'
                            : 'bg-[var(--color-obsidian-elevated)] text-gray-300 hover:bg-[var(--color-obsidian-overlay)] hover:text-white border border-[var(--color-obsidian-border)]',
                    ]"
                >
                    <span>{{ val }}</span>
                    <span class="text-[8px] uppercase opacity-80">
                        {{ esfuerzoLabel(val) }}
                    </span>
                </button>
            </div>
        </div>

        <!-- Botón gigante COMPLETAR SERIE -->
        <button
            v-if="!ejercicioCompleto"
            type="button"
            @click="onCompletar"
            :data-testid="`btn-completar-${ejercicioIndex}`"
            class="w-full py-3.5 sm:py-4 rounded-2xl bg-gradient-to-br from-[var(--color-violet-deep)] via-[var(--color-violet-primary)] to-[var(--color-violet-light)] hover:brightness-110 active:scale-[0.98] text-white text-sm sm:text-base font-black tracking-wider shadow-[0_12px_32px_var(--color-violet-glow)] flex items-center justify-center gap-2.5 transition-all cursor-pointer border border-white/10"
        >
            <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="3"
                    d="M5 13l4 4L19 7"
                />
            </svg>
            <span v-if="form.tipo_serie === 'calentamiento'">
                COMPLETAR CALENTAMIENTO #{{ calentamientoNumero }}
            </span>
            <span v-else>
                COMPLETAR SERIE #{{ serieNumero }}
            </span>
        </button>

        <div
            v-else
            class="w-full py-3.5 sm:py-4 rounded-2xl bg-emerald-900/30 border border-emerald-500/40 text-emerald-300 text-sm sm:text-base font-black tracking-wider flex items-center justify-center gap-2.5"
        >
            ✓ Ejercicio completo
        </div>
    </section>
</template>

<script setup>
import { ref, computed, watch } from 'vue';

const props = defineProps({
    ejercicio: {
        type: Object,
        required: true,
    },
    ejercicioIndex: {
        type: Number,
        required: true,
    },
    showExerciseName: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['complete', 'deshacer']);

const tiposSerie = [
    { id: 'efectiva', label: 'Efectiva', activeClass: 'bg-emerald-500 text-white' },
    { id: 'calentamiento', label: 'Calentamiento', activeClass: 'bg-indigo-500 text-white' },
    { id: 'dropset', label: 'Drop Set', activeClass: 'bg-amber-500 text-white' },
    { id: 'al_fallo', label: 'Al Fallo', activeClass: 'bg-rose-500 text-white' },
];

const form = ref({
    peso: 0,
    reps: 0,
    tipo_serie: 'efectiva',
    esfuerzo_tipo: 'rir',
    esfuerzo_valor: null,
    nota_user: '',
});

// Contadores independientes por ejercicio (clave para superseries, donde cada
// ejercicio tiene su propio avance). Se calculan contando sets en ej.sets
// directamente, NO desde currentSerieNumero global del store.
const setsEfectivos = computed(() => {
    return (props.ejercicio.sets || []).filter((s) => s.tipo_serie !== 'calentamiento');
});
const setsCalentamiento = computed(() => {
    return (props.ejercicio.sets || []).filter((s) => s.tipo_serie === 'calentamiento');
});

const serieNumero = computed(() => {
    // Si el último set es calentamiento, no queremos que 'saltemos' la serie;
    // usamos la cantidad de efectivos + 1, o si la ultima serie es efectiva
    // entonces la próxima serie es setsEfectivos.length + 1.
    return setsEfectivos.value.length + 1;
});

const calentamientoNumero = computed(() => {
    return setsCalentamiento.value.length + 1;
});

const ejercicioCompleto = computed(() => {
    return setsEfectivos.value.length >= Number(props.ejercicio.series_objetivo || 0);
});

const esfuerzoOptions = computed(() => {
    return form.value.esfuerzo_tipo === 'rir' ? [0, 1, 2, 3, 4, 5] : [6, 7, 8, 9, 10];
});

const esfuerzoLabel = (val) => {
    const rir = ['Fallo', 'Máx', 'Óptimo', 'Medio', 'Fácil', 'Calent.'];
    const rpe = ['Fácil', 'Fácil+', 'Medio', 'Medio+', 'Máx'];
    if (form.value.esfuerzo_tipo === 'rir') {
        return rir[Math.min(val, 5)] || '';
    }
    return rpe[Math.min(Math.max(val - 6, 0), 4)] || '';
};

// Pre-rellenar el form con el último set del ejercicio (si hay) o con la
// recomendación por defecto de reps_min.
watch(
    () => [props.ejercicio?.nombre, (props.ejercicio?.sets || []).length],
    () => {
        const sets = props.ejercicio?.sets || [];
        if (sets.length > 0) {
            // Copiamos los valores del último set NO-calentamiento para
            // mantener la consistencia entre series del mismo ejercicio.
            const lastEffective = [...sets].reverse().find((s) => s.tipo_serie !== 'calentamiento');
            const source = lastEffective || sets[sets.length - 1];
            form.value.peso = source.peso || 0;
            form.value.reps = source.reps || 0;
            form.value.tipo_serie = source.tipo_serie || 'efectiva';
            form.value.esfuerzo_tipo = source.esfuerzo_tipo || 'rir';
            form.value.esfuerzo_valor = source.esfuerzo_valor ?? null;
        } else {
            form.value.peso = 0;
            form.value.reps = Number(props.ejercicio?.reps_min) || 8;
            form.value.tipo_serie = 'efectiva';
            form.value.esfuerzo_tipo = 'rir';
            form.value.esfuerzo_valor = null;
        }
    },
    { immediate: true }
);

const ajustarPeso = (delta) => {
    const nuevo = Math.max(0, (Number(form.value.peso) || 0) + delta);
    form.value.peso = Math.round(nuevo * 2) / 2;
};

const ajustarReps = (delta) => {
    form.value.reps = Math.max(0, (Number(form.value.reps) || 0) + delta);
};

const onCompletar = () => {
    emit('complete', {
        ejercicioIndex: props.ejercicioIndex,
        peso: Number(form.value.peso) || 0,
        reps: Number(form.value.reps) || 0,
        tipo_serie: form.value.tipo_serie,
        esfuerzo_tipo: form.value.esfuerzo_tipo,
        esfuerzo_valor: form.value.esfuerzo_valor,
        nota_user: form.value.nota_user,
    });
};
</script>
