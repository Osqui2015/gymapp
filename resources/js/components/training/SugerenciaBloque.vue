<!--
    SugerenciaBloque.vue

    Bloque de sugerencia del Hero card del ActiveWorkoutModal.

    Caso 1 (ejercicio individual): muestra la sugerencia del ejercicio actual.
    Caso 2 (superserie): muestra las sugerencias de ambos ejercicios del par.

    Props:
      - recomendacion: sugerencia del ejercicio actual (o null)
      - recomendacionFirst: sugerencia del primer ejercicio del par (o null)
      - recomendacionSecond: sugerencia del segundo ejercicio del par (o null)
      - superserieOrder: { first, second } o null
      - lastExerciseData: histórico del ejercicio actual (para comparar el peso)
-->
<template>
    <div
        v-if="!superserieOrder && recomendacion"
        class="flex items-start gap-1.5 text-[10px] sm:text-[11px] text-emerald-400 leading-tight mt-1.5 font-medium"
    >
        <svg class="w-3.5 h-3.5 text-emerald-400 shrink-0 mt-0.5" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1.414 1.414 0 001.414 0l4-4z" clip-rule="evenodd" />
        </svg>
        <span>
            <strong class="font-bold">Sugerencia:</strong>
            {{ recomendacion.mensaje }}
            <span
                v-if="recomendacion.pesoSugerido != null && recomendacion.pesoSugerido !== (lastExerciseData?.peso_max ?? lastExerciseData?.peso_top)"
                class="font-black tabular-nums"
            >
                ({{ formatPeso(recomendacion.pesoSugerido) }} kg)
            </span>
        </span>
    </div>
    <div
        v-else-if="superserieOrder && (recomendacionFirst || recomendacionSecond)"
        class="flex flex-col gap-1 mt-1.5 text-[10px] sm:text-[11px] text-emerald-400 leading-tight font-medium"
    >
        <div v-if="recomendacionFirst" class="flex items-start gap-1.5">
            <svg class="w-3.5 h-3.5 text-emerald-400 shrink-0 mt-0.5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1.414 1.414 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
            <span>
                <strong class="font-bold">Sugerencia {{ superserieOrder.first.ejercicio.nombre }}:</strong>
                {{ recomendacionFirst.mensaje }}
            </span>
        </div>
        <div v-if="recomendacionSecond" class="flex items-start gap-1.5">
            <svg class="w-3.5 h-3.5 text-emerald-400 shrink-0 mt-0.5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1.414 1.414 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
            <span>
                <strong class="font-bold">Sugerencia {{ superserieOrder.second.ejercicio.nombre }}:</strong>
                {{ recomendacionSecond.mensaje }}
            </span>
        </div>
    </div>
</template>

<script setup>
defineProps({
    recomendacion: {
        type: Object,
        default: null,
    },
    recomendacionFirst: {
        type: Object,
        default: null,
    },
    recomendacionSecond: {
        type: Object,
        default: null,
    },
    superserieOrder: {
        type: Object,
        default: null,
    },
    lastExerciseData: {
        type: Object,
        default: null,
    },
});

// Formateo amigable: 2.5, 60, 100 (sin decimales raros para enteros).
const formatPeso = (val) => {
    const n = Number(val);
    if (!Number.isFinite(n)) return '0';
    return Number.isInteger(n) ? String(n) : n.toFixed(1).replace(/\.0$/, '');
};
</script>
