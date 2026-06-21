<template>
  <div>
    <!-- Reminder / last record banners -->
    <div
      v-if="puedeRegistrar && progresos.length > 0"
      class="mb-6 p-4 bg-amber-50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-900/50 rounded-xl animate-pulse"
    >
      <div class="flex items-center gap-3">
        <div class="flex-shrink-0">
          <svg class="w-6 h-6 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <div>
          <p class="font-bold text-amber-800 dark:text-amber-200 text-sm">¡Es hora de tu medición!</p>
          <p class="text-xs text-amber-700 dark:text-amber-300 mt-0.5">Han pasado más de 15 días desde tu último registro. Ingresa tus nuevas medidas para ver tu progreso.</p>
        </div>
      </div>
    </div>

    <div
      v-if="ultimoRegistro && !puedeRegistrar"
      class="mb-6 p-4 bg-green-50 dark:bg-green-950/20 border border-green-200 dark:border-green-900/50 rounded-xl"
    >
      <div class="flex items-center justify-between flex-wrap gap-2 text-sm">
        <div class="flex items-center gap-3">
          <div class="flex-shrink-0">
            <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <div>
            <p class="font-bold text-green-800 dark:text-green-200">Último registro</p>
            <p class="text-xs text-green-700 dark:text-green-300" id="fecha-ultimo">
              {{ formatFecha(ultimoRegistro.fecha) }}
            </p>
          </div>
        </div>
        <div class="text-xs font-semibold text-green-700 dark:text-green-400">
          Podrás registrar un nuevo progreso en {{ diasRestantesParaRegistrar }} días
        </div>
      </div>
    </div>

    <!-- Chart -->
    <div v-show="progresos.length > 1" class="mb-8 bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-5 border border-gray-100 dark:border-gray-700">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
          <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
            <span>📈</span> Gráfico de Evolución Física
          </h3>
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Monitorea los cambios de tus medidas en el tiempo de forma interactiva</p>
        </div>
        <select
          :value="metricaGrafica"
          @change="$emit('update:metricaGrafica', $event.target.value)"
          class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm font-semibold"
        >
          <option value="peso">Peso (kg)</option>
          <option value="cintura">Cintura (cm)</option>
          <option value="brazos">Brazos/Bíceps (cm)</option>
          <option value="pecho">Pecho (cm)</option>
          <option value="hombros">Hombros (cm)</option>
          <option value="muslos">Muslos (cm)</option>
        </select>
      </div>

      <div class="relative w-full bg-gray-50 dark:bg-gray-900/30 rounded-xl p-4 min-h-[300px]">
        <canvas id="progresoChart" class="w-full max-h-[300px]"></canvas>
      </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-8">
      <!-- Form -->
      <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-4 sm:p-6 border border-gray-100 dark:border-gray-700 h-fit">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
          <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
          </svg>
          Registrar Medidas
        </h2>

        <form @submit.prevent="$emit('save', form)" class="space-y-6">
          <!-- Datos Personales -->
          <div class="border-b border-gray-200 dark:border-gray-700 pb-6">
            <h3 class="text-base font-semibold text-gray-700 dark:text-gray-300 mb-4 flex items-center gap-2">
              <span>📋</span> Datos Personales
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Peso (kg)</label>
                <input v-model.number="form.peso" type="number" step="0.01" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm" placeholder="Ej: 75.5" :required="progresos.length === 0" />
              </div>
              <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Altura (cm)</label>
                <input v-model.number="form.altura" type="number" step="0.01" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm" placeholder="Ej: 175" :required="progresos.length === 0" />
              </div>
              <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Edad</label>
                <input v-model.number="form.edad" type="number" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm" placeholder="Ej: 25" :required="progresos.length === 0" />
              </div>
              <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Sexo</label>
                <select v-model="form.sexo" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm" :required="progresos.length === 0">
                  <option value="">Seleccionar</option>
                  <option value="masculino">Masculino</option>
                  <option value="femenino">Femenino</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Medidas -->
          <div>
            <h3 class="text-base font-semibold text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-2">
              <span>📐</span> Medidas Corporales (Lado Derecho)
            </h3>
            <p class="text-[11px] text-gray-500 dark:text-gray-400 mb-4 italic">Mide siempre del mismo lado para mantener la consistencia</p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <MedidaInput
                v-for="m in medidasInputs"
                :key="m.campo"
                :value="form[m.campo]"
                :label="m.label"
                :hint="m.hint"
                :placeholder="m.placeholder"
                @change="(v) => form[m.campo] = v"
              />
            </div>
          </div>

          <button type="submit" :disabled="guardando || !puedeRegistrar" class="w-full py-3 px-4 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-bold rounded-xl hover:from-indigo-700 hover:to-purple-700 transition-all shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none flex items-center justify-center gap-2">
            <svg v-if="guardando" class="animate-spin w-5 h-5 text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
            </svg>
            <span>{{ guardando ? 'Guardando...' : 'Guardar Progreso' }}</span>
          </button>
        </form>
      </div>

      <!-- Historial -->
      <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-4 sm:p-6 border border-gray-100 dark:border-gray-700">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
          <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
          </svg>
          Historial de Progreso
        </h2>

        <div class="overflow-x-auto hidden md:block">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
              <tr>
                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Fecha</th>
                <th class="px-3 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Peso</th>
                <th class="px-3 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Cintura</th>
                <th class="px-3 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Pecho</th>
                <th class="px-3 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Brazos</th>
                <th class="px-3 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Acción</th>
              </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-if="progresos.length === 0">
                <td colspan="6" class="px-3 py-8 text-center text-gray-500 dark:text-gray-400">
                  <svg class="mx-auto w-12 h-12 text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                  </svg>
                  <p class="font-semibold text-sm">No hay registros aún</p>
                  <p class="text-xs mt-1">Ingresa tus medidas para comenzar</p>
                </td>
              </tr>
              <tr v-for="(p, index) in progresos" :key="p.id" :class="[index % 2 === 0 ? 'bg-white dark:bg-gray-800' : 'bg-gray-50/50 dark:bg-gray-700/30']" class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                <td class="px-3 py-3 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-white">{{ formatFecha(p.fecha) }}</td>
                <td class="px-3 py-3 whitespace-nowrap text-sm text-center text-gray-600 dark:text-gray-300 font-mono">{{ p.peso ? p.peso + ' kg' : '-' }}</td>
                <td class="px-3 py-3 whitespace-nowrap text-sm text-center text-gray-600 dark:text-gray-300 font-mono">{{ p.cintura ? p.cintura + ' cm' : '-' }}</td>
                <td class="px-3 py-3 whitespace-nowrap text-sm text-center text-gray-600 dark:text-gray-300 font-mono">{{ p.pecho ? p.pecho + ' cm' : '-' }}</td>
                <td class="px-3 py-3 whitespace-nowrap text-sm text-center text-gray-600 dark:text-gray-300 font-mono">{{ p.brazos ? p.brazos + ' cm' : '-' }}</td>
                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">
                  <button @click="$emit('ver-detalle', p.id)" class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 font-semibold transition-colors">Ver</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Mobile: cards con medidas en grilla -->
        <div class="md:hidden divide-y divide-gray-100 dark:divide-gray-700">
          <div v-if="progresos.length === 0" class="px-3 py-8 text-center text-gray-500 dark:text-gray-400">
            <svg class="mx-auto w-12 h-12 text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
            <p class="font-semibold text-sm">No hay registros aún</p>
            <p class="text-xs mt-1">Ingresa tus medidas para comenzar</p>
          </div>
          <div
            v-for="p in progresos"
            :key="p.id"
            class="p-4 space-y-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
          >
            <div class="flex items-center justify-between">
              <span class="font-semibold text-gray-900 dark:text-white">{{ formatFecha(p.fecha) }}</span>
              <button @click="$emit('ver-detalle', p.id)" class="text-sm text-indigo-600 dark:text-indigo-400 font-semibold hover:underline">Ver detalle →</button>
            </div>
            <div class="grid grid-cols-2 gap-2">
              <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-2.5">
                <p class="text-[10px] text-gray-500 dark:text-gray-400 uppercase tracking-wider font-semibold">Peso</p>
                <p class="text-sm font-bold text-gray-900 dark:text-white font-mono">{{ p.peso ? p.peso + ' kg' : '—' }}</p>
              </div>
              <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-2.5">
                <p class="text-[10px] text-gray-500 dark:text-gray-400 uppercase tracking-wider font-semibold">Cintura</p>
                <p class="text-sm font-bold text-gray-900 dark:text-white font-mono">{{ p.cintura ? p.cintura + ' cm' : '—' }}</p>
              </div>
              <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-2.5">
                <p class="text-[10px] text-gray-500 dark:text-gray-400 uppercase tracking-wider font-semibold">Pecho</p>
                <p class="text-sm font-bold text-gray-900 dark:text-white font-mono">{{ p.pecho ? p.pecho + ' cm' : '—' }}</p>
              </div>
              <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-2.5">
                <p class="text-[10px] text-gray-500 dark:text-gray-400 uppercase tracking-wider font-semibold">Brazos</p>
                <p class="text-sm font-bold text-gray-900 dark:text-white font-mono">{{ p.brazos ? p.brazos + ' cm' : '—' }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Tips -->
    <div class="mt-8 bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-900/10 dark:to-purple-900/10 rounded-2xl p-4 sm:p-6 border border-indigo-100 dark:border-indigo-900/30">
      <h3 class="text-lg font-bold text-indigo-900 dark:text-indigo-100 mb-4 flex items-center gap-2">
        <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        Tips para tomar tus medidas
      </h3>
      <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4 text-xs text-indigo-800 dark:text-indigo-200">
        <div v-for="(tip, i) in tips" :key="i" class="flex items-start gap-2">
          <span class="text-indigo-500 font-bold">{{ i + 1 }}.</span>
          <p v-html="tip" />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import MedidaInput from './MedidaInput.vue';

const props = defineProps({
    progresos: { type: Array, required: true },
    ultimoRegistro: { type: Object, default: null },
    puedeRegistrar: { type: Boolean, required: true },
    diasRestantesParaRegistrar: { type: Number, required: true },
    guardando: { type: Boolean, required: true },
    form: { type: Object, required: true },
    metricaGrafica: { type: String, required: true },
    formatFecha: { type: Function, required: true },
});

defineEmits(['save', 'ver-detalle', 'update:metricaGrafica']);

const medidasInputs = [
    { campo: 'cuello', label: 'Cuello', hint: 'Bajo la nuez', placeholder: '38' },
    { campo: 'hombros', label: 'Hombros', hint: 'Contorno completo', placeholder: '110' },
    { campo: 'pecho', label: 'Pecho', hint: 'Prominente', placeholder: '100' },
    { campo: 'brazos', label: 'Brazos/Bíceps', hint: 'Parte más gruesa', placeholder: '35' },
    { campo: 'cintura', label: 'Cintura', hint: 'Pérdida de grasa', placeholder: '85' },
    { campo: 'cadera', label: 'Cadera/Glúteos', hint: 'Parte más ancha', placeholder: '95' },
    { campo: 'muslos', label: 'Muslos', hint: 'Parte más gruesa', placeholder: '55' },
    { campo: 'pantorrillas', label: 'Pantorrillas', hint: 'Parte más ancha', placeholder: '38' },
];

const tips = [
    'Usa siempre una cinta métrica flexible y el <strong>lado derecho</strong> de tu cuerpo.',
    'Mide a primera hora de la mañana, después de ir al baño.',
    'No presiones la cinta, debe estar cómoda pero ajustada.',
    'Mantén los brazos a los lados del cuerpo al medir hombros.',
    'Respira normalmente al medir el pecho.',
    'Si puedes, pide ayuda para las medidas de hombros.',
];
</script>
