<template>
    <Teleport to="body">
        <Transition name="edit-modal">
            <div
                v-if="open"
                class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4 bg-black/50 backdrop-blur-sm"
                role="dialog"
                aria-modal="true"
                aria-labelledby="edit-title"
                @click.self="$emit('close')"
            >
                <div
                    class="w-full sm:max-w-md bg-white dark:bg-gray-900 rounded-t-2xl sm:rounded-2xl shadow-2xl overflow-hidden max-h-[92vh] flex flex-col"
                >
                    <header
                        class="px-5 py-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between gap-2 shrink-0"
                    >
                        <div class="min-w-0">
                            <h2
                                id="edit-title"
                                class="text-base font-bold text-gray-900 dark:text-white truncate"
                            >
                                Editar serie
                            </h2>
                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                {{ serie?.ejercicio_nombre }} · {{ serie?.fecha }}
                            </p>
                        </div>
                        <button
                            type="button"
                            @click="$emit('close')"
                            class="p-2 rounded-full text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800"
                            aria-label="Cerrar"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    </header>

                    <form
                        v-if="form"
                        @submit.prevent="guardar"
                        class="flex-1 overflow-y-auto p-5 space-y-4"
                    >
                        <!-- Peso -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">
                                Peso (kg)
                            </label>
                            <input
                                v-model.number="form.peso"
                                type="number"
                                inputmode="decimal"
                                step="0.5"
                                min="0"
                                class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-700 rounded-xl dark:bg-gray-800 dark:text-white text-base font-semibold focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            />
                        </div>

                        <!-- Reps -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">
                                Repeticiones realizadas
                            </label>
                            <input
                                v-model.number="form.reps_realizadas"
                                type="number"
                                inputmode="numeric"
                                min="0"
                                class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-700 rounded-xl dark:bg-gray-800 dark:text-white text-base font-semibold focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            />
                        </div>

                        <!-- Tipo de serie -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">
                                Tipo de serie
                            </label>
                            <div class="grid grid-cols-4 gap-1.5">
                                <button
                                    v-for="tipo in tiposSerie"
                                    :key="tipo.id"
                                    type="button"
                                    @click="form.tipo_serie = tipo.id"
                                    :class="[
                                        'py-2 text-xs font-bold rounded-lg transition-all',
                                        form.tipo_serie === tipo.id
                                            ? tipo.activeClass
                                            : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300',
                                    ]"
                                >
                                    {{ tipo.label }}
                                </button>
                            </div>
                        </div>

                        <!-- Completado -->
                        <label class="flex items-center gap-2 cursor-pointer select-none">
                            <input
                                v-model="form.completado"
                                type="checkbox"
                                class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            />
                            <span class="text-sm text-gray-700 dark:text-gray-300">Serie completada</span>
                        </label>

                        <!-- Esfuerzo -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">
                                Esfuerzo percibido (opcional)
                            </label>
                            <div class="flex items-center gap-2 mb-1.5">
                                <button
                                    type="button"
                                    @click="form.esfuerzo_tipo = 'rir'"
                                    :class="
                                        form.esfuerzo_tipo === 'rir'
                                            ? 'bg-emerald-500 text-white'
                                            : 'bg-gray-100 dark:bg-gray-800 text-gray-500'
                                    "
                                    class="px-3 py-1 text-xs font-bold rounded-md"
                                >
                                    RIR
                                </button>
                                <button
                                    type="button"
                                    @click="form.esfuerzo_tipo = 'rpe'"
                                    :class="
                                        form.esfuerzo_tipo === 'rpe'
                                            ? 'bg-amber-500 text-white'
                                            : 'bg-gray-100 dark:bg-gray-800 text-gray-500'
                                    "
                                    class="px-3 py-1 text-xs font-bold rounded-md"
                                >
                                    RPE
                                </button>
                            </div>
                            <div class="flex flex-wrap gap-1">
                                <button
                                    v-for="val in esfuerzoOptions"
                                    :key="val"
                                    type="button"
                                    @click="form.esfuerzo_valor = form.esfuerzo_valor === val ? null : val"
                                    :class="[
                                        'w-9 h-9 rounded-lg font-bold text-xs transition-all',
                                        form.esfuerzo_valor === val
                                            ? form.esfuerzo_tipo === 'rir'
                                                ? 'bg-emerald-500 text-white'
                                                : 'bg-amber-500 text-white'
                                            : 'bg-gray-100 dark:bg-gray-800 text-gray-500',
                                    ]"
                                >
                                    {{ val }}
                                </button>
                            </div>
                        </div>

                        <!-- Nota libre -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">
                                Nota (opcional)
                            </label>
                            <textarea
                                v-model="form.nota_user"
                                rows="2"
                                maxlength="500"
                                placeholder="Cómo se sintió, dolor, comentario..."
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-xl dark:bg-gray-800 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            ></textarea>
                        </div>

                        <!-- Acciones -->
                        <div class="flex items-center gap-2 pt-2">
                            <button
                                v-if="puedeEliminar"
                                type="button"
                                @click="eliminar"
                                class="px-3 py-2 text-xs font-semibold text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-lg transition-colors"
                            >
                                🗑 Eliminar
                            </button>
                            <div class="flex-1"></div>
                            <button
                                type="button"
                                @click="$emit('close')"
                                class="px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg"
                            >
                                Cancelar
                            </button>
                            <button
                                type="submit"
                                :disabled="guardando"
                                class="px-4 py-2 text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 rounded-lg shadow-md transition-colors"
                            >
                                {{ guardando ? 'Guardando…' : 'Guardar' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useToast } from '../../composables/useToast';
import { useUndoable } from '../../composables/useUndoable';
import axios from 'axios';

const props = defineProps({
    open: { type: Boolean, default: false },
    serie: { type: Object, default: null },
    puedeEliminar: { type: Boolean, default: true },
});

const emit = defineEmits(['close', 'saved', 'deleted']);

const toast = useToast();
const guardando = ref(false);

const tiposSerie = [
    { id: 'efectiva', label: 'Efectiva', activeClass: 'bg-emerald-500 text-white' },
    { id: 'calentamiento', label: 'Calent.', activeClass: 'bg-indigo-500 text-white' },
    { id: 'dropset', label: 'Drop', activeClass: 'bg-amber-500 text-white' },
    { id: 'al_fallo', label: 'Fallo', activeClass: 'bg-rose-500 text-white' },
];

const form = ref(null);

const esfuerzoOptions = computed(() => {
    if (!form.value) return [];
    return form.value.esfuerzo_tipo === 'rir' ? [0, 1, 2, 3, 4, 5] : [6, 7, 8, 9, 10];
});

watch(
    () => [props.open, props.serie],
    ([isOpen, serie]) => {
        if (isOpen && serie) {
            form.value = {
                peso: serie.peso ?? 0,
                reps_realizadas: serie.reps_realizadas ?? 0,
                tipo_serie: serie.tipo_serie ?? 'efectiva',
                completado: serie.completado ?? true,
                esfuerzo_tipo: serie.esfuerzo_tipo ?? 'rir',
                esfuerzo_valor: serie.esfuerzo_valor ?? null,
                nota_user: serie.nota_user ?? '',
            };
        } else if (! isOpen) {
            form.value = null;
        }
    },
    { immediate: true }
);

const guardar = async () => {
    if (!form.value || !props.serie) return;
    guardando.value = true;
    try {
        const payload = { ...form.value };
        // esfuerzo_valor = null si está vacío
        if (payload.esfuerzo_valor === null) {
            delete payload.esfuerzo_valor;
        }
        const response = await axios.put(`/api/historial/${props.serie.id}`, payload);
        toast.success('Serie actualizada');
        emit('saved', response.data?.data);
        emit('close');
    } catch (e) {
        toast.apiError(e, 'No se pudo actualizar la serie');
    } finally {
        guardando.value = false;
    }
};

const eliminar = async () => {
    if (!props.serie) return;
    const serieId = props.serie.id;
    const serieSnapshot = { ...props.serie };

    const confirmed = await toast.confirm('¿Eliminar esta serie? Podés deshacer durante 5 segundos.', {
        title: 'Eliminar serie',
        confirmLabel: 'Sí, eliminar',
        type: 'error',
    });
    if (!confirmed) return;

    await useUndoable({
        message: 'Serie eliminada',
        apply: () => emit('deleted', serieId),
        undo: () => emit('saved', serieSnapshot), // re-insert local
        commit: async () => {
            await axios.delete(`/api/historial/${serieId}`);
        },
        onError: (e) => toast.apiError(e, 'No se pudo eliminar la serie'),
    });

    emit('close');
};
</script>

<style scoped>
.edit-modal-enter-active,
.edit-modal-leave-active {
    transition: opacity 0.18s ease;
}
.edit-modal-enter-from,
.edit-modal-leave-to {
    opacity: 0;
}
.edit-modal-enter-active > div:last-child,
.edit-modal-leave-active > div:last-child {
    transition: transform 0.22s cubic-bezier(0.2, 0.8, 0.2, 1);
}
.edit-modal-enter-from > div:last-child,
.edit-modal-leave-to > div:last-child {
    transform: translateY(100%);
}
@media (min-width: 640px) {
    .edit-modal-enter-from > div:last-child,
    .edit-modal-leave-to > div:last-child {
        transform: translateY(20px) scale(0.96);
    }
}
</style>
