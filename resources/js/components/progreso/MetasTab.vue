<template>
  <div class="space-y-8 animate-fadeIn">
    <div class="grid md:grid-cols-3 gap-8">
      <!-- Form -->
      <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-5 border border-gray-100 dark:border-gray-700 h-fit md:col-span-1">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
          <span>🎯</span> Establecer Meta
        </h3>
        <form @submit.prevent="$emit('crear', nuevaMeta)" class="space-y-4">
          <div>
            <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Métrica Objetivo</label>
            <select v-model="nuevaMeta.tipo" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm" required>
              <option value="entrenamiento_semanal">Entrenamientos Semanales (Sesiones)</option>
              <option value="peso_corporal">Peso Corporal (kg)</option>
              <option value="cintura_corporal">Medida de Cintura (cm)</option>
              <option value="brazos_corporal">Medida de Brazos/Bíceps (cm)</option>
              <option value="pecho_corporal">Medida de Pecho (cm)</option>
              <option value="otro">Otro</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Valor Objetivo</label>
            <input v-model.number="nuevaMeta.valor_objetivo" type="number" step="0.01" min="0.1" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm" placeholder="Ej: 3 (entrenamientos) o 72.5 (kg)" required />
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Descripción / Notas</label>
            <input v-model="nuevaMeta.descripcion" type="text" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm" placeholder="Ej: Entrenar 3 veces por semana para consistencia" required />
          </div>

          <button type="submit" :disabled="creandoMeta" class="w-full py-2.5 px-4 bg-indigo-600 text-white font-bold rounded-xl hover:bg-indigo-700 transition-all shadow-md flex items-center justify-center gap-2 text-sm">
            <span v-if="creandoMeta">Procesando...</span>
            <span v-else>Establecer Objetivo</span>
          </button>
        </form>
      </div>

      <!-- Listado -->
      <div class="md:col-span-2 space-y-4">
        <div class="flex justify-between items-center mb-2">
          <h3 class="text-lg font-bold text-gray-900 dark:text-white">Mis Objetivos</h3>
          <span class="px-2.5 py-1 text-xs font-bold rounded-full bg-indigo-100 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300">
            {{ metas.filter(m => m.completada).length }} / {{ metas.length }} completados
          </span>
        </div>

        <div v-if="metas.length === 0" class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 border border-gray-100 dark:border-gray-700 text-center text-gray-500 dark:text-gray-400">
          <span class="text-4xl block mb-2">🎯</span>
          <p class="font-bold">No has definido metas personales todavía.</p>
          <p class="text-xs mt-1">Establece objetivos de peso, medidas o entrenamiento para mantenerte motivado.</p>
        </div>

        <div v-else class="grid sm:grid-cols-2 gap-4">
          <article
            v-for="meta in metas"
            :key="meta.id"
            class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-100 dark:border-gray-700 shadow-md relative overflow-hidden transition-all hover:shadow-lg flex flex-col justify-between"
            :class="{'ring-2 ring-emerald-500/50 dark:ring-emerald-400/30': meta.completada}"
          >
            <div v-if="meta.completada" class="absolute top-0 right-0 bg-emerald-500 text-white text-[9px] font-black uppercase px-2 py-1 rounded-bl-lg shadow-sm">
              Alcanzada
            </div>

            <div>
              <div class="flex items-center gap-2.5 mb-2">
                <span class="text-2xl">{{ getMetaEmoji(meta.tipo) }}</span>
                <h4 class="font-bold text-sm text-gray-900 dark:text-white uppercase tracking-wide">
                  {{ formatMetaTipo(meta.tipo) }}
                </h4>
              </div>
              <p class="text-xs text-gray-600 dark:text-gray-400 mb-3">{{ meta.descripcion }}</p>
              <p class="text-sm font-black text-indigo-600 dark:text-indigo-400 font-mono mb-4">
                Objetivo: {{ parseFloat(meta.valor_objetivo) }}
              </p>
            </div>

            <div class="flex gap-2 border-t border-gray-100 dark:border-gray-700 pt-3">
              <button
                @click="$emit('toggle', meta)"
                class="flex-1 py-1.5 px-3 rounded-lg text-xs font-bold transition-all flex items-center justify-center gap-1.5"
                :class="meta.completada
                  ? 'bg-amber-100 hover:bg-amber-200 text-amber-800 dark:bg-amber-950/30 dark:text-amber-300'
                  : 'bg-emerald-600 hover:bg-emerald-700 text-white'"
              >
                <span v-if="meta.completada">Reabrir</span>
                <span v-else>✓ Lograda</span>
              </button>
              <button
                @click="$emit('eliminar', meta.id)"
                class="p-1.5 rounded-lg bg-red-50 hover:bg-red-100 dark:bg-red-950/20 text-red-600 dark:text-red-400 hover:text-red-800 transition-colors"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
              </button>
            </div>
          </article>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';

defineProps({
    metas: { type: Array, required: true },
    creandoMeta: { type: Boolean, required: true },
});

defineEmits(['crear', 'toggle', 'eliminar']);

const nuevaMeta = ref({
    tipo: 'entrenamiento_semanal',
    descripcion: '',
    valor_objetivo: '',
});

const getMetaEmoji = (tipo) => {
    const emojis = {
        entrenamiento_semanal: '🏋️‍♂️',
        peso_corporal: '⚖️',
        cintura_corporal: '📏',
        brazos_corporal: '💪',
        pecho_corporal: '👕',
        otro: '🎯',
    };
    return emojis[tipo] || '🎯';
};

const formatMetaTipo = (tipo) => {
    const labels = {
        entrenamiento_semanal: 'Entrenamientos/Semana',
        peso_corporal: 'Peso Corporal (kg)',
        cintura_corporal: 'Medida Cintura (cm)',
        brazos_corporal: 'Medida Brazos (cm)',
        pecho_corporal: 'Medida Pecho (cm)',
        otro: 'Otro Objetivo',
    };
    return labels[tipo] || tipo;
};
</script>
