<script setup>
import { onMounted, ref } from 'vue'
import axios from 'axios'
import { useToast } from '../composables/useToast'

const props = defineProps({
    currentDay: { type: String, default: '' },
})

const emit = defineEmits(['rescheduled'])

const toast = useToast()

const open = ref(false)
const loading = ref(false)
const saving = ref(false)
const days = ref([])
const selectedDay = ref('')
const reason = ref('missed_day')
const note = ref('')

const loadDays = async () => {
    loading.value = true
    try {
        const res = await axios.get('/api/user-rutina/available-days')
        days.value = res.data.days || []
        selectedDay.value = props.currentDay || res.data.current || ''
    } catch (e) {
        toast.apiError(e, 'No se pudieron cargar los días disponibles.')
    } finally {
        loading.value = false
    }
}

const openModal = () => {
    open.value = true
    if (days.value.length === 0) loadDays()
}

const closeModal = () => {
    open.value = false
    note.value = ''
}

const save = async () => {
    if (!selectedDay.value) {
        toast.error('Elegí un día')
        return
    }
    if (selectedDay.value === props.currentDay) {
        toast.error('Elegí un día distinto al actual')
        return
    }
    saving.value = true
    try {
        const res = await axios.post('/api/user-rutina/reschedule', {
            to_day: selectedDay.value,
            reason: reason.value,
            note: note.value || null,
        })
        toast.success(res.data.message || 'Reprogramado')
        emit('rescheduled', res.data.user_rutina)
        closeModal()
    } catch (e) {
        toast.apiError(e, 'No se pudo reprogramar.')
    } finally {
        saving.value = false
    }
}

onMounted(loadDays)
</script>

<template>
    <button
        type="button"
        @click="openModal"
        class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 transition-colors hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:border-indigo-700 dark:hover:bg-indigo-950/30 dark:hover:text-indigo-300"
    >
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
        Reprogramar día
    </button>

    <Teleport to="body">
        <Transition name="modal">
            <div
                v-if="open"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
                @click.self="closeModal"
            >
                <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl dark:bg-gray-800">
                    <header class="mb-4">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Reprogramar día</h3>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            ¿Querés registrar que hoy hiciste un día distinto al que tocaba?
                        </p>
                    </header>

                    <div v-if="loading" class="py-8 text-center text-sm text-gray-400">Cargando días…</div>

                    <div v-else class="space-y-4">
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-gray-600 dark:text-gray-300">Día que hiciste</label>
                            <div class="grid grid-cols-3 gap-2">
                                <button
                                    v-for="d in days"
                                    :key="d"
                                    type="button"
                                    :class="[
                                        'rounded-lg border px-3 py-2 text-sm font-semibold transition-colors',
                                        selectedDay === d
                                            ? 'border-indigo-500 bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300'
                                            : 'border-gray-200 bg-white text-gray-700 hover:border-gray-400 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200',
                                    ]"
                                    @click="selectedDay = d"
                                >{{ d }}</button>
                            </div>
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-semibold text-gray-600 dark:text-gray-300">Motivo</label>
                            <select
                                v-model="reason"
                                class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                            >
                                <option value="missed_day">Me perdí un día anterior</option>
                                <option value="manual">Prefiero cambiar el orden</option>
                                <option value="trainer">Me lo indicó mi trainer</option>
                                <option value="other">Otro</option>
                            </select>
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-semibold text-gray-600 dark:text-gray-300">Nota (opcional)</label>
                            <input
                                v-model="note"
                                type="text"
                                maxlength="255"
                                placeholder="Algo que quieras recordar…"
                                class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                            />
                        </div>
                    </div>

                    <footer class="mt-6 flex justify-end gap-2">
                        <button
                            type="button"
                            @click="closeModal"
                            class="rounded-lg px-4 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
                        >Cancelar</button>
                        <button
                            type="button"
                            @click="save"
                            :disabled="saving || !selectedDay"
                            class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
                        >{{ saving ? 'Guardando…' : 'Reprogramar' }}</button>
                    </footer>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
