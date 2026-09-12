<template>
    <transition name="slide-fade">
        <div
            v-if="isActive"
            class="fixed bottom-20 md:bottom-6 right-4 sm:right-6 z-50 w-[calc(100%-2rem)] sm:w-88 bg-slate-900 text-white rounded-2xl shadow-2xl border border-slate-800 p-4 transform transition-all duration-300"
            role="dialog"
            aria-label="Temporizador de descanso"
        >
            <div class="flex items-center justify-between mb-3">
                <div class="min-w-0 pr-2">
                    <p class="text-[10px] uppercase tracking-[0.2em] text-indigo-400 font-semibold">
                        Descanso Activo
                    </p>
                    <h4 class="text-xs font-bold truncate text-slate-100">{{ currentExercise }}</h4>
                </div>
                <div class="flex items-center gap-1.5 flex-shrink-0">
                    <!-- Toggle Sonido -->
                    <button
                        type="button"
                        @click="handleToggleSound"
                        :class="[
                            'p-1.5 rounded-lg text-xs transition-colors',
                            isSoundOn
                                ? 'text-indigo-300 hover:text-white bg-slate-800/80 hover:bg-slate-700'
                                : 'text-slate-500 hover:text-slate-300 bg-slate-800/40',
                        ]"
                        :title="isSoundOn ? 'Silenciar sonido' : 'Activar sonido'"
                        :aria-label="isSoundOn ? 'Silenciar sonido' : 'Activar sonido'"
                    >
                        <span v-if="isSoundOn">🔔</span>
                        <span v-else>🔕</span>
                    </button>

                    <!-- Cerrar / Cancelar -->
                    <button
                        type="button"
                        @click="handleSkip"
                        class="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition-colors"
                        title="Cerrar temporizador"
                        aria-label="Cerrar temporizador"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>
            </div>

            <div class="flex items-center justify-between gap-3">
                <!-- Círculo de tiempo -->
                <div class="flex items-center gap-2">
                    <div class="relative flex items-center justify-center w-14 h-14 flex-shrink-0">
                        <svg class="absolute w-full h-full transform -rotate-90">
                            <circle
                                cx="28"
                                cy="28"
                                r="24"
                                stroke="#1e293b"
                                stroke-width="3.5"
                                fill="transparent"
                            />
                            <circle
                                cx="28"
                                cy="28"
                                r="24"
                                stroke="#6366f1"
                                stroke-width="3.5"
                                fill="transparent"
                                :stroke-dasharray="150.796"
                                :stroke-dashoffset="dashOffset"
                                stroke-linecap="round"
                                class="transition-all duration-300"
                            />
                        </svg>
                        <span class="text-xs font-mono font-bold">{{
                            formattedRemainingTime
                        }}</span>
                    </div>
                </div>

                <!-- Botones de control -->
                <div class="flex items-center gap-1.5 flex-1 justify-end flex-wrap">
                    <!-- Botón Restar 15s -->
                    <button
                        type="button"
                        @click="handleSubtract15s"
                        class="px-2 py-1.5 text-xs font-semibold rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white transition-colors"
                        title="Restar 15 segundos"
                        aria-label="Restar 15 segundos"
                    >
                        <span>-15s</span>
                    </button>

                    <!-- Botón Sumar 30s -->
                    <button
                        type="button"
                        @click="handleAdd30s"
                        class="px-2 py-1.5 text-xs font-semibold rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white transition-colors"
                        title="Añadir 30 segundos"
                        aria-label="Añadir 30 segundos"
                    >
                        <span>+30s</span>
                    </button>

                    <!-- Play / Pausa -->
                    <button
                        type="button"
                        @click="handlePauseResume"
                        class="p-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-white transition-colors"
                        :title="isPaused ? 'Reanudar' : 'Pausar'"
                        :aria-label="isPaused ? 'Reanudar temporizador' : 'Pausar temporizador'"
                    >
                        <svg
                            v-if="isPaused"
                            class="w-4 h-4 text-emerald-400"
                            fill="currentColor"
                            viewBox="0 0 20 20"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z"
                                clip-rule="evenodd"
                            />
                        </svg>
                        <svg v-else class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                fill-rule="evenodd"
                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM7 8a1 1 0 012 0v4a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v4a1 1 0 102 0V8a1 1 0 00-1-1z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </button>

                    <!-- Saltar -->
                    <button
                        type="button"
                        @click="handleSkip"
                        class="px-2.5 py-1.5 text-xs font-semibold rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white transition-colors"
                        title="Saltar descanso"
                        aria-label="Saltar descanso"
                    >
                        <span>Saltar</span>
                    </button>
                </div>
            </div>
        </div>
    </transition>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { useRestTimerStore } from '../../stores/restTimer';

const props = defineProps({
    modelValue: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['pausar-reanudar', 'agregar-30s', 'restar-15s', 'saltar']);

let store = null;
try {
    store = useRestTimerStore();
} catch {
    // Si no hay Pinia disponible en el contexto actual
}

onMounted(() => {
    if (store && !props.modelValue) {
        store.init();
    }
});

const isActive = computed(() => {
    if (props.modelValue) return !!props.modelValue.activo;
    return !!store?.active;
});

const isPaused = computed(() => {
    if (props.modelValue) return !!props.modelValue.pausado;
    return !!store?.paused;
});

const currentExercise = computed(() => {
    if (props.modelValue) return props.modelValue.ejercicioNombre || 'Descanso';
    return store?.exerciseName || 'Descanso';
});

const isSoundOn = computed(() => {
    return store?.soundEnabled ?? true;
});

const formattedRemainingTime = computed(() => {
    if (props.modelValue) {
        const mins = Math.floor((props.modelValue.segundosRestantes || 0) / 60);
        const secs = (props.modelValue.segundosRestantes || 0) % 60;
        return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    }
    return store?.formattedTime ?? '00:00';
});

const dashOffset = computed(() => {
    const circumference = 150.796;
    if (props.modelValue) {
        if (!props.modelValue.totalSegundos) return 0;
        const ratio = props.modelValue.segundosRestantes / props.modelValue.totalSegundos;
        return circumference - ratio * circumference;
    }
    const ratio = store?.progressRatio ?? 0;
    return circumference - ratio * circumference;
});

const handlePauseResume = () => {
    if (props.modelValue) {
        emit('pausar-reanudar');
    } else {
        store?.togglePause();
    }
};

const handleAdd30s = () => {
    if (props.modelValue) {
        emit('agregar-30s');
    } else {
        store?.addSeconds(30);
    }
};

const handleSubtract15s = () => {
    if (props.modelValue) {
        emit('restar-15s');
    } else {
        store?.subtractSeconds(15);
    }
};

const handleSkip = () => {
    if (props.modelValue) {
        emit('saltar');
    } else {
        store?.skip();
    }
};

const handleToggleSound = () => {
    store?.toggleSound();
};
</script>

<style scoped>
.slide-fade-enter-active {
    transition: all 0.3s ease-out;
}
.slide-fade-leave-active {
    transition: all 0.3s cubic-bezier(1, 0.5, 0.8, 1);
}
.slide-fade-enter-from,
.slide-fade-leave-to {
    transform: translateY(20px) scale(0.95);
    opacity: 0;
}
</style>
