<script setup>
import { computed, onMounted, ref } from 'vue'
import axios from 'axios'

const data = ref(null)
const loading = ref(true)

const fetchData = async () => {
    loading.value = true
    try {
        const res = await axios.get('/api/dashboard/today')
        data.value = res.data
    } catch (err) {
        // Silenciar — el dashboard igual funciona
        data.value = null
    } finally {
        loading.value = false
    }
}

onMounted(fetchData)

const greeting = computed(() => {
    if (!data.value) return ''
    return `${data.value.hoy.saludo}${data.value.hoy.nombre ? ', ' + data.value.hoy.nombre.split(' ')[0] : ''}`
})

const quickLabel = computed(() => {
    const q = data.value?.quick
    if (q === 'empezar') return 'Empezar entrenamiento'
    if (q === 'continuar') return 'Continuar'
    if (q === 'descanso') return 'Día de descanso'
    if (q === 'nueva_rutina') return 'Elegir rutina'
    return 'Empezar'
})

const quickColor = computed(() => {
    const q = data.value?.quick
    if (q === 'continuar') return 'bg-amber-600 hover:bg-amber-700'
    if (q === 'descanso') return 'bg-emerald-600 hover:bg-emerald-700'
    if (q === 'nueva_rutina') return 'bg-indigo-600 hover:bg-indigo-700'
    return 'bg-indigo-600 hover:bg-indigo-700'
})

const startWorkout = () => {
    const q = data.value?.quick
    if (q === 'nueva_rutina') {
        window.location.href = '/rutinas'
    } else {
        // scrollear al day-selector del dashboard
        const el = document.querySelector('[data-tour="series-list"]')
        if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' })
    }
}

const goHistorial = () => {
    window.location.href = '/historial'
}
</script>

<template>
    <div
        v-if="!loading && data"
        class="relative overflow-hidden rounded-2xl border border-indigo-100 bg-gradient-to-br from-indigo-600 via-indigo-700 to-purple-700 p-5 text-white shadow-md dark:border-indigo-900"
    >
        <div class="absolute -right-6 -top-6 h-32 w-32 rounded-full bg-white/10 blur-2xl"></div>
        <div class="absolute -bottom-6 -left-6 h-32 w-32 rounded-full bg-purple-500/30 blur-2xl"></div>

        <div class="relative flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0 flex-1">
                <p class="text-xs font-semibold uppercase tracking-wider text-indigo-200">{{ data.hoy.dia_semana_es }}</p>
                <h2 class="mt-0.5 truncate text-2xl font-bold">{{ greeting }}</h2>

                <div v-if="data.rutina" class="mt-2 flex flex-wrap items-center gap-2 text-sm text-indigo-100">
                    <span class="inline-flex items-center rounded-full bg-white/15 px-2.5 py-0.5 text-xs font-semibold backdrop-blur">
                        {{ data.rutina.nombre }}
                    </span>
                    <span class="inline-flex items-center rounded-full bg-amber-400/90 px-2.5 py-0.5 text-xs font-bold text-amber-950">
                        {{ data.rutina.dia_actual }}
                    </span>
                    <span v-if="data.stats.streak > 0" class="inline-flex items-center gap-1 text-xs font-semibold">
                        <span>🔥</span> {{ data.stats.streak }} día{{ data.stats.streak > 1 ? 's' : '' }}
                    </span>
                </div>
                <p v-else class="mt-2 text-sm text-indigo-100">
                    No tenés una rutina activa. Empezá eligiendo una.
                </p>
            </div>

            <div class="flex shrink-0 flex-col gap-2 sm:items-end">
                <button
                    type="button"
                    @click="startWorkout"
                    :class="['inline-flex items-center justify-center gap-2 rounded-xl px-5 py-2.5 text-sm font-bold text-white shadow-lg transition-colors', quickColor]"
                >
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M6.4 4.6A1 1 0 005 6v8a1 1 0 001.6.8l5-4a1 1 0 000-1.6l-5-4a1 1 0 00-.2-.2zM14 5a1 1 0 10-2 0v10a1 1 0 102 0V5z" />
                    </svg>
                    {{ quickLabel }}
                </button>
                <button
                    type="button"
                    @click="goHistorial"
                    class="rounded-lg px-3 py-1.5 text-xs font-semibold text-indigo-100 transition-colors hover:bg-white/10"
                >
                    Ver historial →
                </button>
            </div>
        </div>

        <div v-if="data.rutina && data.stats.total_sets_30d > 0" class="relative mt-4 grid grid-cols-3 gap-3 border-t border-white/15 pt-3 text-center">
            <div>
                <p class="text-[10px] uppercase tracking-wider text-indigo-200">Sets 30d</p>
                <p class="mt-0.5 text-lg font-bold">{{ data.stats.total_sets_30d }}</p>
            </div>
            <div>
                <p class="text-[10px] uppercase tracking-wider text-indigo-200">Último</p>
                <p class="mt-0.5 text-lg font-bold">
                    {{ data.stats.days_since_last_workout === 0 ? 'Hoy' : (data.stats.days_since_last_workout === 1 ? 'Ayer' : `Hace ${data.stats.days_since_last_workout}d`) }}
                </p>
            </div>
            <div>
                <p class="text-[10px] uppercase tracking-wider text-indigo-200">Racha</p>
                <p class="mt-0.5 text-lg font-bold">🔥 {{ data.stats.streak }}</p>
            </div>
        </div>
    </div>
</template>
