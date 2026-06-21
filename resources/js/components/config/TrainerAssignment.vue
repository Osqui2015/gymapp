<template>
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Trainers list -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden lg:col-span-1">
      <div class="px-6 py-4 bg-gradient-to-r from-indigo-500 to-purple-500">
        <h2 class="text-xl font-bold text-white flex items-center gap-2">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
          </svg>
          Entrenadores
        </h2>
      </div>
      <div class="p-4 space-y-2 max-h-[550px] overflow-y-auto bg-gray-50/50 dark:bg-gray-900/10">
        <div
          v-for="t in trainerList"
          :key="t.id"
          @click="$emit('select-trainer', t)"
          :class="[
            'p-4 rounded-xl border cursor-pointer transition-all duration-200 flex items-center justify-between',
            selectedTrainer?.id === t.id
              ? 'bg-indigo-50 border-indigo-500 dark:bg-indigo-950/40 dark:border-indigo-500 text-indigo-900 dark:text-indigo-100 shadow-sm'
              : 'border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 text-gray-700 dark:text-gray-300',
          ]"
        >
          <div>
            <p class="font-bold text-sm">{{ t.name }}</p>
            <p class="text-xs text-gray-400">@{{ t.nick }} • <span class="capitalize">{{ t.role }}</span></p>
          </div>
          <svg v-if="selectedTrainer?.id === t.id" class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
          </svg>
        </div>
      </div>
    </div>

    <!-- Alumnos -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden lg:col-span-2">
      <div class="px-6 py-4 bg-gradient-to-r from-purple-500 to-indigo-500 flex justify-between items-center">
        <h2 class="text-xl font-bold text-white flex items-center gap-2">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
          </svg>
          Alumnos Asignados
        </h2>
        <span v-if="selectedTrainer" class="px-3 py-1 bg-white/20 text-white rounded-full text-xs font-semibold">
          {{ selectedAlumnosCount }} alumnos
        </span>
      </div>

      <div v-if="!selectedTrainer" class="p-12 text-center text-gray-500 dark:text-gray-400">
        <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>
        <p class="text-lg font-semibold">Selecciona un entrenador a la izquierda</p>
        <p class="text-sm text-gray-400 mt-1">Para gestionar, activar o modificar su listado de alumnos.</p>
      </div>

      <div v-else class="p-6 space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Alumnos de: {{ selectedTrainer.name }}</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400">Marca los alumnos que entrenarán bajo el cargo de este entrenador.</p>
          </div>
          <button @click="$emit('guardar-asignacion')" :disabled="guardando" class="inline-flex items-center justify-center bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-400 text-white px-5 py-2.5 rounded-lg font-semibold transition-all shadow-md text-sm gap-2">
            <svg v-if="guardando" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            {{ guardando ? 'Guardando...' : 'Guardar Cambios' }}
          </button>
        </div>

        <div class="flex flex-col sm:flex-row gap-3 items-stretch sm:items-center justify-between">
          <div class="relative w-full sm:w-72">
            <input v-model="localSearch" type="text" class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-indigo-500 text-sm" placeholder="Buscar alumno...">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
          </div>
          <div class="flex items-center gap-2">
            <button @click="$emit('seleccionar-todos')" class="px-3 py-1.5 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg text-xs font-semibold transition-colors">Seleccionar Todos</button>
            <button @click="$emit('desmarcar-todos')" class="px-3 py-1.5 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg text-xs font-semibold transition-colors">Desmarcar Todos</button>
          </div>
        </div>

        <div class="border border-gray-200 dark:border-gray-700 rounded-xl divide-y divide-gray-200 dark:divide-gray-700 max-h-[450px] overflow-y-auto bg-gray-50 dark:bg-gray-900/40">
          <label
            v-for="a in filteredAlumnos"
            :key="a.id"
            :class="[
              'p-4 flex items-center gap-4 cursor-pointer transition-colors',
              checkedAlumnos.includes(a.id) ? 'bg-indigo-50/40 dark:bg-indigo-950/20' : 'hover:bg-white dark:hover:bg-gray-800',
            ]"
          >
            <input
              type="checkbox"
              :value="a.id"
              :checked="checkedAlumnos.includes(a.id)"
              @change="$emit('update:checkedAlumnos', toggleId(a.id))"
              class="w-5 h-5 rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500 cursor-pointer"
            />
            <div class="flex-1 flex items-center justify-between gap-4">
              <div>
                <p class="font-semibold text-sm text-gray-900 dark:text-white">{{ a.name }}</p>
                <p class="text-xs text-gray-400">@{{ a.nick }} • {{ a.email }}</p>
              </div>
              <div v-if="a.trainer_id && a.trainer_id !== selectedTrainer.id" class="text-right">
                <span class="inline-flex items-center rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-semibold text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400 border border-yellow-200 dark:border-yellow-900/40">Asignado a: {{ a.trainer?.name }}</span>
              </div>
              <div v-else-if="a.trainer_id === selectedTrainer.id" class="text-right">
                <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-800 dark:bg-green-900/30 dark:text-green-400 border border-green-200 dark:border-green-900/40">Activo</span>
              </div>
              <div v-else class="text-right">
                <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-semibold text-gray-500 dark:bg-gray-800 dark:text-gray-400">Sin Asignar</span>
              </div>
            </div>
          </label>
          <div v-if="filteredAlumnos.length === 0" class="p-8 text-center text-gray-500 dark:text-gray-400">No se encontraron alumnos.</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';

const props = defineProps({
    trainerList: { type: Array, required: true },
    alumnoList: { type: Array, required: true },
    selectedTrainer: { type: Object, default: null },
    checkedAlumnos: { type: Array, required: true },
    guardando: { type: Boolean, required: true },
    selectedAlumnosCount: { type: Number, required: true },
});

const emit = defineEmits([
    'select-trainer',
    'guardar-asignacion',
    'seleccionar-todos',
    'desmarcar-todos',
    'update:search',
    'update:checkedAlumnos',
]);

const toggleId = (id) => {
    return props.checkedAlumnos.includes(id)
        ? props.checkedAlumnos.filter((x) => x !== id)
        : [...props.checkedAlumnos, id];
};

const localSearch = ref('');
watch(localSearch, (val) => emit('update:search', val));

const filteredAlumnos = computed(() => {
    if (!localSearch.value) return props.alumnoList;
    const search = localSearch.value.toLowerCase();
    return props.alumnoList.filter(
        (a) =>
            a.name.toLowerCase().includes(search) ||
            a.nick.toLowerCase().includes(search) ||
            a.email.toLowerCase().includes(search)
    );
});
</script>
