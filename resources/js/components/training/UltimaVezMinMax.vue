<!--
    UltimaVezMinMax.vue

    Grilla de "última vez" (MÍN / TOP) del ejercicio actual en el Hero card,
    para el caso de ejercicio individual (sin superserie).

    Props:
      - lastData: objeto con el histórico (peso_min, peso_max, peso_top,
        reps_en_peso_min/max/top, ultimo_esfuerzo)
-->
<template>
    <div class="mt-1">
        <div
            class="grid grid-cols-2 gap-1.5"
            data-testid="last-exercise-card"
        >
            <!-- MÍNIMO -->
            <div
                class="px-2 py-1 rounded-xl bg-[var(--color-obsidian-surface)] border border-cyan-500/25 flex items-center justify-between gap-1"
                data-testid="last-exercise-min"
            >
                <div class="flex items-center gap-1 text-[9px] font-black text-teal-400 uppercase shrink-0">
                    <svg class="w-2.5 h-2.5 text-teal-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3a3 3 0 00-3 3v1H7a2 2 0 00-2 2v9a2 2 0 002 2h10a2 2 0 002-2V9a2 2 0 00-2-2h-2V6a3 3 0 00-3-3zm-1 4a1 1 0 012 0v1h-2V7z" />
                    </svg>
                    <span>MÍN</span>
                </div>
                <div class="text-[11px] sm:text-xs font-black text-white tabular-nums text-right truncate">
                    {{ formatPeso(lastData?.peso_min ?? lastData?.peso_top ?? 0) }} kg
                    <span class="text-[9px] text-gray-400 font-normal">({{ (lastData?.reps_en_peso_min ?? lastData?.reps_en_peso_top) ?? 0 }} reps)</span>
                </div>
            </div>

            <!-- MÁXIMO (TOP) -->
            <div
                class="px-2 py-1 rounded-xl bg-[var(--color-obsidian-surface)] border border-fuchsia-500/25 flex items-center justify-between gap-1"
                data-testid="last-exercise-max"
            >
                <div class="flex items-center gap-1 text-[9px] font-black text-fuchsia-400 uppercase shrink-0">
                    <span>▲ TOP</span>
                    <span
                        v-if="lastData?.ultimo_esfuerzo"
                        class="px-1 py-0.2 rounded bg-violet-900/60 text-violet-300 text-[8px] font-black shrink-0"
                    >
                        {{ lastData.ultimo_esfuerzo.tipo.toUpperCase() }} {{ lastData.ultimo_esfuerzo.valor }}
                    </span>
                </div>
                <div class="text-[11px] sm:text-xs font-black text-white tabular-nums text-right truncate">
                    {{ formatPeso(lastData?.peso_max ?? lastData?.peso_top ?? 0) }} kg
                    <span class="text-[9px] text-gray-400 font-normal">({{ (lastData?.reps_en_peso_max ?? lastData?.reps_en_peso_top) ?? 0 }} reps)</span>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    lastData: {
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
