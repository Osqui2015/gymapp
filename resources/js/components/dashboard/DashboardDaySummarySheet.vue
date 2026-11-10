<script setup>
defineProps({
    /** Resumen del día (datos precargados por el padre). */
    summary: {
        type: Object,
        default: () => ({}),
    },
    /**
     * Función que formatea el volumen (ej. 12000 → "12k").
     * Se recibe como prop para no duplicar la lógica de formato.
     */
    formatVolumen: {
        type: Function,
        required: true,
    },
});

defineEmits(['close', 'next-day', 'finish-routine']);
</script>

<template>
    <Teleport to="body">
        <Transition name="sheet-fade">
            <div
                v-if="summary"
                class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/60 backdrop-blur-sm"
                @click.self="$emit('close')"
            >
                <div
                    class="sheet-content w-full sm:max-w-md bg-surface-container sm:rounded-3xl rounded-t-3xl rounded-b-none sm:rounded-b-3xl shadow-2xl border border-surface-variant overflow-hidden max-h-[90vh] flex flex-col"
                >
                    <div class="sm:hidden pt-2 pb-1 flex justify-center">
                        <div class="w-10 h-1.5 rounded-full bg-surface-variant"></div>
                    </div>

                    <div
                        class="bg-gradient-to-br from-tertiary-container to-tertiary px-6 py-5 text-on-tertiary"
                    >
                        <div
                            class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider opacity-90"
                        >
                            <span>✓</span>
                            <span>Día completado</span>
                        </div>
                        <h3 class="mt-1 text-2xl font-black">
                            {{ summary.diaLabel }}
                        </h3>
                    </div>

                    <div class="px-6 py-5 space-y-4 overflow-y-auto">
                        <div class="grid grid-cols-3 gap-3 text-center">
                            <div class="rounded-xl bg-primary-container/15 px-3 py-3">
                                <p
                                    class="text-[10px] font-bold uppercase tracking-wider text-primary"
                                >
                                    Series
                                </p>
                                <p
                                    class="mt-0.5 text-2xl font-black text-on-surface tabular-nums"
                                >
                                    {{ summary.completadas }}/{{ summary.total }}
                                </p>
                            </div>
                            <div class="rounded-xl bg-secondary-container/25 px-3 py-3">
                                <p
                                    class="text-[10px] font-bold uppercase tracking-wider text-secondary"
                                >
                                    Volumen
                                </p>
                                <p
                                    class="mt-0.5 text-2xl font-black text-on-surface tabular-nums"
                                >
                                    {{ formatVolumen(summary.volumen) }}
                                </p>
                                <p class="text-[10px] text-outline">kg totales</p>
                            </div>
                            <div class="rounded-xl bg-tertiary/15 px-3 py-3">
                                <p
                                    class="text-[10px] font-bold uppercase tracking-wider text-tertiary"
                                >
                                    PRs
                                </p>
                                <p
                                    class="mt-0.5 text-2xl font-black text-tertiary tabular-nums"
                                >
                                    {{ summary.prs }}
                                </p>
                                <p class="text-[10px] text-tertiary/80">récords</p>
                            </div>
                        </div>

                        <div
                            v-if="summary.mejorSet"
                            class="rounded-xl bg-tertiary/10 border border-tertiary/30 px-4 py-3"
                        >
                            <div
                                class="flex items-center gap-2 text-xs font-bold text-tertiary uppercase tracking-wider"
                            >
                                <span>🏆</span>
                                <span>Mejor set</span>
                            </div>
                            <p class="mt-1 text-base font-bold text-on-surface">
                                {{ summary.mejorSet.ejercicio }}
                            </p>
                            <p class="text-sm font-semibold text-tertiary tabular-nums">
                                {{ summary.mejorSet.peso }} kg ×
                                {{ summary.mejorSet.reps }} reps
                            </p>
                        </div>
                    </div>

                    <div
                        class="px-6 py-4 bg-surface-container-lowest/60 border-t border-surface-variant flex gap-3 pb-[max(env(safe-area-inset-bottom),1rem)]"
                    >
                        <button
                            type="button"
                            @click="$emit('close')"
                            class="flex-1 px-4 py-2.5 rounded-xl border border-surface-variant bg-surface-container-low text-sm font-semibold text-on-surface-variant hover:text-on-surface transition-colors"
                        >
                            Quedarme acá
                        </button>
                        <button
                            v-if="summary.tieneSiguiente"
                            type="button"
                            @click="$emit('next-day')"
                            class="flex-1 px-4 py-2.5 rounded-xl bg-tertiary hover:bg-tertiary-fixed-dim text-on-tertiary text-sm font-bold shadow-md transition-all"
                        >
                            Ir al {{ summary.siguienteLabel }} →
                        </button>
                        <button
                            v-else
                            type="button"
                            @click="$emit('finish-routine')"
                            class="flex-1 px-4 py-2.5 rounded-xl bg-tertiary hover:bg-tertiary-fixed-dim text-on-tertiary text-sm font-bold shadow-md transition-all"
                        >
                            🎉 Finalizar rutina
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.sheet-fade-enter-active,
.sheet-fade-leave-active {
    transition: opacity 0.25s ease;
}
.sheet-fade-enter-from,
.sheet-fade-leave-to {
    opacity: 0;
}

.sheet-fade-enter-active .sheet-content,
.sheet-fade-leave-active .sheet-content {
    transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.sheet-fade-enter-from .sheet-content,
.sheet-fade-leave-to .sheet-content {
    transform: translateY(100%);
}

@media (min-width: 640px) {
    .sheet-fade-enter-from .sheet-content,
    .sheet-fade-leave-to .sheet-content {
        transform: translateY(0) scale(0.96);
    }
}
</style>
