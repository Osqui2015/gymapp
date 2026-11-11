<template>
    <div
        v-if="open"
        class="fixed inset-0 z-[75] flex items-center justify-center p-4 bg-black/85 backdrop-blur-md select-none"
        role="dialog"
        aria-modal="true"
        aria-labelledby="summary-title"
    >
        <div
            class="relative w-full max-w-lg bg-surface border border-border rounded-3xl p-6 sm:p-8 shadow-[0_24px_60px_rgba(0,0,0,0.6)] space-y-5 text-text text-center overflow-hidden"
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
                    class="text-2xl sm:text-3xl font-black tracking-tight text-text"
                >
                    ¡Entrenamiento Completado!
                </h2>
                <p class="text-sm text-text-3 mt-1">
                    {{ store.session.rutina_nombre }} ·
                    <span class="text-brand font-bold">{{ store.session.dia }}</span>
                </p>
            </div>

            <!-- Grilla de Estadísticas (Kinetic Obsidian) -->
            <div class="relative grid grid-cols-2 gap-2.5 text-left">
                <!-- Duración -->
                <div class="bg-surface-2 border border-border rounded-2xl p-4">
                    <span class="text-[10px] font-black uppercase tracking-[0.14em] text-text-3 flex items-center gap-1">
                        ⏱ Duración
                    </span>
                    <p class="text-xl font-black text-text mt-1.5 tabular-nums">
                        {{ duracionTexto }}
                    </p>
                </div>

                <!-- Volumen / Tonelaje -->
                <div class="bg-surface-2 border border-success/30 rounded-2xl p-4 relative overflow-hidden">
                    <span class="text-[10px] font-black uppercase tracking-[0.14em] text-success flex items-center gap-1">
                        🏋️ Tonelaje
                    </span>
                    <p class="text-xl font-black text-success mt-1.5 tabular-nums">
                        {{ store.volumenTotal.toLocaleString() }}
                        <span class="text-xs text-text-3 font-bold">kg</span>
                    </p>
                </div>

                <!-- Series Completadas -->
                <div class="bg-surface-2 border border-border rounded-2xl p-4">
                    <span class="text-[10px] font-black uppercase tracking-[0.14em] text-text-3 flex items-center gap-1">
                        🔢 Series
                    </span>
                    <p class="text-xl font-black text-text mt-1.5 tabular-nums">
                        {{ store.totalSeriesCompletadas }}
                        <span class="text-xs text-text-3 font-bold">/ {{ store.totalSeriesObjetivo }}</span>
                    </p>
                </div>

                <!-- Récords Personales -->
                <div class="bg-surface-2 border border-warning/30 rounded-2xl p-4">
                    <span class="text-[10px] font-black uppercase tracking-[0.14em] text-warning flex items-center gap-1">
                        ⭐ PRs Superados
                    </span>
                    <p class="text-xl font-black text-warning mt-1.5">
                        {{ prsCount > 0 ? `${prsCount} récord${prsCount === 1 ? '' : 's'}` : 'Constancia' }}
                    </p>
                </div>
            </div>

            <!-- Nuevas Medallas -->
            <div
                v-if="newMedals.length > 0"
                class="relative bg-gradient-to-br from-brand/15 to-violet-700/10 border border-brand/30 rounded-2xl p-3.5 text-left space-y-2"
            >
                <span class="text-xs font-black text-brand uppercase tracking-[0.14em]">
                    🎖️ ¡Nuevas Medallas Ganadas!
                </span>
                <div class="flex flex-wrap gap-2">
                    <span
                        v-for="m in newMedals"
                        :key="m.slug || m.nombre"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-surface-2 text-xs font-bold text-text-2 border border-brand/30"
                    >
                        <span class="text-base">{{ m.icono || '🎖️' }}</span>
                        <span>{{ m.nombre }}</span>
                    </span>
                </div>
            </div>

            <!-- Notas de la sesión -->
            <div class="relative text-left space-y-1.5">
                <label class="text-xs font-bold text-text-2">
                    Notas de la sesión (opcional):
                </label>
                <textarea
                    v-model="notas"
                    rows="2"
                    placeholder="¿Cómo te sentiste hoy? (ej: excelente energía, aumenté peso en banca)"
                    class="w-full bg-surface-2 border border-border rounded-xl p-3 text-xs text-text placeholder:text-text-3 focus:outline-none focus:ring-2 focus:ring-brand focus:border-transparent"
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
                    class="w-full py-2.5 rounded-xl text-xs font-bold text-text-3 hover:text-text transition-colors cursor-pointer"
                >
                    Volver al entrenamiento
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
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

// Snapshot de la duración al abrir el modal. Después de guardar y finalizar,
// `store.end()` resetea `startedAt = null` y `elapsed` cae a 0; sin este
// snapshot, el modal podría re-renderizar y mostrar "0 min" un instante.
const duracionSnapshot = ref(0);

const duracionTexto = computed(() => {
    // Usamos el snapshot para que no dependa de cambios reactivos del store
    // mientras el modal esta abierto (incluido `store.end()` al guardar).
    const s = duracionSnapshot.value;
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
            // Capturamos la duración en vivo al momento de abrir.
            duracionSnapshot.value = store.elapsed;
            triggerConfetti();
            if (typeof document !== 'undefined') {
                document.body.style.overflow = 'hidden';
            }
        } else {
            if (typeof document !== 'undefined') {
                document.body.style.overflow = '';
            }
        }
    },
    { immediate: true }
);

onMounted(() => {
    if (props.open) {
        duracionSnapshot.value = store.elapsed;
        triggerConfetti();
        if (typeof document !== 'undefined') {
            document.body.style.overflow = 'hidden';
        }
    }
});

onUnmounted(() => {
    if (typeof document !== 'undefined') {
        document.body.style.overflow = '';
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
