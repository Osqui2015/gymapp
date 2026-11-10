<!--
    SeriesRegistradasList.vue

    Lista de series ya completadas de un ejercicio.

    Se usa en dos lugares del ActiveWorkoutModal:
      - Columna izquierda (desktop): lista siempre visible dentro de una <section>.
      - Columna derecha (mobile): lista dentro de un <details> desplegable.

    Por eso el componente NO trae el contenedor (section / details): solo el
    contenido, y el padre decide el markup y las clases del wrapper.

    Props:
      - sets: array de sets del ejercicio (set.peso, set.reps, set.tipo_serie,
        set.esfuerzo_tipo, set.esfuerzo_valor, set.completed_at, set.series_numero)
      - variant: 'desktop' | 'mobile' (define el padding y el tamaño de texto
        de cada fila, replicando exactamente el markup original)

    El número de serie de calentamiento se resuelve con
    `numeroSerieParaDisplay` (importada acá, no llega por prop).
-->
<template>
    <div
        v-for="(s, idx) in sets"
        :key="idx"
        class="flex items-center justify-between rounded-lg bg-[var(--color-obsidian-surface)] border border-[var(--color-obsidian-border)]"
        :class="
            variant === 'mobile'
                ? 'px-2 py-1 text-[11px]'
                : 'px-2.5 py-1.5 text-xs'
        "
    >
        <div class="flex items-center gap-1.5 flex-wrap">
            <span
                class="font-black tabular-nums"
                :class="
                    s.tipo_serie === 'calentamiento'
                        ? 'text-indigo-300'
                        : 'text-emerald-300'
                "
            >
                <template v-if="s.tipo_serie === 'calentamiento'">
                    Calentamiento {{ numeroSerieParaDisplay(s.series_numero) }}
                </template>
                <template v-else>
                    #{{ s.series_numero }}
                </template>
            </span>
            <span class="font-black text-white tabular-nums">{{ s.peso }} kg</span>
            <span class="text-gray-500">×</span>
            <span class="font-black text-white tabular-nums">{{ s.reps }} reps</span>
            <span
                v-if="s.tipo_serie && s.tipo_serie !== 'efectiva'"
                class="obs-pill obs-pill-amber text-[9px]"
            >
                {{ s.tipo_serie }}
            </span>
            <span
                v-if="s.esfuerzo_valor != null"
                class="obs-pill obs-pill-neutral text-[9px]"
            >
                {{ s.esfuerzo_tipo?.toUpperCase() }} {{ s.esfuerzo_valor }}
            </span>
        </div>
        <span class="text-[10px] text-gray-500 font-mono tabular-nums">
            {{ formatSetTime(s.completed_at) }}
        </span>
    </div>
</template>

<script setup>
import { numeroSerieParaDisplay } from '@/utils/seriesNumero';

defineProps({
    sets: {
        type: Array,
        default: () => [],
    },
    variant: {
        type: String,
        default: 'desktop',
    },
});

const formatSetTime = (isoString) => {
    if (!isoString) return '';
    const d = new Date(isoString);
    return `${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`;
};
</script>
