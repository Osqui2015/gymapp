<script setup>
defineProps({
    /** Lista de días disponibles de la rutina. */
    dias: {
        type: Array,
        default: () => [],
    },
    /** Día actualmente seleccionado. */
    diaActual: {
        type: String,
        default: '',
    },
    /** Etiqueta de la semana del ciclo (ej. "Semana 3"). */
    semanaCicloLabel: {
        type: String,
        default: '',
    },
    /**
     * Función que devuelve la etiqueta de cada pestaña de día.
     * Se recibe como prop para no duplicar la lógica de grupo muscular.
     */
    formatDiaTabLabel: {
        type: Function,
        required: true,
    },
});

const emit = defineEmits(['select-day']);
</script>

<template>
    <div
        class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-space-md mb-space-lg"
        data-tour="day-selector"
    >
        <div
            class="flex flex-wrap items-center gap-space-xs p-1 rounded-xl bg-surface-container-low"
        >
            <button
                v-for="dia in dias"
                :key="dia"
                type="button"
                @click="emit('select-day', dia)"
                :class="[
                    'px-space-md py-space-xs rounded-lg font-label-md text-label-md transition-all',
                    diaActual === dia
                        ? 'bg-secondary-container text-on-secondary-container font-semibold shadow-sm'
                        : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container',
                ]"
            >
                {{ formatDiaTabLabel(dia) }}
            </button>
        </div>
        <div
            class="flex items-center gap-space-xs text-outline font-label-sm text-label-sm"
        >
            <span class="material-symbols-outlined text-[16px] text-outline"
                >tune</span
            >
            <span
                >Ciclo de hipertrofia muscular •
                {{ semanaCicloLabel }}</span
            >
        </div>
    </div>
</template>
