<template>
    <div class="space-y-6 animate-fadeIn">
        <!-- Banner -->
        <div
            class="rounded-xl bg-indigo-500/10 border border-indigo-500/30 p-4 flex gap-3 items-start"
        >
            <span class="material-symbols-outlined text-indigo-400 text-[20px] shrink-0 mt-0.5">stars</span>
            <div>
                <h4 class="font-bold text-white text-sm uppercase tracking-wider">
                    Ejercicios Clave de Seguimiento
                </h4>
                <p class="text-xs text-slate-300 mt-1">
                    {{
                        isTrainerOrAdmin
                            ? 'Define y monitorea los ejercicios fundamentales para este alumno. Puedes dejar comentarios sobre técnica, cargas y observaciones que el alumno verá en su perfil.'
                            : 'Ejercicios marcados por tu entrenador para priorizar y evaluar de cerca tu evolución de fuerza y 1RM estimado.'
                    }}
                </p>
            </div>
        </div>

        <!-- Form (solo trainer/admin) -->
        <form
            v-if="isTrainerOrAdmin"
            @submit.prevent="$emit('save-key', newKeyExercise)"
            class="bg-[#131924] rounded-xl border border-[#232D42] shadow-lg p-5"
        >
            <h3 class="text-sm font-bold text-white uppercase tracking-wider mb-4 flex items-center gap-2">
                <span class="material-symbols-outlined text-indigo-400 text-[18px]">add_circle</span>
                Designar Nuevo Ejercicio Clave
            </h3>
            <div class="grid gap-4 md:grid-cols-3 md:items-end">
                <div>
                    <label class="block text-[11px] font-medium text-slate-400 uppercase tracking-wider mb-1">
                        Nombre del Ejercicio
                    </label>
                    <input
                        v-model="newKeyExercise.nombre"
                        list="exercises-list"
                        type="text"
                        placeholder="Escribe o selecciona..."
                        class="w-full px-3 py-2 bg-[#18202F] border border-[#232D42] rounded-lg text-white text-xs focus:outline-none focus:ring-1 focus:ring-indigo-500"
                        required
                    />
                    <datalist id="exercises-list">
                        <option v-for="ex in todosEjercicios" :key="ex" :value="ex" />
                    </datalist>
                </div>

                <div>
                    <label class="block text-[11px] font-medium text-slate-400 uppercase tracking-wider mb-1">
                        Observaciones / Técnica
                    </label>
                    <input
                        v-model="newKeyExercise.notas"
                        type="text"
                        placeholder="Ej: Foco en romper el paralelo..."
                        class="w-full px-3 py-2 bg-[#18202F] border border-[#232D42] rounded-lg text-white text-xs focus:outline-none focus:ring-1 focus:ring-indigo-500"
                    />
                </div>

                <div>
                    <button
                        type="submit"
                        :disabled="savingKeyExercise"
                        class="w-full inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-xs font-semibold text-white shadow-md shadow-indigo-600/30 hover:bg-indigo-500 disabled:opacity-50 transition-all gap-2 cursor-pointer"
                    >
                        <span>{{ savingKeyExercise ? 'Guardando...' : 'Designar Ejercicio' }}</span>
                    </button>
                </div>
            </div>
        </form>

        <!-- Loading -->
        <div v-if="keyExercisesLoading" class="text-center py-12 text-slate-400 text-xs">
            Cargando ejercicios clave...
        </div>

        <!-- Empty -->
        <div
            v-else-if="ejerciciosClave.length === 0"
            class="bg-[#131924] rounded-xl shadow-lg p-8 text-center border border-[#232D42]"
        >
            <p class="text-slate-400 text-sm">
                No hay ejercicios clave designados actualmente.
            </p>
        </div>

        <!-- Listado -->
        <div v-else class="grid gap-6 md:grid-cols-2">
            <article
                v-for="ej in ejerciciosClave"
                :key="ej.id"
                class="rounded-xl border border-[#232D42] bg-[#131924] shadow-lg overflow-hidden flex flex-col justify-between"
            >
                <div
                    class="p-4 bg-[#18202F]/60 border-b border-[#232D42] flex items-start justify-between gap-4"
                >
                    <div>
                        <h3 class="text-base font-extrabold text-white">
                            {{ ej.ejercicio_nombre }}
                        </h3>
                        <p class="text-xs text-slate-400 mt-0.5">
                            Asignado por: {{ ej.trainer ? ej.trainer.name : 'Entrenador' }}
                        </p>
                    </div>
                    <button
                        v-if="isTrainerOrAdmin"
                        type="button"
                        @click="$emit('delete-key', ej.id)"
                        class="p-1.5 rounded-lg text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 transition-colors cursor-pointer"
                        :aria-label="`Eliminar ${ej.ejercicio_nombre} de ejercicios clave`"
                        title="Eliminar de Ejercicios Clave"
                    >
                        <span class="material-symbols-outlined text-[18px]">delete</span>
                    </button>
                </div>

                <div class="p-5 space-y-4 flex-1">
                    <!-- Notas -->
                    <div class="rounded-xl bg-[#101521] border border-[#232D42] p-4">
                        <div class="flex items-center justify-between gap-2 mb-2">
                            <span class="text-[11px] font-bold text-indigo-400 uppercase tracking-wider flex items-center gap-1">
                                <span class="material-symbols-outlined text-[15px]">edit_note</span>
                                Notas del Entrenador
                            </span>
                            <button
                                v-if="isTrainerOrAdmin && editingNotesId !== ej.id"
                                type="button"
                                @click="startEdit(ej)"
                                class="text-xs text-indigo-400 hover:text-indigo-300 font-semibold cursor-pointer"
                            >
                                Editar nota
                            </button>
                        </div>

                        <div v-if="editingNotesId === ej.id" class="space-y-2">
                            <textarea
                                v-model="editingNotesValue"
                                class="w-full px-3 py-2 text-xs bg-[#18202F] border border-[#232D42] rounded-lg text-white focus:outline-none focus:ring-1 focus:ring-indigo-500"
                                rows="2"
                            ></textarea>
                            <div class="flex justify-end gap-2 text-xs">
                                <button
                                    type="button"
                                    @click="cancelEdit"
                                    class="px-2.5 py-1.5 border border-[#232D42] rounded-lg text-slate-300 hover:bg-[#18202F] font-medium cursor-pointer"
                                >
                                    Cancelar
                                </button>
                                <button
                                    type="button"
                                    @click="saveEdit(ej)"
                                    class="px-2.5 py-1.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-500 font-semibold cursor-pointer"
                                >
                                    Guardar
                                </button>
                            </div>
                        </div>

                        <p v-else class="text-xs text-slate-300 italic whitespace-pre-line">
                            {{ ej.notas_trainer || 'Sin observaciones o notas técnicas registradas.' }}
                        </p>
                    </div>

                    <!-- Gráfico 1RM -->
                    <div>
                        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">
                            Evolución de 1RM Estimado ({{ rmFormula === 'epley' ? 'Epley' : 'Lander' }})
                        </p>

                        <div
                            v-if="getExercise1RMTimeline(ej.ejercicio_nombre).length > 0"
                            class="relative w-full bg-[#0B0F17]/80 rounded-xl p-3 min-h-[220px] border border-[#232D42]"
                        >
                            <canvas :id="'keyChart-' + ej.id" class="w-full max-h-[220px]"></canvas>
                        </div>

                        <div
                            v-else
                            class="text-center py-8 rounded-xl bg-[#0B0F17]/60 border border-[#232D42] text-xs text-slate-400"
                        >
                            Sin datos en el historial para trazar el gráfico de este ejercicio.
                        </div>
                    </div>
                </div>
            </article>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';

defineProps({
    isTrainerOrAdmin: { type: Boolean, required: true },
    ejerciciosClave: { type: Array, required: true },
    todosEjercicios: { type: Array, required: true },
    keyExercisesLoading: { type: Boolean, required: true },
    savingKeyExercise: { type: Boolean, required: true },
    rmFormula: { type: String, required: true },
    getExercise1RMTimeline: { type: Function, required: true },
});

const emit = defineEmits(['save-key', 'delete-key', 'save-notes']);

const newKeyExercise = ref({ nombre: '', notas: '' });
const editingNotesId = ref(null);
const editingNotesValue = ref('');

const startEdit = (ej) => {
    editingNotesId.value = ej.id;
    editingNotesValue.value = ej.notas_trainer || '';
};

const cancelEdit = () => {
    editingNotesId.value = null;
    editingNotesValue.value = '';
};

const saveEdit = (ej) => {
    emit('save-notes', { ej, value: editingNotesValue.value });
    cancelEdit();
};
</script>
