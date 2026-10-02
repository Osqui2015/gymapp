<!--
    NotasEditor.vue

    Editor de texto libre para el campo `rutinas.notas` con validación live
    y preview de los bloques de esfuerzo que se van a parsear.

    Formatos soportados (todos case-insensitive):
      • "2x6 RIR 1"             → RIR con valor 0-5
      • "2x6 RPE 8"             → RPE con valor 6-10
      • "2x8 FALLO"             → al fallo absoluto
      • "2x8 AL FALLO TÉCNICO"  → idem con decoración libre
      • Se concatenan con " + " o "," o saltos de línea.

    Props:
      - modelValue: string actual de las notas
      - seriesTotales: número total de series que tiene el ejercicio (para validar
                       que los bloques sumen exactamente N series).

    Emite:
      - update:modelValue cuando el usuario tipea.
-->
<template>
    <div class="space-y-1.5">
        <!-- Input principal -->
        <div class="relative">
            <input
                :value="modelValue"
                @input="onInput"
                type="text"
                :placeholder="placeholder"
                class="w-full px-2 py-1 text-xs border border-gray-300 dark:border-gray-600 rounded dark:bg-gray-700 dark:text-gray-200 focus:ring-1 focus:ring-indigo-500 font-mono"
                data-testid="notas-editor-input"
            />
        </div>

        <!-- Preview en vivo de los bloques parseados -->
        <div
            v-if="modelValue && modelValue.trim().length > 0"
            class="flex flex-wrap items-center gap-1"
            data-testid="notas-editor-preview"
        >
            <template v-if="blocks.length > 0">
                <span
                    v-for="(b, i) in blocks"
                    :key="i"
                    class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-md text-[9px] font-black"
                    :class="blockClass(b)"
                >
                    {{ b.series }}×{{ b.reps }}
                    <span class="opacity-75">{{ blockLabel(b) }}</span>
                </span>
                <span class="text-[9px] text-gray-500 dark:text-gray-400">
                    ({{ totalSeriesParsed }}/{{ Number(seriesTotales) || '?' }} series cubiertas)
                </span>
            </template>
            <span
                v-else
                class="text-[9px] text-rose-500 dark:text-rose-400 font-bold"
            >
                ⚠ No se reconocieron bloques. Probá con "2x6 RIR 1" o "2x8 FALLO".
            </span>
        </div>

        <!-- Hint / ayuda -->
        <details class="text-[10px] text-gray-500 dark:text-gray-400">
            <summary class="cursor-pointer hover:text-indigo-500">
                ¿Cómo escribir las notas?
            </summary>
            <div class="mt-1 space-y-0.5 pl-2 border-l-2 border-gray-300 dark:border-gray-600">
                <div><strong>RIR</strong>: <code>2x6 RIR 1</code> = 2 series de 6 reps @ RIR 1</div>
                <div><strong>RPE</strong>: <code>2x8 RPE 8</code> = 2 series de 8 reps @ RPE 8</div>
                <div><strong>FALLO</strong>: <code>2x8 FALLO</code> = 2 series al fallo absoluto</div>
                <div class="pt-0.5">
                    Combinar: <code>2x6 RIR 1 + 2x4 FALLO</code>
                </div>
                <div class="text-gray-400 italic">
                    La suma de series de los bloques debería coincidir con el total de series del ejercicio.
                </div>
            </div>
        </details>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    modelValue: { type: String, default: '' },
    seriesTotales: { type: [Number, String], default: 0 },
});

const emit = defineEmits(['update:modelValue']);

const placeholder = computed(() => {
    const n = Number(props.seriesTotales) || 3;
    return `Ej: 2x${n - 2} RIR 1 + ${Math.ceil(n / 2)}x${n - 2} FALLO`;
});

const onInput = (e) => {
    emit('update:modelValue', e.target.value);
};

const blocks = computed(() => parseEsfuerzoBlocks(props.modelValue));

const totalSeriesParsed = computed(() =>
    blocks.value.reduce((acc, b) => acc + b.series, 0)
);

// Reutilizamos el mismo regex/parser que en RutinaAcordeon.vue y ActiveWorkoutModal.vue
// para que el preview coincida exactamente con lo que verá el usuario en la app.
function parseEsfuerzoBlocks(notas) {
    if (!notas) return [];
    const out = [];
    const re = /(\d+)x(\d+)\s+(?:RIR\s*(\d+)|RPE\s*(\d+)|(?:AL\s+)?FALLO(?:\s+T[ÉE]CNICO)?)/gi;
    for (const m of String(notas).matchAll(re)) {
        if (m[3] !== undefined) {
            out.push({ series: Number(m[1]), reps: Number(m[2]), tipo: 'rir', valor: Number(m[3]) });
        } else if (m[4] !== undefined) {
            out.push({ series: Number(m[1]), reps: Number(m[2]), tipo: 'rpe', valor: Number(m[4]) });
        } else {
            out.push({ series: Number(m[1]), reps: Number(m[2]), tipo: 'fallo', valor: null });
        }
    }
    return out;
}

function blockLabel(b) {
    if (b.tipo === 'fallo') return 'FALLO';
    return `${b.tipo.toUpperCase()} ${b.valor}`;
}

function blockClass(b) {
    if (b.tipo === 'fallo') return 'bg-rose-500/25 text-rose-200 border border-rose-500/40';
    if (b.tipo === 'rir' && b.valor === 0) return 'bg-rose-500/25 text-rose-300 border border-rose-500/40';
    return 'bg-amber-500/20 text-amber-300 border border-amber-500/30';
}
</script>
