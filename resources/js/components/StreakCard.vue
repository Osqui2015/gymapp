<!--
  StreakCard — Card de racha tipo "GitHub contribution streak".
  Muestra la racha actual de días consecutivos con al menos 1 set completado.
  Permite ocultar/minimizar la gamificación si el usuario prefiere una UI limpia.

  Look Kinetic Obsidian: fondo oscuro con acento naranja, esquinas 2xl,
  jerarquía tipográfica fuerte. Mantiene data-testids para no romper tests.

  Props:
    - data: { current_streak, longest_streak, this_week, this_month, total_workouts, total_sets }
-->
<template>
    <!-- Estado minimizado / gamificación desactivada -->
    <div
        v-if="hidden"
        data-testid="streak-card-minimized"
        class="obs-card p-4 flex items-center justify-between transition-all"
    >
        <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
            <span>🏆</span>
            <span class="font-medium">Gamificación y racha ocultas</span>
        </div>
        <button
            type="button"
            data-testid="streak-show-button"
            @click="toggleGamification"
            class="text-xs font-bold text-violet-600 hover:text-violet-800 underline dark:text-violet-400 dark:hover:text-violet-300"
        >
            Mostrar
        </button>
    </div>

    <!-- Estado expandido / gamificación activa -->
    <div
        v-else
        data-testid="streak-card-expanded"
        class="relative overflow-hidden rounded-2xl border border-orange-300/40 bg-gradient-to-br from-orange-500/15 via-orange-400/10 to-rose-500/15 p-5 transition-all shadow-sm dark:border-orange-500/30 dark:shadow-[0_8px_28px_rgba(249,115,22,0.15)]"
    >
        <!-- Decoración de fondo (kinetic blur naranja) -->
        <div
            class="pointer-events-none absolute -right-12 -top-12 w-40 h-40 rounded-full bg-orange-500/20 blur-3xl"
            aria-hidden="true"
        ></div>

        <div class="relative flex items-start justify-between gap-3">
            <div>
                <p
                    class="text-[10px] font-bold uppercase tracking-[0.18em] text-orange-700 dark:text-orange-300"
                >
                    Racha actual
                </p>
                <div class="flex items-baseline gap-1.5 mt-1.5">
                    <span class="text-4xl md:text-5xl font-black text-orange-600 dark:text-orange-400 tabular-nums">
                        {{ data?.current_streak ?? 0 }}
                    </span>
                    <span class="text-sm font-bold text-orange-700 dark:text-orange-300">
                        {{ data?.current_streak === 1 ? 'día' : 'días' }}
                    </span>
                </div>
                <p class="text-xs text-orange-700/70 dark:text-orange-300/70 mt-1">
                    consecutivos con al menos 1 set
                </p>
            </div>
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    data-testid="streak-hide-button"
                    @click="toggleGamification"
                    class="opacity-60 hover:opacity-100 transition-opacity p-1.5 text-orange-700 dark:text-orange-300 rounded-lg hover:bg-orange-100 dark:hover:bg-orange-500/20 text-xs"
                    title="Ocultar racha y gamificación"
                    aria-label="Ocultar racha y gamificación"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"
                        />
                    </svg>
                </button>
                <div class="text-4xl flex-shrink-0 select-none">🔥</div>
            </div>
        </div>

        <div class="relative mt-4 grid grid-cols-3 gap-1.5 text-center sm:gap-2">
            <div class="min-w-0 rounded-lg bg-white/70 p-2 dark:bg-[var(--color-obsidian-elevated)]/60 dark:backdrop-blur-sm">
                <p class="text-lg font-bold text-gray-900 dark:text-white tabular-nums">
                    {{ data?.this_week ?? 0 }}
                </p>
                <p
                    class="text-[9px] uppercase leading-tight tracking-wider text-gray-500 dark:text-gray-400 sm:text-[10px]"
                >
                    Esta semana
                </p>
            </div>
            <div class="min-w-0 rounded-lg bg-white/70 p-2 dark:bg-[var(--color-obsidian-elevated)]/60 dark:backdrop-blur-sm">
                <p class="text-lg font-bold text-gray-900 dark:text-white tabular-nums">
                    {{ data?.this_month ?? 0 }}
                </p>
                <p
                    class="text-[9px] uppercase leading-tight tracking-wider text-gray-500 dark:text-gray-400 sm:text-[10px]"
                >
                    Este mes
                </p>
            </div>
            <div class="min-w-0 rounded-lg bg-white/70 p-2 dark:bg-[var(--color-obsidian-elevated)]/60 dark:backdrop-blur-sm">
                <p class="text-lg font-bold text-gray-900 dark:text-white tabular-nums">
                    {{ data?.longest_streak ?? 0 }}
                </p>
                <p
                    class="text-[9px] uppercase leading-tight tracking-wider text-gray-500 dark:text-gray-400 sm:text-[10px]"
                >
                    Mejor racha
                </p>
            </div>
        </div>

        <div
            class="relative mt-3 pt-3 border-t border-orange-300/40 dark:border-orange-500/30 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1 text-xs text-orange-700/80 dark:text-orange-300/80 font-semibold"
        >
            <span>📊 {{ data?.total_workouts ?? 0 }} entrenamientos totales</span>
            <span>💪 {{ data?.total_sets ?? 0 }} sets</span>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';

defineProps({
    data: { type: Object, default: () => ({}) },
});

const STORAGE_KEY = 'gymapp_hide_gamification';

const readStorage = () => {
    try {
        return typeof localStorage !== 'undefined' && localStorage.getItem(STORAGE_KEY) === 'true';
    } catch {
        return false;
    }
};

const hidden = ref(readStorage());

const toggleGamification = () => {
    hidden.value = !hidden.value;
    try {
        localStorage.setItem(STORAGE_KEY, String(hidden.value));
    } catch (e) {
        console.error('[StreakCard] localStorage error', e);
    }
};
</script>
