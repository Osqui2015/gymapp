<template>
  <div class="grid md:grid-cols-3 gap-8">
    <!-- Calculadora Manual -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-lg p-5 h-fit md:col-span-1">
      <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
        <span>🧮</span> Calculadora de 1RM
      </h3>

      <div class="space-y-4">
        <div>
          <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Peso Levantado (kg)</label>
          <input
            v-model.number="localCalc.weight"
            type="number"
            min="0.1"
            step="0.5"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm"
          />
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Repeticiones Realizadas</label>
          <input
            v-model.number="localCalc.reps"
            type="number"
            min="1"
            max="30"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm"
          />
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Fórmula de Estimación</label>
          <div class="grid grid-cols-2 gap-2">
            <button
              @click="localCalc.formula = 'epley'"
              class="py-1.5 text-xs font-semibold rounded-lg border transition-all"
              :class="localCalc.formula === 'epley' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-transparent text-gray-500 border-gray-300 dark:border-gray-600'"
            >
              Epley
            </button>
            <button
              @click="localCalc.formula = 'lander'"
              class="py-1.5 text-xs font-semibold rounded-lg border transition-all"
              :class="localCalc.formula === 'lander' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-transparent text-gray-500 border-gray-300 dark:border-gray-600'"
            >
              Lander
            </button>
          </div>
        </div>

        <!-- Output -->
        <div class="mt-6 p-4 bg-indigo-50 dark:bg-indigo-950/20 border border-indigo-100 dark:border-indigo-900/50 rounded-xl text-center">
          <p class="text-xs text-indigo-700 dark:text-indigo-400 uppercase tracking-wider font-bold">1RM Estimado</p>
          <p class="text-4xl font-black text-indigo-600 dark:text-indigo-400 font-mono mt-1">
            {{ estimated1RM.toFixed(1) }} <span class="text-sm font-normal">kg</span>
          </p>
        </div>

        <!-- Porcentajes -->
        <div v-if="estimated1RM > 0" class="mt-4 border-t border-gray-100 dark:border-gray-700 pt-4">
          <p class="text-xs font-bold text-gray-700 dark:text-gray-300 mb-2">Porcentajes del 1RM:</p>
          <div class="grid grid-cols-2 gap-2 text-xs">
            <div
              v-for="p in percentages1RM"
              :key="p.percentage"
              class="flex justify-between p-2 bg-gray-50 dark:bg-gray-900/50 rounded"
            >
              <span class="font-bold text-gray-500 font-mono">{{ p.percentage }}%</span>
              <span class="font-extrabold text-gray-800 dark:text-gray-200 font-mono">{{ p.weight }} kg</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Récords Personales e Historial de 1RM -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-lg p-5 md:col-span-2">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6 pb-4 border-b border-gray-100 dark:border-gray-700">
        <div>
          <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
            <span>🏆</span> Records e 1RM Históricos
          </h3>
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Estimación en base a las mayores cargas reales registradas</p>
        </div>
        <select
          :value="rmFormula"
          @change="$emit('update:rmFormula', $event.target.value)"
          class="px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 text-xs font-bold"
        >
          <option value="epley">Epley formula</option>
          <option value="lander">Lander formula</option>
        </select>
      </div>

      <ResponsiveTable
        :rows="historical1RMs"
        :columns="[
          { key: 'name', label: 'Ejercicio', thClass: '', tdClass: 'font-bold text-gray-900 dark:text-white', sortable: true, searchable: true },
          { key: 'pr', label: 'Record de Carga (PR)', thClass: 'text-center', tdClass: 'text-center text-gray-700 dark:text-gray-300 font-mono', sortable: true },
          { key: 'rm', label: '1RM Estimado', thClass: 'text-right', tdClass: 'text-right font-black text-indigo-600 dark:text-indigo-400 font-mono text-base', sortable: true },
          { key: 'date', label: 'Fecha del Record', thClass: 'text-center', tdClass: 'text-center text-xs text-gray-500 dark:text-gray-400 font-medium', sortable: true },
        ]"
        thead-class="bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-300 uppercase text-[10px] tracking-wider"
        sortable
        filterable
        filter-placeholder="Buscar ejercicio…"
      >
        <template #rows="{ row }">
          <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors bg-white dark:bg-gray-800">
            <td class="px-4 py-3.5 font-bold text-gray-900 dark:text-white">
              {{ row.name }}
            </td>
            <td class="px-4 py-3.5 text-center text-gray-700 dark:text-gray-300 font-mono">
              {{ row.weight.toFixed(1) }} kg × {{ row.reps }} reps
            </td>
            <td class="px-4 py-3.5 text-right font-black text-indigo-600 dark:text-indigo-400 font-mono text-base">
              {{ row.rm.toFixed(1) }} kg
            </td>
            <td class="px-4 py-3.5 text-center text-xs text-gray-500 dark:text-gray-400 font-medium">
              {{ row.date }}
            </td>
          </tr>
        </template>

        <template #cards="{ row }">
          <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-4 space-y-3">
            <div class="flex items-start justify-between gap-3">
              <p class="font-bold text-gray-900 dark:text-white text-sm flex-1 min-w-0">
                {{ row.name }}
              </p>
              <p class="font-black text-indigo-600 dark:text-indigo-400 font-mono text-lg whitespace-nowrap">
                {{ row.rm.toFixed(1) }} kg
              </p>
            </div>
            <div class="flex items-center justify-between text-sm">
              <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">PR</span>
              <span class="text-gray-700 dark:text-gray-300 font-mono">
                {{ row.weight.toFixed(1) }} kg × {{ row.reps }} reps
              </span>
            </div>
            <div class="flex items-center justify-between text-sm">
              <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Fecha</span>
              <span class="text-xs text-gray-500 dark:text-gray-400 font-medium">
                {{ row.date }}
              </span>
            </div>
          </div>
        </template>

        <template #empty>
          <div class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
            No hay suficientes datos del historial con pesos y repeticiones para estimar records.
          </div>
        </template>
      </ResponsiveTable>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import ResponsiveTable from '../ResponsiveTable.vue';

const props = defineProps({
    calculator: { type: Object, required: true }, // { weight, reps, formula }
    rmFormula: { type: String, required: true },
    historical1RMs: { type: Array, required: true },
});

const emit = defineEmits(['update:calculator', 'update:rmFormula']);

const localCalc = ref({ ...props.calculator });
watch(() => props.calculator, (val) => { localCalc.value = { ...val }; }, { deep: true });
watch(localCalc, (val) => { emit('update:calculator', { ...val }); }, { deep: true });

const estimated1RM = computed(() => {
    const w = parseFloat(localCalc.value.weight);
    const r = parseInt(localCalc.value.reps);
    if (!w || !r || w <= 0 || r <= 0) return 0;
    if (localCalc.value.formula === 'epley') {
        return w * (1 + r / 30);
    }
    return (100 * w) / (101.3 - 2.6712 * r);
});

const percentages1RM = computed(() => {
    const rm = estimated1RM.value;
    if (!rm) return [];
    return [95, 90, 85, 80, 75, 70, 65, 60].map((p) => ({
        percentage: p,
        weight: (rm * (p / 100)).toFixed(1),
    }));
});
</script>
