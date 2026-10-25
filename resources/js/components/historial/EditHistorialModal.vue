<script setup>
import { computed, ref, watch } from 'vue';
import axios from 'axios';
import { useToast } from '../../composables/useToast';

const props = defineProps({
    open: { type: Boolean, default: false },
    serie: { type: Object, default: null },
});

const emit = defineEmits(['close', 'saved', 'deleted']);

const toast = useToast();

const form = ref({
    peso: '',
    repeticiones: '',
    rir: null,
    notas: '',
    completed: true,
});
const saving = ref(false);
const deleting = ref(false);
const error = ref('');

watch(
    () => props.serie,
    (s) => {
        if (!s) return;
        form.value = {
            peso: s.peso ?? 0,
            repeticiones: s.repeticiones ?? 0,
            rir: s.rir === undefined ? null : s.rir,
            notas: s.notas || '',
            completed: s.completed !== false,
        };
        error.value = '';
    },
    { immediate: true }
);

const isEditable = computed(() => {
    if (!props.serie) return false;
    return props.serie.editable !== false;
});

const stepWeight = (delta) => {
    if (!isEditable.value) return;
    const current = parseFloat(form.value.peso) || 0;
    form.value.peso = Math.max(0, Math.round((current + delta) * 100) / 100);
};

const stepReps = (delta) => {
    if (!isEditable.value) return;
    const current = parseInt(form.value.repeticiones, 10) || 0;
    form.value.repeticiones = Math.max(0, current + delta);
};

const validate = () => {
    const peso = Number(form.value.peso);
    const reps = Number(form.value.repeticiones);
    if (Number.isNaN(peso) || peso < 0 || peso > 1000) {
        error.value = 'El peso debe estar entre 0 y 1000 kg.';
        return false;
    }
    if (!Number.isInteger(reps) || reps < 0 || reps > 500) {
        error.value = 'Las repeticiones deben ser un entero entre 0 y 500.';
        return false;
    }
    if (
        form.value.rir !== null &&
        form.value.rir !== '' &&
        (Number(form.value.rir) < 0 || Number(form.value.rir) > 10)
    ) {
        error.value = 'El RIR debe estar entre 0 y 10.';
        return false;
    }
    error.value = '';
    return true;
};

const save = async () => {
    if (!props.serie || !validate()) return;
    saving.value = true;
    try {
        const payload = {
            peso: Number(form.value.peso),
            repeticiones: Number(form.value.repeticiones),
            rir:
                form.value.rir === '' || form.value.rir === null
                    ? null
                    : Number(form.value.rir),
            notas: form.value.notas ? form.value.notas.trim() : null,
            completed: Boolean(form.value.completed),
        };
        const res = await axios.patch(`/api/historial/${props.serie.id}`, payload);
        toast.success('Serie actualizada.');
        emit('saved', res.data.data || { ...props.serie, ...payload });
        emit('close');
    } catch (e) {
        toast.apiError(e, 'No se pudo actualizar la serie.');
    } finally {
        saving.value = false;
    }
};

const remove = async () => {
    if (!props.serie) return;
    const ok = await toast.confirm(
        `¿Seguro que querés eliminar la serie #${props.serie.serie} de ${props.serie.nombre_ejercicio || 'este ejercicio'}? Esta acción no se puede deshacer.`,
        {
            title: 'Eliminar serie',
            confirmLabel: 'Sí, eliminar',
            cancelLabel: 'Cancelar',
            danger: true,
        }
    );
    if (!ok) return;

    deleting.value = true;
    try {
        await axios.delete(`/api/historial/${props.serie.id}`);
        toast.success('Serie eliminada.');
        emit('deleted', props.serie);
        emit('close');
    } catch (e) {
        toast.apiError(e, 'No se pudo eliminar la serie.');
    } finally {
        deleting.value = false;
    }
};
</script>

<template>
    <Teleport to="body">
        <Transition name="fade">
            <div
                v-if="open && serie"
                class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/75 p-0 sm:p-4 backdrop-blur-sm"
                role="dialog"
                aria-modal="true"
                aria-labelledby="edit-historial-title"
                @click.self="emit('close')"
            >
                <div
                    class="w-full max-w-md rounded-t-3xl sm:rounded-2xl bg-[#131924] p-6 pb-8 sm:pb-6 shadow-2xl border-t sm:border border-[#232D42] text-slate-200 max-h-[90vh] overflow-y-auto"
                >
                    <!-- Mobile Drag Handle -->
                    <div class="sm:hidden flex justify-center -mt-2 mb-3">
                        <div class="w-10 h-1.5 rounded-full bg-[#232D42]"></div>
                    </div>

                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                                <p class="text-[11px] font-bold uppercase tracking-widest text-indigo-400">
                                    Editar Serie #{{ serie.serie }}
                                </p>
                            </div>
                            <h3
                                id="edit-historial-title"
                                class="text-lg font-extrabold text-white mt-0.5"
                            >
                                {{ serie.nombre_ejercicio || 'Ejercicio' }}
                            </h3>
                            <p v-if="serie.fecha" class="text-xs text-slate-400 font-mono mt-0.5">
                                {{ serie.fecha }}
                            </p>
                        </div>
                        <button
                            type="button"
                            class="rounded-lg p-1.5 text-slate-400 hover:bg-[#18202F] hover:text-white transition-colors cursor-pointer"
                            aria-label="Cerrar"
                            @click="emit('close')"
                        >
                            <span class="material-symbols-outlined text-[20px]">close</span>
                        </button>
                    </div>

                    <div
                        v-if="!isEditable"
                        class="mt-4 rounded-xl border border-amber-500/30 bg-amber-500/10 p-3 text-xs text-amber-300"
                    >
                        Esta serie tiene más de 7 días y ya no puede modificarse para preservar tu historial.
                    </div>

                    <form class="mt-4 space-y-4" @submit.prevent="save">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <!-- Peso con botones +/- -->
                            <div class="bg-[#18202F]/60 border border-[#232D42] rounded-xl p-3">
                                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1.5">
                                    Peso (kg)
                                </label>
                                <div class="flex items-center gap-1.5">
                                    <button
                                        type="button"
                                        :disabled="!isEditable"
                                        @click="stepWeight(-2.5)"
                                        class="h-10 px-2.5 rounded-lg bg-[#101521] border border-[#232D42] text-xs font-mono font-bold text-slate-300 hover:text-white hover:border-indigo-500/50 active:scale-95 transition-all cursor-pointer disabled:opacity-40"
                                    >
                                        -2.5
                                    </button>
                                    <input
                                        v-model="form.peso"
                                        type="number"
                                        inputmode="decimal"
                                        step="0.25"
                                        min="0"
                                        max="1000"
                                        :disabled="!isEditable"
                                        class="w-full h-10 rounded-lg border border-[#232D42] bg-[#101521] px-2 text-center font-mono text-base font-bold text-white focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 disabled:opacity-50"
                                    />
                                    <button
                                        type="button"
                                        :disabled="!isEditable"
                                        @click="stepWeight(2.5)"
                                        class="h-10 px-2.5 rounded-lg bg-indigo-600/20 border border-indigo-500/40 text-xs font-mono font-bold text-indigo-300 hover:bg-indigo-600/30 active:scale-95 transition-all cursor-pointer disabled:opacity-40"
                                    >
                                        +2.5
                                    </button>
                                </div>
                            </div>

                            <!-- Repeticiones con botones +/- -->
                            <div class="bg-[#18202F]/60 border border-[#232D42] rounded-xl p-3">
                                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1.5">
                                    Repeticiones
                                </label>
                                <div class="flex items-center gap-1.5">
                                    <button
                                        type="button"
                                        :disabled="!isEditable"
                                        @click="stepReps(-1)"
                                        class="h-10 px-3 rounded-lg bg-[#101521] border border-[#232D42] text-xs font-mono font-bold text-slate-300 hover:text-white hover:border-indigo-500/50 active:scale-95 transition-all cursor-pointer disabled:opacity-40"
                                    >
                                        -1
                                    </button>
                                    <input
                                        v-model="form.repeticiones"
                                        type="number"
                                        inputmode="numeric"
                                        step="1"
                                        min="0"
                                        max="500"
                                        :disabled="!isEditable"
                                        class="w-full h-10 rounded-lg border border-[#232D42] bg-[#101521] px-2 text-center font-mono text-base font-bold text-white focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 disabled:opacity-50"
                                    />
                                    <button
                                        type="button"
                                        :disabled="!isEditable"
                                        @click="stepReps(1)"
                                        class="h-10 px-3 rounded-lg bg-emerald-500/20 border border-emerald-500/40 text-xs font-mono font-bold text-emerald-300 hover:bg-emerald-500/30 active:scale-95 transition-all cursor-pointer disabled:opacity-40"
                                    >
                                        +1
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400">
                                RIR (repeticiones en recámara, 0-10)
                            </label>
                            <div class="mt-1.5 flex items-center gap-1.5 flex-wrap">
                                <button
                                    v-for="r in [0, 1, 2, 3, 4, 5]"
                                    :key="r"
                                    type="button"
                                    :disabled="!isEditable"
                                    class="min-w-[38px] h-9 rounded-lg border text-xs font-mono font-bold transition-colors cursor-pointer"
                                    :class="
                                        Number(form.rir) === r && form.rir !== null && form.rir !== ''
                                            ? 'border-indigo-500 bg-indigo-600 text-white shadow-sm shadow-indigo-600/30'
                                            : 'border-[#232D42] bg-[#18202F] text-slate-300 hover:text-white'
                                    "
                                    @click="form.rir = r"
                                >
                                    {{ r }}
                                </button>
                                <button
                                    type="button"
                                    :disabled="!isEditable"
                                    class="h-9 rounded-lg border border-[#232D42] bg-[#101521] px-3 text-xs font-semibold text-slate-400 hover:text-white cursor-pointer"
                                    @click="form.rir = null"
                                >
                                    Sin RIR
                                </button>
                            </div>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400">
                                Notas de la serie
                            </label>
                            <textarea
                                v-model="form.notas"
                                rows="2"
                                maxlength="500"
                                :disabled="!isEditable"
                                placeholder="Molestia en el hombro, buen bombeo, agarré más cerrado…"
                                class="mt-1.5 w-full rounded-xl border border-[#232D42] bg-[#101521] px-3 py-2 text-xs text-white placeholder:text-slate-500 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 disabled:opacity-50"
                            />
                        </div>

                        <label class="flex items-center gap-2.5 text-xs text-slate-300 cursor-pointer select-none">
                            <input
                                v-model="form.completed"
                                type="checkbox"
                                :disabled="!isEditable"
                                class="h-4 w-4 rounded border-[#232D42] bg-[#101521] text-indigo-600 focus:ring-indigo-500 accent-indigo-600"
                            />
                            Serie completada efectivamente
                        </label>

                        <p
                            v-if="error"
                            class="rounded-lg bg-rose-500/10 border border-rose-500/30 px-3 py-2 text-xs text-rose-300"
                        >
                            {{ error }}
                        </p>

                        <div class="flex items-center justify-between gap-2 pt-3 border-t border-[#232D42]">
                            <button
                                type="button"
                                :disabled="!isEditable || deleting || saving"
                                class="inline-flex items-center gap-1 rounded-xl border border-rose-500/30 bg-rose-500/10 px-3 py-2 text-xs font-semibold text-rose-300 hover:bg-rose-500/20 disabled:opacity-40 cursor-pointer"
                                @click="remove"
                            >
                                <span class="material-symbols-outlined text-[15px]">delete</span>
                                <span>{{ deleting ? 'Eliminando…' : 'Eliminar' }}</span>
                            </button>

                            <div class="flex gap-2">
                                <button
                                    type="button"
                                    class="rounded-xl border border-[#232D42] bg-[#18202F] px-4 py-2 text-xs font-semibold text-slate-300 hover:text-white cursor-pointer"
                                    @click="emit('close')"
                                >
                                    Cancelar
                                </button>
                                <button
                                    v-if="isEditable"
                                    type="submit"
                                    :disabled="saving || deleting"
                                    class="rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white hover:bg-indigo-500 shadow-lg shadow-indigo-600/25 disabled:opacity-50 cursor-pointer"
                                >
                                    {{ saving ? 'Guardando…' : 'Guardar cambios' }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.15s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
