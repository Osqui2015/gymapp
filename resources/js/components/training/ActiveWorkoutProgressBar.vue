<!--
    ActiveWorkoutProgressBar.vue

    Barra de progreso global del ActiveWorkoutModal: series completadas sobre
    el objetivo + barra visual + porcentaje.

    Props:
      - completadas: series completadas
      - objetivo: series objetivo
      - porcentaje: porcentaje de progreso (0-100)
-->
<template>
    <div
        class="w-full bg-[var(--color-obsidian-surface)] border-b border-[var(--color-obsidian-border)] px-3 py-1.5 sm:px-4 sm:py-2 shrink-0 flex items-center justify-between gap-3 text-xs"
    >
        <span class="text-gray-300 font-semibold whitespace-nowrap">
            Progreso:
            <strong class="text-emerald-300 tabular-nums">{{ completadas }}</strong>
            /
            <span class="text-gray-400">{{ objetivo }}</span>
            series
        </span>
        <div class="flex-1 h-1.5 sm:h-2 bg-[var(--color-obsidian-elevated)] rounded-full overflow-hidden mx-1 border border-[var(--color-obsidian-border)]">
            <div
                class="h-full bg-gradient-to-r from-[var(--color-violet-deep)] via-[var(--color-violet-primary)] to-emerald-400 transition-all duration-300"
                :style="{ width: `${porcentaje}%` }"
            ></div>
        </div>
        <span class="font-black text-violet-300 tabular-nums whitespace-nowrap">
            {{ porcentaje }}%
        </span>
    </div>
</template>

<script setup>
defineProps({
    completadas: {
        type: Number,
        default: 0,
    },
    objetivo: {
        type: Number,
        default: 0,
    },
    porcentaje: {
        type: Number,
        default: 0,
    },
});
</script>
