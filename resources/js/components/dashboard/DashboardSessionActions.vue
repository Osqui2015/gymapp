<script setup>
defineProps({
    /** Índice del día actual dentro de la lista de días de la rutina. */
    diaIndex: {
        type: Number,
        default: -1,
    },
    /** Si el día actual es el último de la rutina. */
    esUltimoDia: {
        type: Boolean,
        default: false,
    },
    /** Etiqueta del siguiente día (vacío si no hay). */
    diasSiguienteLabel: {
        type: String,
        default: '',
    },
});

defineEmits(['previous-day', 'save-session', 'next-day', 'finish-routine']);
</script>

<template>
    <section class="flex flex-col gap-space-md pt-space-xs pb-space-xl">
        <div class="flex flex-col sm:flex-row items-center gap-space-md">
            <!-- Dia Anterior Secondary -->
            <button
                v-if="diaIndex > 0"
                type="button"
                @click="$emit('previous-day')"
                class="w-full sm:w-auto px-space-lg py-space-md rounded-xl bg-surface-container-high hover:bg-surface-bright text-on-surface font-label-lg text-label-lg flex items-center justify-center gap-2 transition-colors"
            >
                <span class="material-symbols-outlined text-[20px]"
                    >arrow_back</span
                >
                <span>Día anterior</span>
            </button>
            <!-- Guardar Sesion Primary -->
            <button
                type="button"
                @click="$emit('save-session')"
                class="w-full sm:flex-1 px-space-xl py-space-md rounded-xl bg-brand hover:brightness-110 active:scale-[0.98] text-white font-label-lg text-label-lg font-bold flex items-center justify-center gap-2 shadow-[0_0_20px_rgba(79,70,229,0.35)] transition-all cursor-pointer"
            >
                <span class="material-symbols-outlined text-[20px]"
                    >bookmark</span
                >
                <span>Guardar sesión</span>
            </button>
        </div>

        <!-- Siguiente Día (si no es el último día) -->
        <button
            v-if="!esUltimoDia"
            type="button"
            @click="$emit('next-day')"
            class="w-full py-space-md px-space-xl rounded-xl bg-surface-container-high hover:bg-surface-bright text-on-surface font-label-lg text-label-lg font-semibold flex items-center justify-center gap-2 transition-all"
        >
            <span>Avanzar al siguiente día: {{ diasSiguienteLabel }}</span>
            <span class="material-symbols-outlined text-[20px]"
                >arrow_forward</span
            >
        </button>

        <!-- Finalizar Rutina Full Accent CTA -->
        <button
            type="button"
            @click="$emit('finish-routine')"
            class="w-full py-space-md px-space-xl rounded-xl bg-tertiary hover:bg-tertiary-fixed-dim text-on-tertiary font-label-lg text-label-lg font-bold flex items-center justify-center gap-2.5 shadow-[0_0_24px_rgba(78,222,163,0.35)] hover:shadow-[0_0_32px_rgba(78,222,163,0.5)] transition-all"
        >
            <span
                class="material-symbols-outlined text-[22px]"
                :style="{ fontVariationSettings: '\'FILL\' 1' }"
                >check_circle</span
            >
            <span>Finalizar rutina</span>
        </button>
    </section>
</template>
