<!--
    PrescripcionCard.vue

    Renderiza la prescripción de UN ejercicio de forma estructurada, en vez de
    dejar que el usuario lea un bloque de texto en `notas`.

    Consume la util compartida `utils/rutinaPrescripcion.js`, que a su vez
    prefiere el JSON estructurado (`ejercicio.prescripcion`) y cae al parseo
    de `notas` para las rutinas que todavía no lo tienen.

    Muestra:
      - Los bloques de series (ej. "2×6 RIR 1 → 2×4 RIR 0") con color según
        el tipo de esfuerzo.
      - El rest-pause como cadena real de sets (set → 5" → set → 10" → set)
        y el volumen real en sets.
      - La progresión (cuándo/what subir) y el consejo (técnica/condiciones).

    Props:
      - ejercicio: objeto del ejercicio (notas / prescripcion / nombre).
      - prescripcion: prescripción ya normalizada (opcional, evita recomputar).
      - serieActual: número de serie 1-indexed para resaltar el bloque actual.
      - compact: modo condensado (listas de rutinas).
      - mostrarNotas: agrega al final el texto crudo de `notas`.
-->
<template>
    <div
        v-if="tieneContenido"
        class="space-y-2"
        data-testid="prescripcion-card"
    >
        <!-- BLOQUES DE SERIES -->
        <div
            v-if="prescripcion.bloques.length"
            class="flex flex-wrap items-center gap-1.5"
            data-testid="prescripcion-bloques"
        >
            <template v-for="(bloque, i) in prescripcion.bloques" :key="i">
                <span
                    v-if="i > 0"
                    class="text-[10px] font-black text-gray-600 shrink-0"
                    aria-hidden="true"
                >
                    →
                </span>
                <span
                    class="inline-flex items-center gap-1.5 px-2 py-1 rounded-lg border text-[11px] font-black leading-none"
                    :class="[
                        clasesBloque(bloque),
                        esBloqueActual(bloque) ? 'ring-2 ring-white/30' : '',
                    ]"
                    :data-testid="`prescripcion-bloque-${i}`"
                    :title="`${bloque.series} ${bloque.series === 1 ? 'serie' : 'series'} × ${bloque.reps} reps con ${etiquetaEsfuerzo(bloque.tipo, bloque.valor)}`"
                >
                    <span class="tabular-nums">
                        {{ bloque.series }}
                        {{ bloque.series === 1 ? 'serie' : 'series' }} × {{ bloque.reps }}
                        reps
                    </span>
                    <span class="opacity-80 font-bold">
                        {{ etiquetaEsfuerzo(bloque.tipo, bloque.valor) }}
                    </span>
                </span>
            </template>

            <span
                v-if="prescripcion.etiqueta"
                class="px-1.5 py-0.5 rounded-md bg-violet-500/15 border border-violet-500/30 text-violet-300 text-[9px] font-black uppercase tracking-wider"
            >
                {{ prescripcion.etiqueta }}
            </span>
        </div>

        <!-- REST-PAUSE: cadena real de sets -->
        <div
            v-if="prescripcion.restPause"
            class="rounded-xl border border-amber-500/25 bg-amber-500/5 px-2.5 py-2 space-y-1.5"
            data-testid="prescripcion-rest-pause"
        >
            <div class="flex items-center justify-between gap-2 flex-wrap">
                <span class="inline-flex items-center gap-1 text-[9px] font-black uppercase tracking-[0.14em] text-amber-300">
                    <span>⏱</span>
                    <span>Rest-pause</span>
                </span>
                <span class="text-[10px] font-bold text-amber-200/90 tabular-nums">
                    {{ resumenRestPause }}
                </span>
            </div>

            <!-- Cadena: set principal → descanso → mini-set → descanso → mini-set -->
            <div class="flex flex-wrap items-center gap-1 text-[10px] font-bold">
                <span
                    class="px-1.5 py-0.5 rounded-md bg-emerald-500/15 border border-emerald-500/30 text-emerald-300"
                    :title="`Set principal: ${prescripcion.bloques[0]?.reps ?? 0} reps`"
                >
                    {{ prescripcion.bloques[0]?.reps ?? 0 }} reps
                    <template v-if="prescripcion.bloques[0]">
                        · {{ etiquetaEsfuerzo(prescripcion.bloques[0].tipo, prescripcion.bloques[0].valor) }}
                    </template>
                </span>

                <template v-for="(mini, i) in prescripcion.restPause.minisets" :key="`mini-${i}`">
                    <span class="text-amber-400/80 shrink-0">→</span>
                    <span class="text-amber-300/90 tabular-nums shrink-0" :title="`Descanso ${mini.descansoSeg} segundos`">
                        {{ mini.descansoSeg }}"
                    </span>
                    <span class="text-amber-400/80 shrink-0">→</span>
                    <span
                        class="px-1.5 py-0.5 rounded-md bg-rose-500/15 border border-rose-500/30 text-rose-300"
                        :title="`Mini-set: ${mini.reps} reps al fallo`"
                    >
                        {{ mini.reps }} reps · FALLO
                    </span>
                </template>
            </div>

            <p class="text-[10px] text-amber-200/70 leading-snug">
                Cada serie son {{ setsEfectivos }} sets: el principal + {{ prescripcion.restPause.minisets.length }} mini-sets al fallo.
            </p>
        </div>

        <!-- PROGRESIÓN -->
        <div
            v-if="prescripcion.progresion"
            class="flex items-start gap-1.5 text-[11px] text-teal-200"
            data-testid="prescripcion-progresion"
        >
            <span class="text-teal-400 font-bold shrink-0">↗</span>
            <span class="font-semibold leading-snug">{{ prescripcion.progresion }}</span>
        </div>

        <!-- CONSEJO -->
        <div
            v-if="prescripcion.consejo"
            class="flex items-start gap-1.5 text-[11px] text-gray-300"
            data-testid="prescripcion-consejo"
        >
            <span class="text-amber-400 font-bold shrink-0">ℹ</span>
            <span class="font-medium leading-snug">{{ prescripcion.consejo }}</span>
        </div>

        <!-- NOTAS CRUDAS (opcional) -->
        <p
            v-if="mostrarNotas && ejercicio?.notas"
            class="text-[10px] text-gray-500 leading-snug border-t border-white/5 pt-1.5"
        >
            {{ ejercicio.notas }}
        </p>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import {
    prescripcionDe,
    etiquetaEsfuerzo,
    resumenRestPause as buildResumenRestPause,
    setsEfectivos as countSetsEfectivos,
} from '../../utils/rutinaPrescripcion';

const props = defineProps({
    ejercicio: { type: Object, default: null },
    prescripcion: { type: Object, default: null },
    serieActual: { type: Number, default: 0 },
    compact: { type: Boolean, default: false },
    mostrarNotas: { type: Boolean, default: false },
});

const prescripcion = computed(() => props.prescripcion || prescripcionDe(props.ejercicio));

const tieneContenido = computed(() => {
    const p = prescripcion.value;
    return !!p && (p.bloques.length > 0 || !!p.restPause || !!p.progresion || !!p.consejo);
});

const setsEfectivos = computed(() => countSetsEfectivos(prescripcion.value));

const resumenRestPause = computed(() => buildResumenRestPause(prescripcion.value));

/**
 * Índice de la serie (1-indexed) a la que pertenece el bloque, para poder
 * resaltar el bloque "actual" de la sesión.
 */
const rangoBloque = (bloque) => {
    let acumulado = 0;
    for (const b of prescripcion.value.bloques) {
        const desde = acumulado + 1;
        acumulado += b.series;
        if (b === bloque) return { desde, hasta: acumulado };
    }
    return { desde: 0, hasta: 0 };
};

const esBloqueActual = (bloque) => {
    if (!props.serieActual) return false;
    const { desde, hasta } = rangoBloque(bloque);
    return props.serieActual >= desde && props.serieActual <= hasta;
};

/**
 * Color por tipo de esfuerzo:
 *   rose  → al fallo (FALLO o RIR 0, que es lo mismo)
 *   amber → RIR 1 / RPE 9-10 (al límite, se puede ir una rep más)
 *   verde → RIR 2+ (zona cómoda, se reserva reps en el banco)
 */
const clasesBloque = (bloque) => {
    if (bloque.tipo === 'fallo' || (bloque.tipo === 'rir' && Number(bloque.valor) === 0)) {
        return 'bg-rose-500/15 border-rose-500/40 text-rose-300';
    }
    if (bloque.tipo === 'rir' && Number(bloque.valor) === 1) {
        return 'bg-amber-500/15 border-amber-500/40 text-amber-300';
    }
    if (bloque.tipo === 'rpe' && Number(bloque.valor) >= 9) {
        return 'bg-amber-500/15 border-amber-500/40 text-amber-300';
    }
    return 'bg-emerald-500/15 border-emerald-500/35 text-emerald-300';
};
</script>
