<template>
    <div class="space-y-8 animate-fadeIn">
        <!-- Stats -->
        <div v-if="logrosStats" class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div
                v-for="stat in statsCards"
                :key="stat.label"
                class="rounded-2xl border border-obsidian-border bg-obsidian-card p-4 shadow-card-border text-center"
            >
                <span class="text-2xl block mb-1">{{ stat.emoji }}</span>
                <p class="text-xs text-slate-400">{{ stat.label }}</p>
                <p class="mt-1 text-2xl font-black font-mono font-display" :class="stat.colorClass">
                    {{ stat.value }}
                </p>
            </div>
        </div>

        <!-- Badges -->
        <div class="space-y-4">
            <h3 class="text-base font-bold font-display text-white flex items-center gap-2">
                <span>🏆</span> Vitrina de Medallas
            </h3>
            <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-6">
                <article
                    v-for="logro in logros"
                    :key="logro.slug"
                    class="bg-obsidian-card rounded-2xl p-5 border border-obsidian-border shadow-card-border flex flex-col items-center text-center transition-all hover:scale-105 relative"
                    :class="{
                        'opacity-50 grayscale': !logro.desbloqueada,
                        'ring-1 ring-accent-indigo/50 bg-gradient-to-br from-obsidian-card to-indigo-950/25':
                            logro.desbloqueada,
                        'animate-bounce-in ring-2 ring-accent-amber/80': newlyUnlockedSlugs.has(
                            logro.slug
                        ),
                    }"
                >
                    <!-- Badge "¡NUEVA!" -->
                    <div
                        v-if="newlyUnlockedSlugs.has(logro.slug)"
                        class="absolute -top-2 -right-2 bg-gradient-to-br from-accent-amber to-orange-500 text-white text-[10px] font-extrabold px-2 py-1 rounded-full shadow-lg animate-pulse"
                    >
                        ¡NUEVA!
                    </div>

                    <div
                        class="w-16 h-16 rounded-2xl flex items-center justify-center text-3xl mb-4 relative shadow-inner border"
                        :class="
                            logro.desbloqueada
                                ? 'bg-indigo-500/15 border-indigo-500/30 text-white'
                                : 'bg-obsidian-surface border-obsidian-border text-slate-500'
                        "
                    >
                        <span>{{ logro.icono }}</span>
                        <div
                            v-if="!logro.desbloqueada"
                            class="absolute -bottom-1 -right-1 bg-obsidian-card border border-obsidian-border text-slate-400 rounded-full p-1 text-[10px] w-5 h-5 flex items-center justify-center"
                        >
                            🔒
                        </div>
                    </div>

                    <h4 class="font-extrabold text-sm text-white mb-1 font-display">
                        {{ logro.nombre }}
                    </h4>
                    <p
                        class="text-xs text-slate-400 mb-4 h-10 flex items-center justify-center text-center"
                    >
                        {{ logro.descripcion }}
                    </p>

                    <div class="w-full mt-auto">
                        <div
                            v-if="logro.desbloqueada"
                            class="text-[10px] font-semibold text-accent-emerald"
                        >
                            Desbloqueada el {{ formatFechaMedalla(logro.ganado_at) }}
                        </div>
                        <div v-else class="space-y-1">
                            <ProgressBar
                                :value="(logro.progreso / logro.objetivo) * 100"
                                color="indigo"
                                size="sm"
                            />
                            <div class="text-[9px] font-mono text-slate-400">
                                {{ logro.progreso }} / {{ logro.objetivo }}
                            </div>
                        </div>
                    </div>
                </article>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, ref, watch, onMounted } from 'vue';
import ProgressBar from '../ProgressBar.vue';
import { useConfetti } from '../../composables/useConfetti';

const props = defineProps({
    logros: { type: Array, required: true },
    logrosStats: { type: Object, default: null },
    formatFechaMedalla: { type: Function, required: true },
});

const { bigCelebration, mini } = useConfetti();

// Detecta medallas nuevas comparando con localStorage
const STORAGE_KEY = 'gymapp_seen_medals';
const newlyUnlockedSlugs = ref(new Set());

const detectNewMedals = () => {
    try {
        const stored = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]');
        const storedSet = new Set(stored);

        const newOnes = props.logros.filter((l) => l.desbloqueada && !storedSet.has(l.slug));
        if (newOnes.length > 0) {
            newlyUnlockedSlugs.value = new Set(newOnes.map((l) => l.slug));
            try {
                if (newOnes.length === 1) {
                    bigCelebration();
                } else {
                    mini();
                }
            } catch {
                /* ignore */
            }
        }

        const currentSlugs = props.logros.filter((l) => l.desbloqueada).map((l) => l.slug);
        localStorage.setItem(STORAGE_KEY, JSON.stringify(currentSlugs));
    } catch (e) {
        // Ignorar error de storage
    }
};

onMounted(detectNewMedals);
watch(() => props.logros.map((l) => `${l.slug}:${l.desbloqueada}`).join(','), detectNewMedals);

const statsCards = computed(() =>
    props.logrosStats
        ? [
              {
                  emoji: '🏋️‍♂️',
                  label: 'Series Completadas',
                  value: props.logrosStats.total_series,
                  colorClass: 'text-accent-indigo',
              },
              {
                  emoji: '🔥',
                  label: 'Racha Actual',
                  value: `${props.logrosStats.streak} días`,
                  colorClass: 'text-accent-amber',
              },
              {
                  emoji: '📅',
                  label: 'Días Entrenados',
                  value: props.logrosStats.unique_days,
                  colorClass: 'text-accent-emerald',
              },
              {
                  emoji: '🎯',
                  label: 'Objetivos Alcanzados',
                  value: props.logrosStats.completed_goals_count,
                  colorClass: 'text-accent-violet',
              },
          ]
        : []
);
</script>
