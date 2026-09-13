<template>
    <div>
        <!-- Stats grid: Kinetic Obsidian (2 cols mobile, 4 cols desktop) -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-2.5 md:gap-3 mb-5 md:mb-6">
            <ObsidianStat
                label="Series totales"
                :value="seriesTotales"
                accent="gray"
            />
            <ObsidianStat
                label="Completadas"
                :value="seriesCompletadas"
                accent="emerald"
                :trend="seriesTotales ? Math.round((seriesCompletadas / seriesTotales) * 100) + '%' : ''"
            />
            <ObsidianStat
                label="Peso registrado"
                :value="pesoRegistrado"
                unit="kg"
                accent="violet"
            />
            <ObsidianStat
                label="Repeticiones"
                :value="repsRegistradas"
                accent="orange"
            />
        </div>

        <!-- Progreso del día -->
        <div class="obs-card p-4 md:p-5 mb-5 md:mb-6">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <p class="text-xs obs-text-secondary uppercase tracking-wider font-bold">
                        Progreso del día
                    </p>
                    <p class="text-base md:text-lg font-black text-gray-900 dark:text-white mt-0.5">
                        {{ seriesCompletadas }} de {{ seriesTotales }} series
                    </p>
                </div>
                <span class="text-sm md:text-base font-black text-violet-600 dark:text-violet-300 tabular-nums">
                    {{ progresoDia }}%
                </span>
            </div>
            <div class="obs-progress-track">
                <div
                    class="obs-progress-fill"
                    :style="{ width: `${progresoDia}%` }"
                    role="progressbar"
                    :aria-valuenow="progresoDia"
                    aria-valuemin="0"
                    aria-valuemax="100"
                ></div>
            </div>
            <div class="mt-3 flex flex-wrap gap-x-3 gap-y-1 text-xs md:text-sm obs-text-secondary">
                <span>
                    Promedio de peso:
                    <span class="font-bold text-gray-900 dark:text-white tabular-nums">{{ pesoPromedio }} kg</span>
                </span>
                <span class="obs-text-tertiary">•</span>
                <span>
                    Series pendientes:
                    <span class="font-bold text-gray-900 dark:text-white tabular-nums">{{ seriesPendientes }}</span>
                </span>
            </div>
        </div>
    </div>
</template>

<script setup>
import ObsidianStat from '../common/obsidian/ObsidianStat.vue';

defineProps({
    seriesTotales: { type: Number, required: true },
    seriesCompletadas: { type: Number, required: true },
    seriesPendientes: { type: Number, required: true },
    pesoRegistrado: { type: [String, Number], required: true },
    pesoPromedio: { type: [String, Number], required: true },
    repsRegistradas: { type: Number, required: true },
    progresoDia: { type: Number, required: true },
});
</script>
