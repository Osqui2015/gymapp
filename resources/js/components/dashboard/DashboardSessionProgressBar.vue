<script setup>
defineProps({
    /** Series completadas en la sesión. */
    seriesCompletadas: {
        type: Number,
        default: 0,
    },
    /** Series totales de la sesión. */
    seriesTotales: {
        type: Number,
        default: 0,
    },
    /** Porcentaje de avance de la sesión (0-100). */
    progresoDia: {
        type: Number,
        default: 0,
    },
});
</script>

<template>
    <div
        class="flex flex-col gap-space-xs pt-space-md bg-surface-container/50 -mx-space-lg md:-mx-space-xl -mb-space-lg md:-mb-space-xl px-space-lg md:px-space-xl pb-space-lg"
    >
        <div class="flex items-center justify-between text-on-surface">
            <span
                class="font-label-md text-label-md text-on-surface-variant flex items-center gap-2"
            >
                <span
                    class="material-symbols-outlined text-tertiary text-[16px]"
                    >verified</span
                >
                Progreso de la sesión
            </span>
            <span class="font-label-lg text-label-lg font-bold text-tertiary">
                {{ seriesCompletadas }} / {{ seriesTotales }} series ({{
                    progresoDia
                }}%)
            </span>
        </div>
        <div
            class="w-full h-2.5 rounded-full bg-surface-container-highest overflow-hidden"
        >
            <div
                class="h-full rounded-full bg-gradient-to-r from-secondary via-primary-container to-tertiary transition-all duration-700"
                :style="{ width: progresoDia + '%' }"
            ></div>
        </div>
    </div>
</template>
