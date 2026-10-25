<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
    userId: { type: Number, default: null },
});

const loading = ref(true);
const error = ref(null);
const data = ref(null);
const range = ref('30d');

const ranges = [
    { key: '30d', label: '30d', subtitle: '30 días' },
    { key: '90d', label: '90d', subtitle: '90 días' },
    { key: '1y', label: '1Y', subtitle: '1 año' },
    { key: 'all', label: 'All', subtitle: 'Histórico' },
];

const currentRangeSubtitle = computed(() => {
    const found = ranges.find((r) => r.key === range.value);
    return found ? found.subtitle : '30 días';
});

const fetchData = async () => {
    loading.value = true;
    error.value = null;
    try {
        const params = { range: range.value };
        if (props.userId) params.user_id = props.userId;
        const res = await axios.get('/api/historial/effort-analysis', { params });
        data.value = res.data;
    } catch (e) {
        error.value = e?.response?.data?.message || 'No se pudo cargar el análisis de esfuerzo.';
    } finally {
        loading.value = false;
    }
};

onMounted(fetchData);
watch(range, fetchData);
// Cuando el trainer/admin cambia de alumno hay que recargar: sin esto la
// tarjeta sigue mostrando el esfuerzo del alumno anterior.
watch(() => props.userId, fetchData);

const maxBucketSets = computed(() => {
    if (!data.value) return 1;
    const vals = Object.values(data.value.distribution || {});
    return Math.max(1, ...vals);
});

const bucketWidth = (count) => {
    if (!count) return '0%';
    const pct = Math.round((count / maxBucketSets.value) * 100);
    return `${Math.max(pct, 6)}%`;
};

const bucketColor = (bucket) => {
    const n = Number(bucket);
    if (n === 0) return 'bg-primary';
    if (n === 1) return 'bg-tertiary';
    if (n === 2) return 'bg-secondary';
    return 'bg-outline-variant';
};

// Sparkline SVG path & points (400 x 60 viewBox)
const sparkline = computed(() => {
    if (!data.value || !data.value.weekly_trend?.length) {
        return { path: '', points: [], firstLabel: '', lastLabel: '' };
    }
    const items = data.value.weekly_trend;
    const w = 400;
    const h = 60;
    const padX = 12;
    const padY = 12;
    const usableW = w - padX * 2;
    const usableH = h - padY * 2;

    const points = items.map((it, idx) => {
        const x =
            items.length === 1
                ? w / 2
                : Math.round(padX + (idx / (items.length - 1)) * usableW);
        // RIR 0 (high effort) at top (padY), RIR 4 at bottom (h - padY)
        const clamped = Math.max(0, Math.min(4, Number(it.avg_rir ?? 2)));
        const y = Math.round(padY + (clamped / 4) * usableH);
        return { x, y, ...it };
    });

    let path = '';
    if (points.length === 1) {
        path = `M ${padX},${points[0].y} L ${w - padX},${points[0].y}`;
    } else {
        path = points.reduce((acc, pt, i) => {
            if (i === 0) return `M ${pt.x},${pt.y}`;
            const prev = points[i - 1];
            const cx = Math.round((prev.x + pt.x) / 2);
            return `${acc} Q ${cx},${prev.y} ${pt.x},${pt.y}`;
        }, '');
    }

    const formatWeek = (iso) => {
        if (!iso) return '';
        const parts = iso.split('-');
        return parts.length === 3 ? `${parts[2]}-${parts[1]}` : iso;
    };

    return {
        path,
        points,
        firstLabel: formatWeek(items[0]?.week_start),
        lastLabel: formatWeek(items[items.length - 1]?.week_start),
    };
});

const topTrackedColor = (idx) => {
    const colors = ['text-tertiary', 'text-secondary', 'text-primary', 'text-tertiary', 'text-outline'];
    return colors[idx % colors.length];
};
</script>

<template>
    <div class="p-space-lg rounded-2xl bg-surface-container-low shadow-sm flex flex-col justify-between gap-space-md h-full">
        <!-- Card Header & Range Toggle -->
        <div class="flex items-start justify-between gap-2">
            <div>
                <h3 class="font-headline-md text-headline-md text-on-surface">Esfuerzo</h3>
                <p class="text-body-sm text-on-surface-variant">
                    RIR / RPE por set · {{ currentRangeSubtitle }}
                </p>
            </div>
            <div class="inline-flex p-0.5 bg-surface-container rounded-lg text-[11px] font-semibold">
                <button
                    v-for="r in ranges"
                    :key="r.key"
                    type="button"
                    @click="range = r.key"
                    :class="[
                        'px-2 py-1 rounded-md transition-colors cursor-pointer',
                        range === r.key
                            ? 'bg-secondary-container text-on-secondary-container'
                            : 'text-outline hover:text-on-surface',
                    ]"
                >
                    {{ r.label }}
                </button>
            </div>
        </div>

        <!-- Loading / Error / Empty States -->
        <div v-if="loading" class="py-10 text-center text-body-sm text-outline">
            Cargando análisis de esfuerzo…
        </div>
        <div v-else-if="error" class="py-6 text-center text-body-sm text-error">
            {{ error }}
        </div>
        <div
            v-else-if="data && data.summary.sets_with_effort === 0"
            class="rounded-xl bg-surface-container p-6 text-center text-body-sm text-on-surface-variant"
        >
            <p class="font-semibold text-on-surface">Todavía no registraste RIR o RPE</p>
            <p class="mt-1 text-body-sm text-outline">
                Al cargar tus series en el workout podés indicar cuántas repeticiones en reserva (RIR 0-5) te quedaron.
            </p>
        </div>

        <!-- Main Telemetry Content -->
        <template v-else-if="data">
            <!-- Highlight Telemetry Metrics -->
            <div class="grid grid-cols-2 gap-space-sm p-space-md rounded-xl bg-surface-container">
                <div>
                    <span class="text-[10px] text-outline uppercase tracking-wider">Promedio</span>
                    <div class="font-headline-lg text-headline-lg text-on-surface font-bold mt-0.5 tabular-nums">
                        RIR {{ data.summary.avg_rir ?? '—' }}
                    </div>
                    <span class="text-[11px] text-on-surface-variant">average effort</span>
                </div>
                <div>
                    <span class="text-[10px] text-outline uppercase tracking-wider">A RIR ≤ 2</span>
                    <div class="font-headline-lg text-headline-lg text-tertiary font-bold mt-0.5 tabular-nums">
                        {{ data.summary.high_effort_pct ?? 0 }}%
                    </div>
                    <span class="text-[11px] text-on-surface-variant">at high effort</span>
                </div>
            </div>

            <p class="text-[11px] text-outline px-1">
                {{ data.summary.sets_with_effort }} de {{ data.summary.total_sets }} sets con esfuerzo · {{ data.summary.coverage_pct }}%
            </p>

            <!-- Weekly Trend Sparkline (Inline SVG) -->
            <div v-if="data.weekly_trend?.length" class="flex flex-col gap-1.5">
                <div class="flex items-center justify-between text-[11px] text-outline uppercase tracking-wider">
                    <span>Semana a semana</span>
                    <span>{{ data.weekly_trend.length }} {{ data.weekly_trend.length === 1 ? 'semana' : 'semanas' }}</span>
                </div>
                <div class="p-3 rounded-xl bg-surface-container-lowest/60 relative">
                    <svg class="w-full h-14 overflow-visible" viewBox="0 0 400 60">
                        <line stroke="#262a33" stroke-dasharray="3 3" stroke-width="1" x1="0" x2="400" y1="15" y2="15"></line>
                        <line stroke="#262a33" stroke-dasharray="3 3" stroke-width="1" x1="0" x2="400" y1="45" y2="45"></line>
                        <path
                            v-if="sparkline.path"
                            :d="sparkline.path"
                            fill="none"
                            stroke="#4edea3"
                            stroke-linecap="round"
                            stroke-width="2.5"
                        ></path>
                        <circle
                            v-for="(pt, idx) in sparkline.points"
                            :key="idx"
                            :cx="pt.x"
                            :cy="pt.y"
                            fill="#4edea3"
                            r="4"
                            class="drop-shadow-[0_0_6px_#4edea3]"
                        >
                            <title>{{ pt.week_start }}: RIR {{ pt.avg_rir }} ({{ pt.sets }} sets)</title>
                        </circle>
                    </svg>
                    <div class="flex justify-between text-[10px] text-outline mt-1">
                        <span>{{ sparkline.firstLabel }}</span>
                        <span>{{ sparkline.lastLabel }}</span>
                    </div>
                </div>
            </div>

            <!-- Distribution Bars -->
            <div class="flex flex-col gap-1.5">
                <span class="text-[11px] text-outline uppercase tracking-wider">Distribución</span>
                <div class="flex flex-col gap-1.5 text-label-sm">
                    <div
                        v-for="b in [0, 1, 2, 3, 4]"
                        :key="b"
                        class="flex items-center gap-2"
                    >
                        <span class="w-10 text-outline">RIR {{ b === 4 ? '4+' : b }}</span>
                        <div class="flex-1 h-2 rounded-full bg-surface-container overflow-hidden">
                            <div
                                class="h-full rounded-full transition-all duration-300"
                                :class="bucketColor(b)"
                                :style="{ width: bucketWidth(data.distribution[String(b)] || 0) }"
                            ></div>
                        </div>
                        <span
                            class="w-6 text-right tabular-nums"
                            :class="(data.distribution[String(b)] || 0) > 0 ? 'text-on-surface font-semibold' : 'text-outline'"
                        >
                            {{ data.distribution[String(b)] || 0 }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Top Tracked Mini List -->
            <div v-if="data.by_exercise?.length" class="flex flex-col gap-1.5 pt-1">
                <span class="text-[11px] text-outline uppercase tracking-wider">Más Trackeados</span>
                <div class="flex flex-col gap-1 text-body-sm">
                    <div
                        v-for="(ex, idx) in data.by_exercise.slice(0, 5)"
                        :key="ex.ejercicio"
                        class="flex items-center justify-between py-1 px-2 rounded-lg hover:bg-surface-container transition-colors"
                    >
                        <span class="text-on-surface truncate pr-2">{{ ex.ejercicio }}</span>
                        <span
                            class="text-label-sm whitespace-nowrap tabular-nums"
                            :class="topTrackedColor(idx)"
                        >
                            {{ ex.sets }} sets · {{ ex.avg_rir }}
                        </span>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>
