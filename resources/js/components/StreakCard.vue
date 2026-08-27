<!--
  StreakCard — Card de racha tipo "GitHub contribution streak".
  Muestra la racha actual de días consecutivos con al menos 1 set completado.

  Props:
    - data: { current_streak, longest_streak, this_week, this_month, total_workouts, total_sets }
-->
<template>
    <div class="bg-gradient-to-br from-orange-50 to-red-50 dark:from-orange-900/20 dark:to-red-900/20 rounded-2xl border border-orange-200 dark:border-orange-800 p-5 shadow-sm">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-orange-700 dark:text-orange-300">Racha actual</p>
                <div class="flex items-baseline gap-1.5 mt-1">
                    <span class="text-4xl font-black text-orange-600 dark:text-orange-400">{{ data?.current_streak ?? 0 }}</span>
                    <span class="text-sm font-semibold text-orange-700 dark:text-orange-300">
                        {{ data?.current_streak === 1 ? 'día' : 'días' }}
                    </span>
                </div>
                <p class="text-xs text-orange-700/70 dark:text-orange-300/70 mt-1">
                    consecutivos con al menos 1 set
                </p>
            </div>
            <div class="text-4xl flex-shrink-0">🔥</div>
        </div>

        <div class="mt-4 grid grid-cols-3 gap-2 text-center">
            <div class="bg-white/60 dark:bg-gray-800/40 rounded-lg p-2">
                <p class="text-lg font-bold text-gray-900 dark:text-white">{{ data?.this_week ?? 0 }}</p>
                <p class="text-[10px] uppercase tracking-wider text-gray-500 dark:text-gray-400">Esta semana</p>
            </div>
            <div class="bg-white/60 dark:bg-gray-800/40 rounded-lg p-2">
                <p class="text-lg font-bold text-gray-900 dark:text-white">{{ data?.this_month ?? 0 }}</p>
                <p class="text-[10px] uppercase tracking-wider text-gray-500 dark:text-gray-400">Este mes</p>
            </div>
            <div class="bg-white/60 dark:bg-gray-800/40 rounded-lg p-2">
                <p class="text-lg font-bold text-gray-900 dark:text-white">{{ data?.longest_streak ?? 0 }}</p>
                <p class="text-[10px] uppercase tracking-wider text-gray-500 dark:text-gray-400">Mejor racha</p>
            </div>
        </div>

        <div class="mt-3 pt-3 border-t border-orange-200/60 dark:border-orange-800/60 flex items-center justify-between text-xs text-orange-700/80 dark:text-orange-300/80">
            <span>📊 {{ data?.total_workouts ?? 0 }} entrenamientos totales</span>
            <span>💪 {{ data?.total_sets ?? 0 }} sets</span>
        </div>
    </div>
</template>

<script setup>
defineProps({
    data: { type: Object, default: () => ({}) },
});
</script>
