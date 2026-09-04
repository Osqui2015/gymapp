<script setup>
import { computed, onMounted, ref } from 'vue'
import axios from 'axios'

const props = defineProps({
    userId: { type: Number, default: null },
})

const loading = ref(true)
const error = ref(null)
const data = ref(null)
const weekOffset = ref(0)  // 0 = esta semana, -1 = pasada, +1 = próxima

const fetchData = async () => {
    loading.value = true
    error.value = null
    try {
        const weekStart = new Date()
        weekStart.setDate(weekStart.getDate() - weekStart.getDay() + 1 + (weekOffset.value * 7))  // lunes
        const dateStr = weekStart.toISOString().split('T')[0]
        const params = { week_start: dateStr }
        if (props.userId) params.user_id = props.userId
        const res = await axios.get('/api/historial/week-summary', { params })
        data.value = res.data
    } catch (e) {
        error.value = e?.response?.data?.message || 'No se pudo cargar'
    } finally {
        loading.value = false
    }
}

onMounted(fetchData)

const prevWeek = () => { weekOffset.value--; fetchData() }
const nextWeek = () => { weekOffset.value++; fetchData() }
const thisWeek = () => { weekOffset.value = 0; fetchData() }

const weekLabel = computed(() => {
    if (!data.value) return ''
    const start = data.value.week_start.split('-').reverse().join('/')
    const end = data.value.week_end.split('-').reverse().join('/')
    return `${start} – ${end}`
})

const goToHistorial = () => {
    window.location.href = '/historial'
}
</script>

<template>
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <header class="mb-3 flex items-center justify-between gap-2">
            <div>
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Esta semana</h3>
                <p class="text-[11px] text-gray-500 dark:text-gray-400">{{ weekLabel }}</p>
            </div>
            <div class="flex items-center gap-1">
                <button type="button" @click="prevWeek" class="rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700" aria-label="Semana anterior">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                </button>
                <button v-if="weekOffset !== 0" type="button" @click="thisWeek" class="rounded px-2 py-0.5 text-[10px] font-semibold text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-950/30">Hoy</button>
                <button type="button" @click="nextWeek" class="rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700" aria-label="Semana siguiente">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                </button>
            </div>
        </header>

        <div v-if="loading" class="py-6 text-center text-sm text-gray-400">Cargando…</div>
        <div v-else-if="error" class="py-4 text-center text-sm text-red-500">{{ error }}</div>
        <div v-else-if="data" class="space-y-3">
            <!-- Grid 7 días -->
            <div class="grid grid-cols-7 gap-1.5">
                <div
                    v-for="d in data.days"
                    :key="d.date"
                    :class="[
                        'rounded-lg p-1.5 text-center transition-all',
                        d.completado
                            ? (d.es_hoy ? 'bg-indigo-100 ring-2 ring-indigo-400 dark:bg-indigo-950/50' : 'bg-emerald-50 dark:bg-emerald-950/30')
                            : (d.es_hoy ? 'bg-gray-100 ring-1 ring-gray-300 dark:bg-gray-700' : 'bg-gray-50 dark:bg-gray-900/40')
                    ]"
                    :title="d.completado ? `${d.sets} sets · ${d.ejercicios.join(', ')}` : 'Descanso'"
                >
                    <p class="text-[9px] font-semibold uppercase tracking-wider" :class="d.es_hoy ? 'text-indigo-700 dark:text-indigo-300' : 'text-gray-500 dark:text-gray-400'">
                        {{ d.dia_semana_corto }}
                    </p>
                    <p class="mt-0.5 text-base font-bold" :class="d.es_hoy ? 'text-indigo-900 dark:text-indigo-100' : 'text-gray-700 dark:text-gray-200'">
                        {{ d.date.slice(-2) }}
                    </p>
                    <!-- dot -->
                    <div class="mt-1 flex justify-center">
                        <span
                            v-if="d.completado"
                            class="h-2 w-2 rounded-full"
                            :class="d.sets >= 20 ? 'bg-emerald-500' : d.sets >= 10 ? 'bg-indigo-500' : 'bg-amber-500'"
                        />
                        <span v-else class="h-1.5 w-1.5 rounded-full bg-gray-300 dark:bg-gray-600" />
                    </div>
                    <p v-if="d.completado" class="mt-0.5 text-[9px] font-semibold text-gray-600 dark:text-gray-300">
                        {{ d.sets }}
                    </p>
                </div>
            </div>

            <!-- Resumen -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 border-t border-gray-100 pt-3 text-center dark:border-gray-700">
                <div class="min-w-0">
                    <p class="text-[10px] uppercase tracking-wider text-gray-500 dark:text-gray-400">Sets</p>
                    <p class="text-sm font-bold text-gray-700 dark:text-gray-200">{{ data.totals.sets }}</p>
                </div>
                <div class="min-w-0">
                    <p class="text-[10px] uppercase tracking-wider text-gray-500 dark:text-gray-400">Días</p>
                    <p class="text-sm font-bold text-gray-700 dark:text-gray-200">{{ data.totals.dias_entrenados }}/7</p>
                </div>
                <div class="min-w-0">
                    <p class="text-[10px] uppercase tracking-wider text-gray-500 dark:text-gray-400">Racha</p>
                    <p class="text-sm font-bold text-gray-700 dark:text-gray-200">🔥 {{ data.streak }}</p>
                </div>
            </div>

            <button
                type="button"
                @click="goToHistorial"
                class="block w-full rounded-lg bg-gray-50 px-3 py-2 text-center text-xs font-semibold text-gray-600 transition-colors hover:bg-gray-100 dark:bg-gray-900/40 dark:text-gray-300 dark:hover:bg-gray-900"
            >
                Ver detalle →
            </button>
        </div>
    </div>
</template>
