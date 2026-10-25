<template>
    <!-- Estado minimizado / gamificación desactivada -->
    <div
        v-if="hidden"
        data-testid="streak-card-minimized"
        class="p-space-md rounded-2xl bg-surface-container-low flex items-center justify-between transition-all shadow-sm"
    >
        <div class="flex items-center gap-2 text-label-sm text-outline">
            <span class="material-symbols-outlined text-[18px] text-secondary">emoji_events</span>
            <span class="font-medium">Gamificación y racha ocultas</span>
        </div>
        <button
            type="button"
            data-testid="streak-show-button"
            @click="toggleGamification"
            class="text-label-sm font-bold text-secondary hover:underline cursor-pointer"
        >
            Mostrar
        </button>
    </div>

    <!-- Estado expandido / gamificación activa -->
    <div
        v-else
        data-testid="streak-card-expanded"
        class="p-space-lg rounded-2xl bg-surface-container-low shadow-sm flex flex-col justify-between gap-space-md relative overflow-hidden h-full"
    >
        <div class="absolute -right-6 -top-6 w-28 h-28 rounded-full bg-secondary-container/20 blur-2xl pointer-events-none"></div>

        <div class="relative flex items-start justify-between">
            <div class="flex flex-col">
                <span class="font-label-sm text-label-sm uppercase tracking-wider text-outline">Racha Actual</span>
                <div class="flex items-baseline gap-1 mt-1">
                    <span class="font-headline-xl text-[36px] leading-none font-bold text-on-surface tabular-nums">
                        {{ data?.current_streak ?? 0 }}
                    </span>
                    <span class="font-headline-md text-headline-md text-secondary">
                        {{ data?.current_streak === 1 ? 'día' : 'días' }}
                    </span>
                </div>
                <span class="text-body-sm text-on-surface-variant mt-1">consecutivos con al menos 1 set</span>
            </div>
            <div class="flex items-center gap-1.5">
                <button
                    type="button"
                    data-testid="streak-hide-button"
                    @click="toggleGamification"
                    class="w-8 h-8 rounded-lg bg-surface-container hover:bg-surface-container-high flex items-center justify-center text-outline hover:text-on-surface transition-colors cursor-pointer"
                    title="Ocultar racha y gamificación"
                    aria-label="Ocultar racha y gamificación"
                >
                    <span class="material-symbols-outlined text-[16px]">visibility_off</span>
                </button>
                <div class="w-10 h-10 rounded-xl bg-secondary-container/40 flex items-center justify-center text-secondary">
                    <span class="material-symbols-outlined text-[22px]">local_fire_department</span>
                </div>
            </div>
        </div>

        <!-- 3-Col Mini Breakdown -->
        <div class="relative grid grid-cols-3 gap-space-xs pt-2">
            <div class="p-2.5 rounded-xl bg-surface-container flex flex-col items-center text-center">
                <span class="font-headline-md text-headline-md text-on-surface font-bold tabular-nums">
                    {{ data?.this_week ?? 0 }}
                </span>
                <span class="text-[10px] text-outline uppercase tracking-wider mt-0.5">Esta semana</span>
            </div>
            <div class="p-2.5 rounded-xl bg-surface-container flex flex-col items-center text-center">
                <span class="font-headline-md text-headline-md text-on-surface font-bold tabular-nums">
                    {{ data?.this_month ?? 0 }}
                </span>
                <span class="text-[10px] text-outline uppercase tracking-wider mt-0.5">Este mes</span>
            </div>
            <div class="p-2.5 rounded-xl bg-surface-container flex flex-col items-center text-center">
                <span class="font-headline-md text-headline-md text-tertiary font-bold tabular-nums">
                    {{ data?.longest_streak ?? 0 }}
                </span>
                <span class="text-[10px] text-outline uppercase tracking-wider mt-0.5">Mejor racha</span>
            </div>
        </div>

        <!-- Card Footer -->
        <div class="relative flex items-center justify-between text-label-sm text-outline pt-1">
            <span class="flex items-center gap-1">
                <span class="material-symbols-outlined text-[14px] text-secondary">fitness_center</span>
                {{ data?.total_workouts ?? 0 }} entrenamientos
            </span>
            <span class="flex items-center gap-1">
                <span class="material-symbols-outlined text-[14px] text-tertiary">format_list_numbered</span>
                {{ data?.total_sets ?? 0 }} sets
            </span>
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
