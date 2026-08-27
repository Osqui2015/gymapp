<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import axios from 'axios'

const props = defineProps({
    ejercicioNombre: { type: String, required: true },
    userId: { type: Number, default: null },
    formula: { type: String, default: 'epley' },
    months: { type: Number, default: 6 },
    height: { type: String, default: 'h-64' },
})

const loading = ref(false)
const error = ref(null)
const data = ref(null)

const fetchData = async () => {
    if (!props.ejercicioNombre) return
    loading.value = true
    error.value = null
    try {
        const params = { ejercicio_nombre: props.ejercicioNombre, formula: props.formula, months: props.months }
        if (props.userId) params.user_id = props.userId
        const res = await axios.get('/api/stats/estimated-1rm', { params })
        data.value = res.data
    } catch (e) {
        error.value = e?.response?.data?.error || e?.response?.data?.message || 'No se pudo cargar'
    } finally {
        loading.value = false
    }
}

onMounted(fetchData)
watch(() => [props.ejercicioNombre, props.formula, props.userId, props.months], fetchData)

const chartSize = computed(() => {
    const w = 600
    const h = 220
    const padL = 40
    const padR = 16
    const padT = 16
    const padB = 28
    return { w, h, padL, padR, padT, padB }
})

const chart = computed(() => {
    if (!data.value?.timeline?.length) return null
    const tl = data.value.timeline
    const { w, h, padL, padR, padT, padB } = chartSize.value
    const innerW = w - padL - padR
    const innerH = h - padT - padB

    const values = tl.map((p) => p.estimated_1rm)
    const minV = Math.floor(Math.min(...values) * 0.95)
    const maxV = Math.ceil(Math.max(...values) * 1.05)
    const range = maxV - minV || 1

    const xs = (i) => padL + (tl.length === 1 ? innerW / 2 : (i / (tl.length - 1)) * innerW)
    const ys = (v) => padT + innerH - ((v - minV) / range) * innerH

    const path = tl.map((p, i) => `${i === 0 ? 'M' : 'L'} ${xs(i).toFixed(1)} ${ys(p.estimated_1rm).toFixed(1)}`).join(' ')

    // y-axis ticks (4)
    const yTicks = []
    for (let i = 0; i <= 3; i++) {
        const v = minV + (range * i) / 3
        yTicks.push({ v: Math.round(v), y: ys(v) })
    }

    // x-axis ticks: primera, mitad, última
    const xTicks = []
    if (tl.length === 1) {
        xTicks.push({ i: 0, label: tl[0].fecha.slice(5) })
    } else {
        const stops = [0, Math.floor(tl.length / 2), tl.length - 1]
        for (const i of stops) {
            xTicks.push({ i, label: tl[i].fecha.slice(5) })
        }
    }

    return { path, points: tl.map((p, i) => ({ x: xs(i), y: ys(p.estimated_1rm), p })), yTicks, xTicks, w, h, padL, padR, padT, padB }
})

const bestLabel = computed(() => {
    const b = data.value?.best_1rm
    if (!b) return null
    return `${b.value} kg`
})

const lastLabel = computed(() => {
    const e = data.value?.estimated_1rm
    if (!e) return null
    return `${e.value} kg`
})
</script>

<template>
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <header class="mb-3 flex items-start justify-between gap-3">
            <div class="min-w-0">
                <h3 class="truncate text-sm font-semibold text-gray-700 dark:text-gray-200">
                    1RM estimado · {{ ejercicioNombre }}
                </h3>
                <p class="text-[11px] text-gray-500 dark:text-gray-400">
                    Fórmula {{ formula === 'lander' ? 'Lander' : 'Epley' }} · últimos {{ months }} meses
                </p>
            </div>
            <div v-if="data && data.total_sets > 0" class="shrink-0 text-right">
                <p v-if="bestLabel" class="text-xs text-gray-500 dark:text-gray-400">Mejor</p>
                <p v-if="bestLabel" class="text-lg font-bold text-emerald-600 dark:text-emerald-400">{{ bestLabel }}</p>
                <p v-if="data.pr_count > 0" class="text-[10px] text-amber-600 dark:text-amber-400">+{{ data.pr_count }} PR{{ data.pr_count > 1 ? 's' : '' }}</p>
            </div>
        </header>

        <div v-if="loading" class="flex h-40 items-center justify-center text-sm text-gray-400">Cargando…</div>
        <div v-else-if="error" class="rounded-md bg-red-50 p-3 text-sm text-red-600 dark:bg-red-950/30">{{ error }}</div>
        <div v-else-if="!data || data.total_sets === 0" class="rounded-md bg-gray-50 p-6 text-center text-sm text-gray-500 dark:bg-gray-900/50">
            Sin sets con peso y reps para este ejercicio.
        </div>
        <div v-else>
            <svg
                :viewBox="`0 0 ${chartSize.w} ${chartSize.h}`"
                class="w-full"
                :class="height"
                preserveAspectRatio="none"
                role="img"
                aria-label="Evolución del 1RM estimado"
            >
                <!-- grid lines -->
                <g v-for="(t, i) in chart.yTicks" :key="`y-${i}`">
                    <line :x1="chart.padL" :x2="chart.w - chart.padR" :y1="t.y" :y2="t.y" stroke="currentColor" stroke-opacity="0.1" stroke-width="1" />
                    <text :x="chart.padL - 6" :y="t.y + 3" text-anchor="end" class="fill-gray-400" style="font-size: 10px">{{ t.v }}</text>
                </g>
                <!-- x labels -->
                <g v-for="(t, i) in chart.xTicks" :key="`x-${i}`">
                    <text :x="chart.points[t.i]?.x ?? 0" :y="chart.h - 6" text-anchor="middle" class="fill-gray-400" style="font-size: 10px">{{ t.label }}</text>
                </g>
                <!-- path -->
                <path :d="chart.path" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-indigo-500" />
                <!-- points -->
                <g v-for="(pt, i) in chart.points" :key="`p-${i}`">
                    <circle :cx="pt.x" :cy="pt.y" r="3" class="fill-indigo-500" />
                    <title>{{ pt.p.fecha }} · {{ pt.p.weight }}kg × {{ pt.p.reps }} → {{ pt.p.estimated_1rm }}kg</title>
                </g>
            </svg>
            <p v-if="lastLabel" class="mt-1 text-right text-[11px] text-gray-500 dark:text-gray-400">
                Último: <span class="font-semibold text-gray-700 dark:text-gray-200">{{ lastLabel }}</span>
                <span v-if="data.estimated_1rm" class="text-gray-400"> · {{ data.estimated_1rm.weight }}kg × {{ data.estimated_1rm.reps }}</span>
            </p>
        </div>
    </div>
</template>
