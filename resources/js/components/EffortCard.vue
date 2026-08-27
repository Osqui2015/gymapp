<script setup>
import { computed, onMounted, ref } from 'vue'
import axios from 'axios'

const props = defineProps({
    userId: { type: Number, default: null },
})

const loading = ref(false)
const error = ref(null)
const data = ref(null)

const fetchData = async () => {
    loading.value = true
    error.value = null
    try {
        const res = await axios.get('/api/stats/esfuerzo', {
            params: props.userId ? { user_id: props.userId } : {},
        })
        data.value = res.data
    } catch (e) {
        error.value = e?.response?.data?.message || 'No se pudo cargar el esfuerzo'
    } finally {
        loading.value = false
    }
}

onMounted(fetchData)

const coverage = computed(() => {
    if (!data.value || data.value.total_sets === 0) return 0
    return Math.round((data.value.sets_with_esfuerzo / data.value.total_sets) * 100)
})

// Distribución como array plano {label, count, tipo}
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

const tendencia = computed(() => {
    if (!data.value?.tendencia_30d?.length) return null
    const rows = data.value.tendencia_30d
    // Combinar RIR + RPE en una sola serie usando escala normalizada (RIR invierte)
    // Para sparkline simple, usamos el avg diario de cualquier tipo presente.
    const points = rows
        .map((r) => {
            if (r.rir !== null) return { fecha: r.fecha, value: r.rir, kind: 'rir' }
            if (r.rpe !== null) return { fecha: r.fecha, value: r.rpe, kind: 'rpe' }
            return null
        })
        .filter(Boolean)
    return points
})

const sparkPath = computed(() => {
    if (!tendencia.value || tendencia.value.length < 2) return ''
    const values = tendencia.value.map((p) => p.value)
    const min = Math.min(...values)
    const max = Math.max(...values)
    const range = max - min || 1
    const w = 120
    const h = 28
    const stepX = w / (tendencia.value.length - 1)
    return tendencia.value
        .map((p, i) => {
            const x = i * stepX
            const norm = (p.value - min) / range
            // RIR: lower value = más esfuerzo, invertir
            // RPE: higher value = más esfuerzo
            const y = p.kind === 'rir' ? h - norm * h : h - norm * h
            return `${i === 0 ? 'M' : 'L'} ${x.toFixed(1)} ${y.toFixed(1)}`
        })
        .join(' ')
})
</script>

<template>
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <header class="mb-3 flex items-center justify-between">
            <div>
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Esfuerzo (RIR / RPE)</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">Últimos 30 días</p>
            </div>
            <button
                v-if="!loading"
                type="button"
                class="rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700"
                aria-label="Recargar"
                @click="fetchData"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
            </button>
        </header>

        <div v-if="loading" class="py-8 text-center text-sm text-gray-400">Cargando…</div>

        <div v-else-if="error" class="py-6 text-center text-sm text-red-500">{{ error }}</div>

        <div v-else-if="!data || data.sets_with_esfuerzo === 0" class="py-6 text-center">
            <p class="text-sm text-gray-500 dark:text-gray-400">Todavía no registraste esfuerzo en tus sets.</p>
            <p class="mt-1 text-xs text-gray-400">Empezá marcando RIR o RPE al guardar cada set.</p>
        </div>

        <div v-else class="space-y-4">
            <!-- Promedio + cobertura -->
            <div class="flex items-end justify-between gap-4">
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Promedio</p>
                    <p class="text-3xl font-bold text-indigo-600 dark:text-indigo-400">{{ avgLabel }}</p>
                </div>
                <div class="text-right">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Cobertura</p>
                    <p class="text-lg font-semibold text-gray-700 dark:text-gray-200">{{ coverage }}%</p>
                    <p class="text-[10px] text-gray-400">{{ data.sets_with_esfuerzo }} / {{ data.total_sets }} sets</p>
                </div>
            </div>

            <!-- Sparkline -->
            <div v-if="sparkPath" class="rounded-md bg-gray-50 p-2 dark:bg-gray-900/50">
                <svg :viewBox="`0 0 120 28`" class="h-7 w-full" preserveAspectRatio="none" aria-hidden="true">
                    <path :d="sparkPath" fill="none" stroke="currentColor" stroke-width="1.5" class="text-indigo-500" />
                </svg>
            </div>

            <!-- Distribución -->
            <div>
                <p class="mb-2 text-xs font-medium text-gray-600 dark:text-gray-300">Distribución</p>
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
                <p class="mb-2 text-xs font-medium text-gray-600 dark:text-gray-300">Más trackeados</p>
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
