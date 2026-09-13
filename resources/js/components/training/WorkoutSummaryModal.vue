<template>
    <div
        v-if="open"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/85 backdrop-blur-md select-none"
        role="dialog"
        aria-modal="true"
        aria-labelledby="summary-title"
    >
        <div
            class="relative w-full max-w-lg bg-[var(--color-obsidian-surface)] border border-[var(--color-obsidian-border-strong)] rounded-3xl p-6 sm:p-8 shadow-[0_24px_60px_rgba(0,0,0,0.6)] space-y-5 text-white text-center overflow-hidden"
        >
            <!-- Decoración de fondo (kinetic blur violeta/emerald) -->
            <div class="pointer-events-none absolute -top-20 -right-20 w-64 h-64 rounded-full bg-violet-500/15 blur-3xl" aria-hidden="true"></div>
            <div class="pointer-events-none absolute -bottom-20 -left-20 w-64 h-64 rounded-full bg-emerald-500/15 blur-3xl" aria-hidden="true"></div>

            <!-- Badge / Icono -->
            <div
                class="relative mx-auto w-20 h-20 rounded-2xl bg-gradient-to-br from-[var(--color-violet-deep)] via-[var(--color-violet-primary)] to-[var(--color-violet-light)] flex items-center justify-center shadow-[0_12px_32px_var(--color-violet-glow)] text-3xl border border-white/10"
            >
                🏆
            </div>

            <!-- Título -->
            <div class="relative">
                <h2
                    id="summary-title"
                    class="text-2xl sm:text-3xl font-black tracking-tight text-white"
                >
                    ¡Entrenamiento Completado!
                </h2>
                <p class="text-sm text-gray-400 mt-1">
                    {{ store.session.rutina_nombre }} ·
                    <span class="text-violet-300 font-bold">{{ store.session.dia }}</span>
                </p>
            </div>

            <!-- Grilla de Estadísticas (Kinetic Obsidian) -->
            <div class="relative grid grid-cols-2 gap-2.5 text-left">
                <!-- Duración -->
                <div class="bg-[var(--color-obsidian-elevated)] border border-[var(--color-obsidian-border)] rounded-2xl p-4">
                    <span class="text-[10px] font-black uppercase tracking-[0.14em] text-gray-400 flex items-center gap-1">
                        ⏱ Duración
                    </span>
                    <p class="text-xl font-black text-white mt-1.5 tabular-nums">
                        {{ duracionTexto }}
                    </p>
                </div>

                <!-- Volumen / Tonelaje -->
                <div class="bg-[var(--color-obsidian-elevated)] border border-emerald-500/20 rounded-2xl p-4 relative overflow-hidden">
                    <span class="text-[10px] font-black uppercase tracking-[0.14em] text-emerald-300 flex items-center gap-1">
                        🏋️ Tonelaje
                    </span>
                    <p class="text-xl font-black text-emerald-300 mt-1.5 tabular-nums">
                        {{ store.volumenTotal.toLocaleString() }}
                        <span class="text-xs text-gray-400 font-bold">kg</span>
                    </p>
                </div>

                <!-- Series Completadas -->
                <div class="bg-[var(--color-obsidian-elevated)] border border-[var(--color-obsidian-border)] rounded-2xl p-4">
                    <span class="text-[10px] font-black uppercase tracking-[0.14em] text-gray-400 flex items-center gap-1">
                        🔢 Series
                    </span>
                    <p class="text-xl font-black text-white mt-1.5 tabular-nums">
                        {{ store.totalSeriesCompletadas }}
                        <span class="text-xs text-gray-400 font-bold">/ {{ store.totalSeriesObjetivo }}</span>
                    </p>
                </div>

                <!-- Récords Personales -->
                <div class="bg-[var(--color-obsidian-elevated)] border border-amber-500/20 rounded-2xl p-4">
                    <span class="text-[10px] font-black uppercase tracking-[0.14em] text-amber-300 flex items-center gap-1">
                        ⭐ PRs Superados
                    </span>
                    <p class="text-xl font-black text-amber-300 mt-1.5">
                        {{ prsCount > 0 ? `${prsCount} récord${prsCount === 1 ? '' : 's'}` : 'Constancia' }}
                    </p>
                </div>
            </div>

            <!-- Nuevas Medallas -->
            <div
                v-if="newMedals.length > 0"
                class="relative bg-gradient-to-br from-violet-500/15 to-violet-700/10 border border-violet-500/30 rounded-2xl p-3.5 text-left space-y-2"
            >
                <span class="text-xs font-black text-violet-200 uppercase tracking-[0.14em]">
                    🎖️ ¡Nuevas Medallas Ganadas!
                </span>
                <div class="flex flex-wrap gap-2">
                    <span
                        v-for="m in newMedals"
                        :key="m.slug || m.nombre"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-[var(--color-obsidian-elevated)] text-xs font-bold text-violet-200 border border-violet-500/30"
                    >
                        <span class="text-base">{{ m.icono || '🎖️' }}</span>
                        <span>{{ m.nombre }}</span>
                    </span>
                </div>
            </div>

            <!-- Notas de la sesión -->
            <div class="relative text-left space-y-1.5">
                <label class="text-xs font-bold text-gray-300">
                    Notas de la sesión (opcional):
                </label>
                <textarea
                    v-model="notas"
                    rows="2"
                    placeholder="¿Cómo te sentiste hoy? (ej: excelente energía, aumenté peso en banca)"
                    class="w-full bg-[var(--color-obsidian-elevated)] border border-[var(--color-obsidian-border)] rounded-xl p-3 text-xs text-white placeholder:text-gray-500 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent"
                ></textarea>
            </div>

            <!-- Botones de Acción -->
            <div class="relative space-y-2 pt-1">
                <button
                    type="button"
                    @click="guardarYSalir"
                    :disabled="guardando"
                    class="w-full py-4 rounded-2xl bg-gradient-to-br from-[var(--color-violet-deep)] via-[var(--color-violet-primary)] to-[var(--color-violet-light)] hover:brightness-110 text-white font-black text-base shadow-[0_12px_32px_var(--color-violet-glow)] flex items-center justify-center gap-2 cursor-pointer transition-all active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed border border-white/10"
                >
                    <span v-if="!guardando">Guardar y Finalizar Sesión</span>
                    <span v-else class="animate-pulse">Guardando sesión...</span>
                </button>

                <button
                    type="button"
                    @click="$emit('cancel')"
                    class="w-full py-2.5 rounded-xl text-xs font-bold text-gray-400 hover:text-white transition-colors cursor-pointer"
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
