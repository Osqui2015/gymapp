<!--
    SuperserieHistorialCard.vue

    Card de "última vez" (mínimo / máximo) de UNO de los dos ejercicios de una
    superserie. Se usa dos veces dentro de la grilla de historial del Hero card.

    Props:
      - posicion: 1 | 2 (número que se muestra delante del nombre)
      - nombre: nombre del ejercicio
      - lastData: objeto con el histórico (peso_min, peso_max, peso_top,
        reps_en_peso_min/max/top, ultimo_esfuerzo)
-->
<template>
    <div
        class="p-2 rounded-xl bg-[var(--color-obsidian-surface)] border border-violet-500/30 flex flex-col justify-between space-y-1.5"
        :data-testid="`superset-history-card-${posicion}`"
    >
        <div class="flex items-center justify-between gap-1 border-b border-violet-500/20 pb-1">
            <span class="text-[10px] sm:text-[11px] font-black text-violet-200 uppercase truncate" :title="nombre">
                {{ posicion }}. {{ nombre }}
            </span>
            <span
                v-if="lastData?.ultimo_esfuerzo"
                class="px-1.5 py-0.2 rounded-full bg-violet-900/60 border border-violet-500/40 text-violet-300 text-[8px] font-black shrink-0"
            >
                {{ lastData.ultimo_esfuerzo.tipo.toUpperCase() }} {{ lastData.ultimo_esfuerzo.valor }}
            </span>
        </div>
        <div class="grid grid-cols-2 gap-1.5">
            <div class="p-1 rounded-lg bg-[var(--color-obsidian-elevated)] border border-cyan-500/20 text-left">
                <div class="text-[8px] font-black text-teal-400 uppercase">MÍNIMO</div>
                <div class="text-xs font-black text-white tabular-nums">
                    {{ formatPeso(lastData?.peso_min ?? lastData?.peso_top ?? 0) }} kg
                </div>
                <div class="text-[9px] text-gray-400 tabular-nums">
                    x {{ (lastData?.reps_en_peso_min ?? lastData?.reps_en_peso_top) ?? 0 }} reps
                </div>
            </div>
            <div class="p-1 rounded-lg bg-[var(--color-obsidian-elevated)] border border-fuchsia-500/20 text-left">
                <div class="text-[8px] font-black text-fuchsia-400 uppercase">MÁXIMO</div>
                <div class="text-xs font-black text-white tabular-nums">
                    {{ formatPeso(lastData?.peso_max ?? lastData?.peso_top ?? 0) }} kg
                </div>
                <div class="text-[9px] text-gray-400 tabular-nums">
                    x {{ (lastData?.reps_en_peso_max ?? lastData?.reps_en_peso_top) ?? 0 }} reps
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    posicion: {
        type: Number,
        required: true,
    },
    nombre: {
        type: String,
        required: true,
    },
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
