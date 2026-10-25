<template>
    <section
        class="bg-[#131924] border border-[#232D42] rounded-xl shadow-lg overflow-hidden"
    >
        <div
            class="p-4 bg-[#18202F]/60 border-b border-[#232D42] flex items-center justify-between"
        >
            <div class="flex items-center gap-2.5">
                <div class="w-2.5 h-2.5 rounded bg-indigo-500"></div>
                <h2 class="text-sm font-bold text-white tracking-wide uppercase">
                    Calendario de Entrenamientos
                </h2>
            </div>
            <div class="text-xs font-mono text-indigo-400 bg-indigo-500/10 border border-indigo-500/30 px-2.5 py-0.5 rounded">
                Últimas {{ weeks }} semanas
            </div>
        </div>

        <div class="p-5 space-y-6">
            <!-- Leyenda -->
            <div class="flex items-center justify-between text-xs text-slate-400 font-mono">
                <span>{{ formatDate(weeksAgo[0]?.date) }} – {{ formatDate(todayLabel) }}</span>
                <div class="flex items-center gap-2">
                    <span>Menos</span>
                    <div class="flex gap-1">
                        <div class="w-3 h-3 rounded-sm bg-[#18202F] border border-[#232D42]"></div>
                        <div class="w-3 h-3 rounded-sm bg-emerald-500/25"></div>
                        <div class="w-3 h-3 rounded-sm bg-emerald-500/50"></div>
                        <div class="w-3 h-3 rounded-sm bg-emerald-500/75"></div>
                        <div class="w-3 h-3 rounded-sm bg-emerald-400"></div>
                    </div>
                    <span>Más</span>
                </div>
            </div>

            <!-- Heatmap grid: 7 días × N semanas -->
            <div class="overflow-x-auto pb-2">
                <div class="inline-flex gap-1.5">
                    <!-- Labels de días -->
                    <div class="flex flex-col gap-1.5 text-[10px] text-slate-400 font-mono pt-4 pr-1">
                        <div v-for="dia in diasSemana" :key="dia" class="h-3.5 flex items-center">
                            {{ dia }}
                        </div>
                    </div>

                    <!-- Columnas por semana -->
                    <div
                        v-for="(semana, sIdx) in heatmap"
                        :key="sIdx"
                        class="flex flex-col gap-1.5"
                    >
                        <div class="text-[10px] text-slate-400 font-mono text-center h-3.5 leading-3.5">
                            {{ semana[0]?.labelMes || '' }}
                        </div>
                        <div
                            v-for="(dia, dIdx) in semana"
                            :key="dIdx"
                            class="w-3.5 h-3.5 rounded-sm transition-all"
                            :class="
                                dia.color +
                                (dia.futuro
                                    ? ' opacity-30'
                                    : ' hover:ring-1 hover:ring-indigo-400 cursor-pointer')
                            "
                            :title="
                                dia.futuro
                                    ? ''
                                    : `${formatDate(dia.date)}: ${dia.count} ${dia.count === 1 ? 'serie' : 'series'}`
                            "
                        ></div>
                    </div>
                </div>
            </div>

            <!-- Resumen -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3.5 pt-2 border-t border-[#232D42]">
                <div class="text-center p-3.5 bg-[#101521] border border-[#232D42] rounded-xl">
                    <p class="text-2xl font-extrabold text-indigo-400 font-mono">
                        {{ totalSeries }}
                    </p>
                    <p class="text-[11px] text-slate-400 uppercase tracking-wider mt-1">Series totales</p>
                </div>
                <div class="text-center p-3.5 bg-[#101521] border border-[#232D42] rounded-xl">
                    <p class="text-2xl font-extrabold text-emerald-400 font-mono">
                        {{ diasEntrenados }}
                    </p>
                    <p class="text-[11px] text-slate-400 uppercase tracking-wider mt-1">Días entrenados</p>
                </div>
                <div class="text-center p-3.5 bg-[#101521] border border-[#232D42] rounded-xl">
                    <p class="text-2xl font-extrabold text-amber-400 font-mono">
                        {{ rachaActual }}
                    </p>
                    <p class="text-[11px] text-slate-400 uppercase tracking-wider mt-1">Racha actual (días)</p>
                </div>
                <div class="text-center p-3.5 bg-[#101521] border border-[#232D42] rounded-xl">
                    <p class="text-2xl font-extrabold text-violet-400 font-mono">
                        {{ rachaMax }}
                    </p>
                    <p class="text-[11px] text-slate-400 uppercase tracking-wider mt-1">Mejor racha (días)</p>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
import { computed } from 'vue';
import { useFormatters } from '@/composables/useFormatters';
import { contarSeriesPorFecha } from '@/composables/useHistorialFiltros';

const { formatDateShort, formatMonthShort } = useFormatters();

const props = defineProps({
    historial: { type: Array, default: () => [] },
    weeks: { type: Number, default: 12 },
});

const diasSemana = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];

const today = new Date();
today.setHours(0, 0, 0, 0);
const todayLabel = today.toISOString().split('T')[0];

const startOfCurrentWeek = new Date(today);
const dayOfWeek = (startOfCurrentWeek.getDay() + 6) % 7;
startOfCurrentWeek.setDate(startOfCurrentWeek.getDate() - dayOfWeek);

const countsByDate = computed(() => contarSeriesPorFecha(props.historial));

const maxCount = computed(() => Math.max(1, ...Object.values(countsByDate.value)));

const weeksAgo = computed(() => {
    const result = [];
    for (let w = props.weeks - 1; w >= 0; w--) {
        const week = [];
        for (let d = 0; d < 7; d++) {
            const date = new Date(startOfCurrentWeek);
            date.setDate(startOfCurrentWeek.getDate() - w * 7 + d);
            const iso = date.toISOString().split('T')[0];
            const isFuture = date > today;
            const count = countsByDate.value[iso] || 0;
            const ratio = count / maxCount.value;

            let color = 'bg-[#18202F] border border-[#232D42]/60';
            if (count > 0) {
                if (ratio < 0.25) color = 'bg-emerald-500/25';
                else if (ratio < 0.5) color = 'bg-emerald-500/50';
                else if (ratio < 0.75) color = 'bg-emerald-500/75';
                else color = 'bg-emerald-400 shadow-[0_0_8px_rgba(16,185,129,0.4)]';
            }

            week.push({
                date: iso,
                count,
                color,
                futuro: isFuture,
                labelMes: d === 0 ? formatMonthShort(iso) : '',
            });
        }
        result.push(week);
    }
    return result;
});

const heatmap = computed(() => weeksAgo.value);

const formatDate = (iso) => {
    if (!iso) return '';
    const d = new Date(iso + 'T00:00:00');
    return formatDateShort(d);
};

const totalSeries = computed(() => Object.values(countsByDate.value).reduce((a, b) => a + b, 0));
const diasEntrenados = computed(
    () => Object.values(countsByDate.value).filter((c) => c > 0).length
);

const rachaActual = computed(() => {
    let racha = 0;
    const checkDate = new Date(today);
    while (true) {
        const iso = checkDate.toISOString().split('T')[0];
        if (countsByDate.value[iso]) {
            racha++;
            checkDate.setDate(checkDate.getDate() - 1);
        } else {
            break;
        }
    }
    return racha;
});

const rachaMax = computed(() => {
    let max = 0;
    let current = 0;
    const sortedDates = Object.keys(countsByDate.value).sort();
    if (sortedDates.length === 0) return 0;

    let prevDate = null;
    for (const iso of sortedDates) {
        const d = new Date(iso + 'T00:00:00');
        if (prevDate && (d - prevDate) / (1000 * 60 * 60 * 24) === 1) {
            current++;
        } else {
            current = 1;
        }
        max = Math.max(max, current);
        prevDate = d;
    }
    return max;
});
</script>
