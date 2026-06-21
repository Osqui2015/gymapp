<template>
  <div class="space-y-6 animate-fadeIn">
    <!-- Banner -->
    <div class="rounded-xl bg-indigo-50 dark:bg-indigo-950/20 border border-indigo-100 dark:border-indigo-900/50 p-4 flex gap-3">
      <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      <div>
        <h4 class="font-bold text-indigo-900 dark:text-indigo-200 text-sm">Ejercicios Clave de Seguimiento</h4>
        <p class="text-xs text-indigo-800 dark:text-indigo-300 mt-1">
          {{ isTrainerOrAdmin ? 'Define y monitorea los ejercicios fundamentales para este alumno. Puedes dejar comentarios sobre técnica, cargas y observaciones que el alumno verá en su perfil.' : 'Ejercicios marcados por tu entrenador para priorizar y evaluar de cerca tu evolución de fuerza y 1RM estimado.' }}
        </p>
      </div>
    </div>

    <!-- Form (solo trainer/admin) -->
    <form v-if="isTrainerOrAdmin" @submit.prevent="$emit('save-key', newKeyExercise)" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-lg p-5">
      <h3 class="text-base font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
        <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        Designar Nuevo Ejercicio Clave
      </h3>
      <div class="grid gap-4 md:grid-cols-3 md:items-end">
        <div>
          <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Nombre del Ejercicio</label>
          <input
            v-model="newKeyExercise.nombre"
            list="exercises-list"
            type="text"
            placeholder="Escribe o selecciona..."
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 text-sm"
            required
          />
          <datalist id="exercises-list">
            <option v-for="ex in todosEjercicios" :key="ex" :value="ex" />
          </datalist>
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Observaciones / Técnica</label>
          <input
            v-model="newKeyExercise.notas"
            type="text"
            placeholder="Ej: Foco en romper el paralelo..."
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 text-sm"
          />
        </div>

        <div>
          <button
            type="submit"
            :disabled="savingKeyExercise"
            class="w-full inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:opacity-50 transition-all gap-2"
          >
            <span v-if="savingKeyExercise" class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent"></span>
            <span>{{ savingKeyExercise ? 'Guardando...' : 'Designar Ejercicio' }}</span>
          </button>
        </div>
      </div>
    </form>

    <!-- Loading -->
    <div v-if="keyExercisesLoading" class="text-center py-12 text-gray-500 dark:text-gray-400">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600 mx-auto mb-2"></div>
      Cargando ejercicios clave...
    </div>

    <!-- Empty -->
    <div v-else-if="ejerciciosClave.length === 0" class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8 text-center border border-gray-200 dark:border-gray-700">
      <p class="text-gray-500 dark:text-gray-400 text-lg">No hay ejercicios clave designados actualmente.</p>
    </div>

    <!-- Listado -->
    <div v-else class="grid gap-6 md:grid-cols-2">
      <article
        v-for="ej in ejerciciosClave"
        :key="ej.id"
        class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-lg overflow-hidden flex flex-col justify-between"
      >
        <div class="p-5 border-b border-gray-100 dark:border-gray-700 flex items-start justify-between gap-4">
          <div>
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ ej.ejercicio_nombre }}</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
              Asignado por: {{ ej.trainer ? ej.trainer.name : 'Entrenador' }}
            </p>
          </div>
          <button
            v-if="isTrainerOrAdmin"
            @click="$emit('delete-key', ej.id)"
            class="p-1 rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-950/30 transition-colors"
            :aria-label="`Eliminar ${ej.ejercicio_nombre} de ejercicios clave`"
            title="Eliminar de Ejercicios Clave"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
          </button>
        </div>

        <div class="p-5 space-y-4 flex-1">
          <!-- Notas -->
          <div class="rounded-xl bg-slate-50 dark:bg-slate-900/50 border border-slate-100 dark:border-slate-800 p-4">
            <div class="flex items-center justify-between gap-2 mb-2">
              <span class="text-xs font-bold text-slate-500 dark:text-slate-400 flex items-center gap-1">
                <span>📝</span> NOTAS DEL ENTRENADOR
              </span>
              <button
                v-if="isTrainerOrAdmin && editingNotesId !== ej.id"
                @click="startEdit(ej)"
                class="text-xs text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300 font-semibold"
              >
                Editar nota
              </button>
            </div>

            <div v-if="editingNotesId === ej.id" class="space-y-2">
              <textarea
                v-model="editingNotesValue"
                class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500"
                rows="2"
              ></textarea>
              <div class="flex justify-end gap-2 text-xs">
                <button
                  @click="cancelEdit"
                  class="px-2.5 py-1.5 border border-gray-300 dark:border-gray-600 rounded text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 font-medium"
                >
                  Cancelar
                </button>
                <button
                  @click="saveEdit(ej)"
                  class="px-2.5 py-1.5 bg-indigo-600 text-white rounded hover:bg-indigo-500 font-semibold shadow-sm"
                >
                  Guardar
                </button>
              </div>
            </div>

            <p v-else class="text-xs md:text-sm text-slate-700 dark:text-slate-300 italic whitespace-pre-line">
              {{ ej.notas_trainer || 'Sin observaciones o notas técnicas registradas.' }}
            </p>
          </div>

          <!-- Gráfico 1RM -->
          <div>
            <p class="text-xs font-bold text-gray-500 dark:text-gray-400 mb-2">
              Evolución de 1RM Estimado (Fórmula: {{ rmFormula === 'epley' ? 'Epley' : 'Lander' }})
            </p>

            <div v-if="getExercise1RMTimeline(ej.ejercicio_nombre).length > 0" class="relative w-full bg-slate-50 dark:bg-slate-900/30 rounded-xl p-3 min-h-[220px] border border-slate-100 dark:border-slate-800">
              <canvas :id="'keyChart-' + ej.id" class="w-full max-h-[220px]"></canvas>
            </div>

            <div v-else class="text-center py-8 rounded-xl bg-slate-50 dark:bg-slate-900/30 border border-slate-100 dark:border-slate-800 text-xs text-gray-400 dark:text-gray-500">
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

const props = defineProps({
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
