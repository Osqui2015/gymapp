<template>
    <div class="p-space-lg rounded-2xl bg-surface-container-low shadow-sm flex flex-col justify-between gap-space-md h-full">
        <div class="flex items-start justify-between gap-2">
            <div>
                <h3 class="font-headline-md text-headline-md text-on-surface">Actividad</h3>
                <p class="text-body-sm text-on-surface-variant">
                    Sets completados por día en el último año
                </p>
            </div>
            <!-- Legend -->
            <div class="flex items-center gap-1 text-[10px] text-outline">
                <span>Menos</span>
                <span
                    v-for="i in 5"
                    :key="i"
                    :class="['w-2.5 h-2.5 rounded-sm', colorFor(i - 1)]"
                ></span>
                <span>Más</span>
            </div>
        </div>

        <!-- Heatmap Dot Matrix -->
        <div class="overflow-x-auto py-2">
            <div
                class="inline-grid grid-flow-col gap-1.5 min-w-[320px]"
                :style="{ gridTemplateRows: 'repeat(7, 1fr)' }"
            >
                <template v-for="(week, wi) in weeks" :key="wi">
                    <div
                        v-for="day in week"
                        :key="day.fecha || `empty-${wi}-${day.col}`"
                        :class="[
                            'w-3 h-3 rounded-sm transition-all cursor-default',
                            day.fecha ? colorFor(day.level) : 'bg-transparent',
                            day.level === 4 ? 'shadow-[0_0_8px_rgba(78,222,163,0.4)]' : '',
                            hover && day.fecha ? 'hover:ring-1 hover:ring-secondary' : '',
                        ]"
                        :title="
                            day.fecha
                                ? `${day.fecha}: ${day.sets} sets, ${day.volumen?.toFixed(0) ?? 0} kg`
                                : ''
                        "
                    ></div>
                </template>
            </div>
        </div>

        <div class="flex items-center justify-between text-label-sm text-outline pt-1">
            <span>{{ formatDate(data?.from) }} → {{ formatDate(data?.to) }}</span>
            <span class="text-tertiary font-semibold">
                {{ (data?.total_sets ?? 0).toLocaleString() }} sets en el año
            </span>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    data: { type: Object, default: () => ({ days: [] }) },
    hover: { type: Boolean, default: true },
});

const weeks = computed(() => {
    if (!props.data?.days?.length) return [];

    const byDate = {};
    for (const d of props.data.days) {
        byDate[d.fecha] = { sets: d.sets, volumen: d.volumen };
    }

    const from = new Date(props.data.from + 'T00:00:00');
    const to = new Date(props.data.to + 'T00:00:00');
    const firstSunday = new Date(from);
    firstSunday.setDate(firstSunday.getDate() - firstSunday.getDay());

    const maxSets = Math.max(1, ...Object.values(byDate).map((d) => d.sets));

    const result = [];
    const cursor = new Date(firstSunday);
    while (cursor <= to) {
        const week = [];
        for (let dow = 0; dow < 7; dow++) {
            const isoDate = cursor.toISOString().slice(0, 10);
            const inRange = cursor >= from && cursor <= to;
            const entry = inRange ? byDate[isoDate] : null;
            const sets = entry?.sets ?? 0;
            const level = sets === 0 ? 0 : Math.min(4, Math.ceil((sets / maxSets) * 4));
            week.push({
                col: dow,
                fecha: inRange ? isoDate : null,
                sets,
                volumen: entry?.volumen ?? 0,
                level,
            });
            cursor.setDate(cursor.getDate() + 1);
        }
        result.push(week);
    }
    return result;
});

function colorFor(level) {
    if (level === 0) return 'bg-surface-container-high';
    if (level === 1) return 'bg-tertiary/25';
    if (level === 2) return 'bg-tertiary/50';
    if (level === 3) return 'bg-tertiary/75';
    if (level === 4) return 'bg-tertiary';
    return 'bg-surface-container-high';
}

function formatDate(iso) {
    if (!iso) return '';
    const d = new Date(iso + 'T00:00:00');
    return new Intl.DateTimeFormat('es-MX', { month: 'short', year: 'numeric' }).format(d);
}
</script>
