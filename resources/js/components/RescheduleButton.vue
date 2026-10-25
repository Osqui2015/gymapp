<script setup>
import { onMounted, ref } from 'vue';
import axios from 'axios';
import { useToast } from '../composables/useToast';

const props = defineProps({
    currentDay: { type: String, default: '' },
});

const emit = defineEmits(['rescheduled']);

const toast = useToast();

const open = ref(false);
const loading = ref(false);
const saving = ref(false);
const days = ref([]);
const selectedDay = ref('');
const reason = ref('missed_day');
const note = ref('');

const loadDays = async () => {
    loading.value = true;
    try {
        const res = await axios.get('/api/user-rutina/available-days');
        days.value = res.data.days || [];
        selectedDay.value = props.currentDay || res.data.current || '';
    } catch (e) {
        toast.apiError(e, 'No se pudieron cargar los días disponibles.');
    } finally {
        loading.value = false;
    }
};

const openModal = () => {
    open.value = true;
    if (days.value.length === 0) loadDays();
};

const closeModal = () => {
    open.value = false;
    note.value = '';
};

const save = async () => {
    if (!selectedDay.value) {
        toast.error('Elegí un día');
        return;
    }
    if (selectedDay.value === props.currentDay) {
        toast.error('Elegí un día distinto al actual');
        return;
    }
    saving.value = true;
    try {
        const res = await axios.post('/api/user-rutina/reschedule', {
            to_day: selectedDay.value,
            reason: reason.value,
            note: note.value || null,
        });
        toast.success(res.data.message || 'Reprogramado');
        emit('rescheduled', res.data.user_rutina);
        closeModal();
    } catch (e) {
        toast.apiError(e, 'No se pudo reprogramar.');
    } finally {
        saving.value = false;
    }
};

onMounted(loadDays);
</script>

<template>
    <button
        type="button"
        @click="openModal"
        class="h-9 px-space-md rounded-lg bg-surface-container-highest hover:bg-secondary-container hover:text-on-secondary-container text-on-surface font-label-md text-label-md flex items-center gap-1.5 transition-all cursor-pointer"
    >
        <span class="material-symbols-outlined text-[16px]">edit_calendar</span>
        <span>Reprogramar día</span>
    </button>

    <Teleport to="body">
        <Transition name="modal">
            <div
                v-if="open"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm p-4"
                @click.self="closeModal"
            >
                <div class="w-full max-w-md rounded-2xl bg-surface-container-low p-6 shadow-2xl border border-outline-variant/20 text-on-surface">
                    <header class="mb-4">
                        <h3 class="font-headline-md text-headline-md text-on-surface">
                            Reprogramar día
                        </h3>
                        <p class="mt-1 text-body-sm text-on-surface-variant">
                            ¿Querés registrar que hoy hiciste un día distinto al que tocaba?
                        </p>
                    </header>

                    <div v-if="loading" class="py-8 text-center text-body-sm text-outline">
                        Cargando días…
                    </div>

                    <div v-else class="space-y-4">
                        <div>
                            <label class="mb-1.5 block font-label-sm text-label-sm uppercase tracking-wider text-outline">
                                Día que hiciste
                            </label>
                            <div class="grid grid-cols-3 gap-2">
                                <button
                                    v-for="d in days"
                                    :key="d"
                                    type="button"
                                    :class="[
                                        'rounded-xl px-3 py-2 text-label-md font-label-md transition-colors cursor-pointer',
                                        selectedDay === d
                                            ? 'bg-secondary-container text-on-secondary-container shadow-sm'
                                            : 'bg-surface-container text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high',
                                    ]"
                                    @click="selectedDay = d"
                                >
                                    {{ d }}
                                </button>
                            </div>
                        </div>

                        <div>
                            <label class="mb-1.5 block font-label-sm text-label-sm uppercase tracking-wider text-outline">
                                Motivo
                            </label>
                            <select
                                v-model="reason"
                                class="w-full h-11 rounded-xl bg-surface-container-lowest px-3 text-body-md text-on-surface focus:outline-none focus:ring-2 focus:ring-secondary border-0"
                            >
                                <option value="missed_day">Me perdí un día anterior</option>
                                <option value="manual">Prefiero cambiar el orden</option>
                                <option value="trainer">Me lo indicó mi trainer</option>
                                <option value="other">Otro</option>
                            </select>
                        </div>

                        <div>
                            <label class="mb-1.5 block font-label-sm text-label-sm uppercase tracking-wider text-outline">
                                Nota (opcional)
                            </label>
                            <input
                                v-model="note"
                                type="text"
                                maxlength="255"
                                placeholder="Algo que quieras recordar…"
                                class="w-full h-11 rounded-xl bg-surface-container-lowest px-3 text-body-md text-on-surface placeholder:text-outline/60 focus:outline-none focus:ring-2 focus:ring-secondary border-0"
                            />
                        </div>
                    </div>

                    <footer class="mt-6 flex justify-end gap-2">
                        <button
                            type="button"
                            @click="closeModal"
                            class="rounded-xl px-4 py-2 text-label-md font-label-md text-on-surface-variant hover:bg-surface-container hover:text-on-surface transition-colors cursor-pointer"
                        >
                            Cancelar
                        </button>
                        <button
                            type="button"
                            @click="save"
                            :disabled="saving || !selectedDay"
                            class="rounded-xl bg-secondary-container px-4 py-2 text-label-md font-label-md text-on-secondary-container transition-colors hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-50 cursor-pointer"
                        >
                            {{ saving ? 'Guardando…' : 'Reprogramar' }}
                        </button>
                    </footer>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
