<!--
    PlanSeriesChips.vue

    Versión COMPACTA del plan de series, para las listas densas (la del
    dashboard con las series del día y el acordeón de rutinas).

    Muestra una fila de chips con los bloques (ej. "2×6 RIR 1 → 2×4 RIR 0"),
    un badge cuando el ejercicio es rest-pause y, opcionalmente, el criterio
    de progresión.

    Usa la util compartida `utils/rutinaPrescripcion.js`, que prefiere el JSON
    estructurado (`ejercicio.prescripcion`) y cae al parseo de `notas`.
-->
<template>
    <div v-if="prescripcion.bloques.length" class="space-y-0.5">
        <div class="flex flex-wrap items-center gap-1">
            <template v-for="(bloque, i) in prescripcion.bloques" :key="i">
                <span
                    v-if="i > 0"
                    class="text-[9px] font-black text-gray-600"
                    aria-hidden="true"
                >
                    →
                </span>
                <span
                    class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-md text-[9px] font-black"
                    :class="claseBloque(bloque)"
                    :title="`${bloque.series} ${bloque.series === 1 ? 'serie' : 'series'} × ${bloque.reps} reps con ${etiquetaEsfuerzo(bloque.tipo, bloque.valor)}`"
                >
                    {{ bloque.series }}×{{ bloque.reps }}
                    <span class="opacity-75">{{ etiquetaEsfuerzo(bloque.tipo, bloque.valor) }}</span>
                </span>
            </template>

            <span
                v-if="prescripcion.restPause"
                class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-md text-[9px] font-black bg-amber-500/20 text-amber-600 dark:text-amber-300 border border-amber-500/30"
                :title="`Rest-pause: ${resumenRestPause}`"
            >
                ⏱ REST-PAUSE
            </span>
        </div>

        <p
            v-if="mostrarProgresion && prescripcion.progresion"
            class="text-[10px] leading-snug text-teal-700 dark:text-teal-300/90 line-clamp-2"
            :title="prescripcion.progresion"
        >
            ↗ {{ prescripcion.progresion }}
        </p>

        <p
            v-if="mostrarConsejo && prescripcion.consejo"
            class="text-[11px] leading-relaxed text-gray-600 dark:text-gray-300 italic"
            :title="prescripcion.consejo"
        >
            💡 {{ prescripcion.consejo }}
        </p>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import {
    prescripcionDe,
    etiquetaEsfuerzo,
    resumenRestPause as buildResumenRestPause,
} from '@/utils/rutinaPrescripcion';

const props = defineProps({
    ejercicio: { type: Object, default: null },
    mostrarProgresion: { type: Boolean, default: true },
    mostrarConsejo: { type: Boolean, default: false },
    /**
     * 'dark' → siempre fondo oscuro (página /rutinas).
     * 'auto' → segue el tema de la app (dashboard).
     */
    tema: { type: String, default: 'dark' },
});

const prescripcion = computed(() => prescripcionDe(props.ejercicio));

const resumenRestPause = computed(() => buildResumenRestPause(prescripcion.value));

const claseBloque = (b) => {
    const esFallo = b.tipo === 'fallo' || (b.tipo === 'rir' && Number(b.valor) === 0);
    const esLimite = b.tipo === 'rir' && Number(b.valor) === 1;

    if (props.tema === 'dark') {
        if (esFallo) {
            return 'bg-rose-500/25 text-rose-200 border border-rose-500/40';
        }
        if (esLimite) {
            return 'bg-amber-500/20 text-amber-300 border border-amber-500/30';
        }
        return 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30';
    }

    if (esFallo) {
        return 'bg-rose-500/15 text-rose-700 dark:text-rose-300 border border-rose-500/30';
    }
    if (esLimite) {
        return 'bg-amber-500/15 text-amber-700 dark:text-amber-300 border border-amber-500/30';
    }
    return 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30';
};
</script>
