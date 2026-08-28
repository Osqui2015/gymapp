<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import axios from 'axios'
import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts'

const props = defineProps({
    userId: { type: Number, default: null },
})

const loading = ref(false)
const error = ref(null)
const data = ref(null)
const window = ref('30')

const WINDOWS = [
    { key: '30', label: '30d' },
    { key: '90', label: '90d' },
    { key: '365', label: '1Y' },
    { key: 'all', label: 'All' },
]

const fetchData = async () => {
    loading.value = true
    error.value = null
    try {
        const params = { window: window.value }
        if (props.userId) params.user_id = props.userId
        const res = await axios.get('/api/stats/esfuerzo', { params })
        data.value = res.data
    } catch (e) {
        error.value = e?.response?.data?.message || 'No se pudo cargar el esfuerzo'
    } finally {
        loading.value = false
    }
}

onMounted(fetchData)
watch(window, fetchData)
watch(() => props.userId, fetchData)

const coverage = computed(() => {
    if (!data.value || data.value.total_sets === 0) return 0
    return Math.round((data.value.sets_with_esfuerzo / data.value.total_sets) * 100)
})

const distribution = computed(() => {
    if (!data.value) return []
    const rows = []
    data.value.distribucion.rir.forEach((count, val) => {
        rows.push({ label: `RIR ${val}`, val, tipo: 'rir', count })
    })
    data.value.distribucion.rpe.forEach((count, val) => {
        if (val >= 6) {
            rows.push({ label: `RPE ${val}`, val, tipo: 'rpe', count })
        }
    })
    return rows
})

const maxDistCount = computed(() => {
    if (!data.value) return 0
    return Math.max(0, ...distribution.value.map((d) => d.count))
})

const avgLabel = computed(() => {
    if (!data.value) return '–'
    const { rir, rpe } = data.value.avg_por_tipo
    if (rir !== null) return `RIR ${rir}`
    if (rpe !== null) return `RPE ${rpe}`
    return '–'
})

// Para el LineChart: solo valores no-null en sus semanas
const chartData = computed(() => {
    if (!data.value?.tendencia) return []
    return data.value.tendencia.map((w) => ({
        week: w.week_label,
        rir: w.rir,
        rpe: w.rpe,
    }))
})

const hasRir = computed(() => data.value?.avg_por_tipo?.rir !== null && data.value?.avg_por_tipo?.rir !== undefined)
const hasRpe = computed(() => data.value?.avg_por_tipo?.rpe !== null && data.value?.avg_por_tipo?.rpe !== undefined)

// Dominante (la que más sets tiene en la ventana)
const dominant = computed(() => {
    if (!data.value?.tendencia) return 'rir'
    const counts = data.value.tendencia.reduce(
        (acc, w) => {
            acc.rir += w.rir_sets || 0
            acc.rpe += w.rpe_sets || 0
            return acc
        },
        { rir: 0, rpe: 0 },
    )
    if (counts.rir === 0 && counts.rpe === 0) return 'rir'
    return counts.rir >= counts.rpe ? 'rir' : 'rpe'
})

// Y-axis range adaptativo a la escala dominante
const yDomain = computed(() => {
    if (dominant.value === 'rir') return [0, 5]
    return [5, 10]
})

const reverseY = computed(() => dominant.value === 'rir')

const totalChartPoints = computed(() => chartData.value.length)
</script>

<template>
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <header class="mb-3 flex items-center justify-between gap-2">
            <div>
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Esfuerzo</h3>
                <p class="text-[11px] text-gray-500 dark:text-gray-400">
                    RIR / RPE por set · <span v-if="data?.window">{{ data.window.label }}</span>
                </p>
            </div>
            <div class="inline-flex rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden text-[11px] font-semibold">
                <button
                    v-for="w in WINDOWS"
                    :key="w.key"
                    type="button"
                    :class="[
                        'px-2.5 py-1 transition-colors',
                        window === w.key
                            ? 'bg-indigo-600 text-white'
                            : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700'
                    ]"
                    @click="window = w.key"
                >{{ w.label }}</button>
            </div>
        </header>

        <div v-if="loading" class="py-8 text-center text-sm text-gray-400">Cargando…</div>

        <div v-else-if="error" class="py-6 text-center text-sm text-red-500">{{ error }}</div>

        <div v-else-if="!data || data.sets_with_esfuerzo === 0" class="py-6 text-center">
            <p class="text-sm text-gray-500 dark:text-gray-400">Todavía no registraste esfuerzo en tus sets.</p>
            <p class="mt-1 text-xs text-gray-400">Empezá marcando RIR o RPE al guardar cada set.</p>
        </div>

        <div v-else class="space-y-4">
            <!-- Promedio + "A alto esfuerzo" -->
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <p class="text-[10px] uppercase tracking-wider text-gray-500 dark:text-gray-400">Promedio</p>
                    <p class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ avgLabel }}</p>
                    <p class="text-[10px] text-gray-400">average effort</p>
                </div>
                <div class="text-right">
                    <p class="text-[10px] uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ dominant === 'rir' ? 'A RIR ≤ 2' : 'A RPE ≥ 8' }}
                    </p>
                    <p class="text-2xl font-bold text-amber-500">{{ data.avg_hard }}<span class="text-base">%</span></p>
                    <p class="text-[10px] text-gray-400">at high effort</p>
                </div>
            </div>

            <!-- Cobertura -->
            <div class="rounded-md bg-gray-50 px-3 py-2 dark:bg-gray-900/50">
                <p class="text-[11px] text-gray-600 dark:text-gray-300">
                    <span class="font-semibold">{{ data.sets_with_esfuerzo }}</span>
                    de <span class="font-semibold">{{ data.total_sets }}</span> sets con esfuerzo
                    · <span class="font-semibold text-indigo-600 dark:text-indigo-400">{{ coverage }}%</span>
                </p>
            </div>

            <!-- Week by week chart -->
            <div v-if="totalChartPoints > 0">
                <div class="mb-1 flex items-center justify-between">
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        Semana a semana
                    </p>
                    <p class="text-[10px] text-gray-400">
                        {{ totalChartPoints }} semana{{ totalChartPoints > 1 ? 's' : '' }}
                    </p>
                </div>
                <div style="width: 100%; height: 140px">
                    <ResponsiveContainer width="100%" height="100%">
                        <LineChart :data="chartData" :margin="{ top: 4, right: 8, left: -16, bottom: 0 }">
                            <CartesianGrid stroke-dasharray="3 3" stroke="#9ca3af" :stroke-opacity="0.3" />
                            <XAxis
                                data-key="week"
                                :tick="{ fontSize: 10, fill: '#9ca3af' }"
                                :tick-line="false"
                                :axis-line="false"
                                interval="preserveStartEnd"
                            />
                            <YAxis
                                :domain="yDomain"
                                :reversed="reverseY"
                                :tick="{ fontSize: 10, fill: '#9ca3af' }"
                                :tick-line="false"
                                :axis-line="false"
                                :width="32"
                            />
                            <Tooltip
                                :content-style="{
                                    backgroundColor: 'rgba(17, 24, 39, 0.95)',
                                    border: 'none',
                                    borderRadius: '8px',
                                    fontSize: '11px',
                                    color: '#fff',
                                }"
                                :label-style="{ color: '#a5b4fc', fontWeight: 600 }"
                                :item-style="{ color: '#fff' }"
                            />
                            <Line
                                v-if="hasRir"
                                type="monotone"
                                data-key="rir"
                                name="RIR"
                                stroke="#10b981"
                                :stroke-width="2"
                                :dot="{ r: 3, fill: '#10b981' }"
                                :active-dot="{ r: 5, fill: '#10b981' }"
                                connect-nulls
                            />
                            <Line
                                v-if="hasRpe"
                                type="monotone"
                                data-key="rpe"
                                name="RPE"
                                stroke="#f59e0b"
                                :stroke-width="2"
                                :dot="{ r: 3, fill: '#f59e0b' }"
                                :active-dot="{ r: 5, fill: '#f59e0b' }"
                                connect-nulls
                            />
                        </LineChart>
                    </ResponsiveContainer>
                </div>
            </div>

            <!-- Distribución -->
            <div v-if="data.distribucion">
                <p class="mb-2 text-[10px] font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Distribución</p>
                <div class="space-y-1">
                    <div
                        v-for="d in distribution"
                        :key="d.label"
                        class="flex items-center gap-2 text-[11px]"
                    >
                        <span class="w-10 shrink-0 text-gray-500 dark:text-gray-400">{{ d.label }}</span>
                        <div class="h-2.5 flex-1 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700">
                            <div
                                class="h-full rounded-full transition-all"
                                :class="d.tipo === 'rir' ? 'bg-emerald-500' : 'bg-amber-500'"
                                :style="{ width: maxDistCount > 0 ? `${(d.count / maxDistCount) * 100}%` : '0%' }"
                            />
                        </div>
                        <span class="w-5 text-right tabular-nums text-gray-600 dark:text-gray-300">{{ d.count }}</span>
                    </div>
                </div>
            </div>

            <!-- Top ejercicios -->
            <div v-if="data.por_ejercicio.length">
                <p class="mb-2 text-[10px] font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Más trackeados</p>
                <ul class="space-y-1 text-xs text-gray-600 dark:text-gray-300">
                    <li
                        v-for="e in data.por_ejercicio"
                        :key="e.ejercicio"
                        class="flex items-center justify-between"
                    >
                        <span class="truncate">{{ e.ejercicio }}</span>
                        <span class="shrink-0 text-gray-400">{{ e.sets }} sets · {{ e.avg }}</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>
