<script setup>
import { computed } from 'vue';
import { useTrainingSessionStore } from '@/stores/trainingSession';

defineEmits(['continue', 'discard']);

const session = useTrainingSessionStore();

const formattedActiveTime = computed(() => {
    const s = session.elapsed;
    const hrs = Math.floor(s / 3600);
    const mins = Math.floor((s % 3600) / 60);
    const secs = s % 60;
    if (hrs > 0) {
        return `${String(hrs).padStart(2, '0')}:${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
    }
    return `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
});
</script>

<template>
    <div
        v-if="session.isActive"
        class="relative mb-space-lg overflow-hidden rounded-xl border border-tertiary/40 bg-surface-container-low p-space-md text-on-surface shadow-xl flex flex-wrap items-center justify-between gap-3 animate-fadeIn"
    >
        <div class="flex items-center gap-space-md">
            <span class="relative flex h-3.5 w-3.5">
                <span
                    class="animate-ping absolute inline-flex h-full w-full rounded-full bg-tertiary opacity-75"
                ></span>
                <span
                    class="relative inline-flex rounded-full h-3.5 w-3.5 bg-tertiary"
                ></span>
            </span>
            <div>
                <p
                    class="font-label-lg text-label-lg text-on-surface flex items-center gap-2"
                >
                    <span
                        >Entrenamiento en curso ·
                        {{ formattedActiveTime }}</span
                    >
                    <span
                        v-if="session.isPaused"
                        class="font-label-sm text-label-sm bg-secondary-container/60 text-secondary px-2 py-0.5 rounded-full"
                    >
                        Pausado
                    </span>
                </p>
                <p class="font-body-sm text-body-sm text-on-surface-variant">
                    {{
                        session.currentEjercicio?.nombre || 'Sesión iniciada'
                    }}
                    · {{ session.totalSeriesCompletadas }}/{{
                        session.totalSeriesObjetivo
                    }}
                    series
                </p>
            </div>
        </div>
        <div class="flex items-center gap-space-sm">
            <button
                type="button"
                @click="emit('continue')"
                class="px-space-md py-space-sm rounded-lg bg-tertiary text-on-tertiary font-label-md text-label-md font-bold shadow-md active:scale-95 transition-all flex items-center gap-1"
            >
                <span class="material-symbols-outlined text-[16px]">bolt</span>
                <span>Continuar</span>
            </button>
            <button
                type="button"
                @click="emit('discard')"
                title="Descartar entrenamiento en curso"
                aria-label="Descartar entrenamiento en curso"
                class="px-space-sm py-space-sm rounded-lg bg-surface-container-high hover:bg-error-container text-on-surface-variant hover:text-error font-label-md text-label-md transition-all"
            >
                ✕
            </button>
        </div>
    </div>
</template>
