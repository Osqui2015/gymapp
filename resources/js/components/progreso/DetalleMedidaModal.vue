<template>
  <Teleport to="body">
    <div
      v-if="modal.mostrar"
      class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4"
      @click.self="$emit('cerrar')"
    >
      <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto border border-gray-100 dark:border-gray-700 animate-scaleIn">
        <div class="p-6">
          <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-100 dark:border-gray-700">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
              <span>📋</span> Detalle de Progreso: {{ formatFecha(modal.progreso.fecha) }}
            </h3>
            <button @click="$emit('cerrar')" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition-colors">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <div class="space-y-6">
            <!-- Datos Personales -->
            <div
              v-if="modal.progreso.peso || modal.progreso.altura || modal.progreso.edad || modal.progreso.sexo"
              class="border-b border-gray-100 dark:border-gray-700 pb-4"
            >
              <h4 class="font-bold text-sm text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
                <span>👤</span> Datos Generales
              </h4>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                <div v-if="modal.progreso.peso" class="flex justify-between items-center p-2.5 rounded-lg bg-gray-50 dark:bg-gray-900/50">
                  <span class="text-gray-500">Peso:</span>
                  <div class="flex items-center gap-2">
                    <span class="font-bold font-mono">{{ modal.progreso.peso }} kg</span>
                    <span
                      v-if="modal.comparacion.peso && modal.comparacion.peso.diferencia !== null"
                      :class="diffClass(modal.comparacion.peso.diferencia)"
                    >
                      {{ modal.comparacion.peso.diferencia > 0 ? '+' : '' }}{{ modal.comparacion.peso.diferencia }}
                    </span>
                  </div>
                </div>
                <div v-if="modal.progreso.altura" class="flex justify-between items-center p-2.5 rounded-lg bg-gray-50 dark:bg-gray-900/50">
                  <span class="text-gray-500">Altura:</span>
                  <span class="font-bold font-mono">{{ modal.progreso.altura }} cm</span>
                </div>
                <div v-if="modal.progreso.edad" class="flex justify-between items-center p-2.5 rounded-lg bg-gray-50 dark:bg-gray-900/50">
                  <span class="text-gray-500">Edad:</span>
                  <span class="font-bold font-mono">{{ modal.progreso.edad }} años</span>
                </div>
                <div v-if="modal.progreso.sexo" class="flex justify-between items-center p-2.5 rounded-lg bg-gray-50 dark:bg-gray-900/50">
                  <span class="text-gray-500">Sexo:</span>
                  <span class="font-bold capitalize">{{ modal.progreso.sexo }}</span>
                </div>
              </div>
            </div>

            <!-- Medidas -->
            <div>
              <h4 class="font-bold text-sm text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
                <span>📏</span> Medidas Corporales
              </h4>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                <div
                  v-for="campo in camposMedidas"
                  :key="campo"
                  v-show="modal.comparacion[campo]"
                  class="flex justify-between items-center p-2.5 rounded-lg bg-gray-50 dark:bg-gray-900/50"
                >
                  <span class="text-gray-500 capitalize">{{ labelCampos[campo] }}:</span>
                  <div class="flex items-center gap-2" v-if="modal.comparacion[campo]">
                    <span class="font-bold font-mono">{{ modal.comparacion[campo].actual }} cm</span>
                    <span
                      v-if="modal.comparacion[campo].diferencia !== null"
                      :class="diffClass(modal.comparacion[campo].diferencia)"
                    >
                      {{ modal.comparacion[campo].diferencia > 0 ? '+' : '' }}{{ modal.comparacion[campo].diferencia }}
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Tips -->
            <div
              v-if="modal.comparacion.cintura && modal.comparacion.cintura.diferencia < 0"
              class="p-4 bg-green-50 dark:bg-green-950/20 border border-green-200 dark:border-green-900/30 rounded-xl"
            >
              <p class="text-xs text-green-800 dark:text-green-200">
                <strong>🎉 ¡Excelente!</strong> Tu cintura ha disminuido {{ Math.abs(modal.comparacion.cintura.diferencia) }} cm.
                Esto indica una pérdida de tejido adiposo (grasa corporal). ¡Continúa así!
              </p>
            </div>

            <div
              v-if="modal.comparacion.brazos && modal.comparacion.brazos.diferencia > 0"
              class="p-4 bg-indigo-50 dark:bg-indigo-950/20 border border-indigo-200 dark:border-indigo-900/30 rounded-xl"
            >
              <p class="text-xs text-indigo-800 dark:text-indigo-200">
                <strong>💪 ¡Excelente progresión!</strong> Tus brazos han aumentado {{ modal.comparacion.brazos.diferencia }} cm.
                Esto sugiere una ganancia de hipertrofia y masa muscular. ¡Sigue entrenando duro!
              </p>
            </div>
          </div>

          <div class="mt-8 pt-4 border-t border-gray-100 dark:border-gray-700 flex justify-end">
            <button @click="$emit('cerrar')" class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-650 text-gray-700 dark:text-gray-200 font-bold rounded-xl transition-colors text-sm">
              Cerrar
            </button>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
defineProps({
    modal: { type: Object, required: true },
    formatFecha: { type: Function, required: true },
});

defineEmits(['cerrar']);

const camposMedidas = ['cuello', 'hombros', 'pecho', 'brazos', 'cintura', 'cadera', 'muslos', 'pantorrillas'];
const labelCampos = {
    cuello: 'Cuello',
    hombros: 'Hombros',
    pecho: 'Pecho',
    brazos: 'Brazos',
    cintura: 'Cintura',
    cadera: 'Cadera',
    muslos: 'Muslos',
    pantorrillas: 'Pantorrillas',
};

const diffClass = (d) => {
    if (d > 0) return 'text-xs font-semibold px-2 py-0.5 rounded-full text-green-600 bg-green-50 dark:text-green-400 dark:bg-green-950/20';
    if (d < 0) return 'text-xs font-semibold px-2 py-0.5 rounded-full text-red-600 bg-red-50 dark:text-red-400 dark:bg-red-950/20';
    return 'text-xs font-semibold px-2 py-0.5 rounded-full text-gray-500 bg-gray-100 dark:text-gray-400 dark:bg-gray-800';
};
</script>
