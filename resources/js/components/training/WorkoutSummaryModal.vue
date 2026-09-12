<template>
    <div
        v-if="open"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md select-none"
        role="dialog"
        aria-modal="true"
        aria-labelledby="summary-title"
    >
        <div
            class="relative w-full max-w-lg bg-gray-900 border border-gray-800 rounded-3xl p-6 sm:p-8 shadow-2xl space-y-6 text-white text-center"
        >
            <!-- Badge / Icono -->
            <div
                class="mx-auto w-16 h-16 rounded-2xl bg-gradient-to-tr from-emerald-500 to-teal-400 flex items-center justify-center shadow-lg shadow-emerald-500/30 text-3xl"
            >
                🏆
            </div>

            <!-- Título -->
            <div>
                <h2
                    id="summary-title"
                    class="text-2xl sm:text-3xl font-black tracking-tight text-white"
                >
                    ¡Entrenamiento Completado!
                </h2>
                <p class="text-sm text-gray-400 mt-1">
                    {{ store.session.rutina_nombre }} · {{ store.session.dia }}
                </p>
            </div>

            <!-- Grilla de Estadísticas -->
            <div class="grid grid-cols-2 gap-3 text-left">
                <!-- Duración -->
                <div class="bg-gray-950/70 border border-gray-800/80 rounded-2xl p-4">
                    <span class="text-xs font-semibold text-gray-400 flex items-center gap-1">
                        ⏱ Duración
                    </span>
                    <p class="text-xl font-bold text-white mt-1">
                        {{ duracionTexto }}
                    </p>
                </div>

                <!-- Volumen / Tonelaje -->
                <div class="bg-gray-950/70 border border-gray-800/80 rounded-2xl p-4">
                    <span class="text-xs font-semibold text-gray-400 flex items-center gap-1">
                        🏋️‍♂️ Tonelaje Total
                    </span>
                    <p class="text-xl font-bold text-emerald-400 mt-1">
                        {{ store.volumenTotal.toLocaleString() }}
                        <span class="text-xs text-gray-400 font-normal">kg</span>
                    </p>
                </div>

                <!-- Series Completadas -->
                <div class="bg-gray-950/70 border border-gray-800/80 rounded-2xl p-4">
                    <span class="text-xs font-semibold text-gray-400 flex items-center gap-1">
                        🔢 Series
                    </span>
                    <p class="text-xl font-bold text-white mt-1">
                        {{ store.totalSeriesCompletadas }}
                        <span class="text-xs text-gray-400 font-normal"
                            >/ {{ store.totalSeriesObjetivo }}</span
                        >
                    </p>
                </div>

                <!-- Récords Personales (si hubo o placeholder) -->
                <div class="bg-gray-950/70 border border-gray-800/80 rounded-2xl p-4">
                    <span class="text-xs font-semibold text-gray-400 flex items-center gap-1">
                        ⭐ PRs Superados
                    </span>
                    <p class="text-xl font-bold text-amber-400 mt-1">
                        {{ prsCount > 0 ? `${prsCount} récord(s)` : 'Constancia' }}
                    </p>
                </div>
            </div>

            <!-- Nuevas Medallas (si desbloqueó alguna) -->
            <div
                v-if="newMedals.length > 0"
                class="bg-indigo-950/40 border border-indigo-800/60 rounded-2xl p-3 text-left space-y-2"
            >
                <span class="text-xs font-bold text-indigo-300 uppercase tracking-wider">
                    🎖️ ¡Nuevas Medallas Ganadas!
                </span>
                <div class="flex flex-wrap gap-2">
                    <span
                        v-for="m in newMedals"
                        :key="m.slug || m.nombre"
                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-indigo-900/60 text-xs font-bold text-indigo-200 border border-indigo-700/50"
                    >
                        <span>{{ m.icono || '🎖️' }}</span>
                        <span>{{ m.nombre }}</span>
                    </span>
                </div>
            </div>

            <!-- Notas de la sesión -->
            <div class="text-left space-y-1.5">
                <label class="text-xs font-semibold text-gray-400">
                    Notas de la sesión (opcional):
                </label>
                <textarea
                    v-model="notas"
                    rows="2"
                    placeholder="¿Cómo te sentiste hoy? (ej: excelente energía, aumenté peso en banca)"
                    class="w-full bg-gray-950 border border-gray-800 rounded-xl p-3 text-xs text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                ></textarea>
            </div>

            <!-- Botones de Acción -->
            <div class="space-y-2 pt-2">
                <button
                    type="button"
                    @click="guardarYSalir"
                    :disabled="guardando"
                    class="w-full py-4 rounded-2xl bg-gradient-to-r from-emerald-500 via-teal-500 to-cyan-500 hover:from-emerald-400 hover:to-teal-400 text-white font-bold text-base shadow-xl shadow-emerald-950/50 flex items-center justify-center gap-2 cursor-pointer transition-all active:scale-[0.98] disabled:opacity-50"
                >
                    <span v-if="!guardando">Guardar y Finalizar Sesión</span>
                    <span v-else class="animate-pulse">Guardando sesión...</span>
                </button>

                <button
                    type="button"
                    @click="$emit('cancel')"
                    class="w-full py-2.5 rounded-xl text-xs font-semibold text-gray-400 hover:text-white transition-colors cursor-pointer"
                >
                    Volver al entrenamiento
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useTrainingSessionStore } from '@/stores/trainingSession';
import axios from 'axios';
import confetti from 'canvas-confetti';

const props = defineProps({
    open: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['saved', 'cancel']);

const store = useTrainingSessionStore();
const notas = ref('');
const guardando = ref(false);
const prsCount = ref(0);
const newMedals = ref([]);

const duracionTexto = computed(() => {
    const s = store.elapsed;
    const mins = Math.floor(s / 60);
    const hrs = Math.floor(mins / 60);
    if (hrs > 0) {
        return `${hrs} h ${mins % 60} min`;
    }
    return `${mins} min`;
});

const triggerConfetti = () => {
    try {
        confetti({
            particleCount: 80,
            spread: 70,
            origin: { y: 0.6 },
        });
    } catch {
        // Ignorar si confetti falla
    }
};

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            triggerConfetti();
        }
    }
);

onMounted(() => {
    if (props.open) {
        triggerConfetti();
    }
});

const guardarYSalir = async () => {
    guardando.value = true;
    try {
        const response = await axios.post('/api/sesiones/finalizar', {
            uuid: store.session.id,
            ended_at: new Date().toISOString(),
            notas: notas.value,
        });

        if (response.data && response.data.resumen) {
            prsCount.value = response.data.resumen.prs_superados || 0;
            newMedals.value = response.data.resumen.new_medals || [];
        }

        // Cierra la sesión en Pinia y limpia localStorage
        store.end();
        emit('saved', response.data?.resumen);
    } catch (e) {
        console.error('Error finalizando sesión en backend:', e);
        // Aun con error de red o timeout, cerramos localmente para no bloquear
        store.end();
        emit('saved', null);
    } finally {
        guardando.value = false;
    }
};
</script>
