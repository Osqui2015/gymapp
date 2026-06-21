<template>
  <div class="min-h-screen bg-gradient-to-br from-gray-50 to-indigo-50 dark:from-gray-900 dark:to-indigo-900/40 py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
      <Breadcrumbs :items="[{ label: 'Inicio', href: '/dashboard' }, { label: 'Nutrición' }]" />
      <!-- Header -->
      <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
          <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white flex items-center gap-2">
            <span>🍎</span> Diario de Nutrición & Agua
          </h1>
          <p class="mt-2 text-gray-600 dark:text-gray-400">Registra tus calorías, macronutrientes e hidratación diaria</p>
        </div>
        <a
          href="/dashboard"
          class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-xl border border-gray-200 dark:border-gray-700 font-semibold text-sm shadow-sm transition-all"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          Volver al Dashboard
        </a>
      </div>

      <!-- Date selector -->
      <div class="mb-8 bg-white dark:bg-gray-800 rounded-2xl shadow-md p-4 border border-gray-100 dark:border-gray-700 flex justify-between items-center">
        <button
          @click="cambiarDia(-1)"
          class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition-colors text-gray-600 dark:text-gray-300"
        >
          &larr; Día Anterior
        </button>
        <span class="font-extrabold text-gray-800 dark:text-white text-sm sm:text-base">
          📅 {{ formatFecha(fechaSeleccionada) }}
        </span>
        <button
          @click="cambiarDia(1)"
          class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition-colors text-gray-600 dark:text-gray-300"
        >
          Siguiente Día &rarr;
        </button>
      </div>

      <div class="grid md:grid-cols-3 gap-8">
        <!-- Circular progress and macros chart -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700 md:col-span-1 flex flex-col items-center">
          <h3 class="text-base font-bold text-gray-700 dark:text-gray-300 mb-6 text-center w-full">Resumen Calórico</h3>

          <!-- SVG Circular Progress Ring -->
          <div class="relative w-40 h-40 flex items-center justify-center mb-6">
            <svg class="absolute w-full h-full transform -rotate-90">
              <circle
                cx="80"
                cy="80"
                r="70"
                stroke="#e2e8f0"
                class="dark:stroke-gray-700"
                stroke-width="10"
                fill="transparent"
              />
              <circle
                cx="80"
                cy="80"
                r="70"
                stroke="#ef4444"
                stroke-width="10"
                fill="transparent"
                :stroke-dasharray="439.8"
                :stroke-dashoffset="dashOffset"
                stroke-linecap="round"
                class="transition-all duration-500"
              />
            </svg>
            <div class="text-center z-10">
              <p class="text-3xl font-black text-gray-900 dark:text-white font-mono">
                {{ diario.calorias }}
              </p>
              <p class="text-[10px] text-gray-500 dark:text-gray-400 uppercase tracking-widest font-bold">Consumidas</p>
              <p class="text-xs text-gray-400 dark:text-gray-500 font-mono mt-1">Límite: {{ dailyCalorieGoal }}</p>
            </div>
          </div>

          <!-- Macros Progress Bars -->
          <div class="w-full space-y-4 border-t border-gray-100 dark:border-gray-700 pt-4">
            <div class="space-y-1">
              <div class="flex justify-between text-xs font-semibold">
                <span class="text-red-500">🥩 Proteínas</span>
                <span class="font-mono text-gray-600 dark:text-gray-400">{{ diario.proteinas }}g</span>
              </div>
              <div class="h-2 w-full bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                <div class="h-full bg-red-500 rounded-full" :style="{ width: `${Math.min((diario.proteinas / 150) * 100, 100)}%` }"></div>
              </div>
            </div>

            <div class="space-y-1">
              <div class="flex justify-between text-xs font-semibold">
                <span class="text-amber-500">🍞 Carbohidratos</span>
                <span class="font-mono text-gray-600 dark:text-gray-400">{{ diario.carbohidratos }}g</span>
              </div>
              <div class="h-2 w-full bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                <div class="h-full bg-amber-500 rounded-full" :style="{ width: `${Math.min((diario.carbohidratos / 250) * 100, 100)}%` }"></div>
              </div>
            </div>

            <div class="space-y-1">
              <div class="flex justify-between text-xs font-semibold">
                <span class="text-emerald-500">🥑 Grasas</span>
                <span class="font-mono text-gray-600 dark:text-gray-400">{{ diario.grasas }}g</span>
              </div>
              <div class="h-2 w-full bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                <div class="h-full bg-emerald-500 rounded-full" :style="{ width: `${Math.min((diario.grasas / 70) * 100, 100)}%` }"></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Logging forms and Hydration -->
        <div class="md:col-span-2 space-y-8">
          <!-- Log Nutrition Form -->
          <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
            <h3 class="text-base font-bold text-gray-700 dark:text-gray-300 mb-6 flex items-center gap-2">
              <span>🍽️</span> Registrar Alimentos & Macros
            </h3>

            <form @submit.prevent="guardarNutricion" class="grid sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Calorías (kcal)</label>
                <input
                  v-model.number="form.calorias"
                  type="number"
                  min="0"
                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm"
                  placeholder="Ej: 450"
                  required
                />
              </div>

              <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Proteínas (g)</label>
                <input
                  v-model.number="form.proteinas"
                  type="number"
                  min="0"
                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm"
                  placeholder="Ej: 30"
                  required
                />
              </div>

              <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Carbohidratos (g)</label>
                <input
                  v-model.number="form.carbohidratos"
                  type="number"
                  min="0"
                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm"
                  placeholder="Ej: 50"
                  required
                />
              </div>

              <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Grasas (g)</label>
                <input
                  v-model.number="form.grasas"
                  type="number"
                  min="0"
                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm"
                  placeholder="Ej: 12"
                  required
                />
              </div>

              <div class="sm:col-span-2 pt-2">
                <button
                  type="submit"
                  :disabled="guardando"
                  class="w-full py-2.5 px-4 bg-indigo-600 text-white font-bold rounded-xl hover:bg-indigo-700 transition-all shadow-md flex items-center justify-center gap-2 text-sm"
                >
                  <span v-if="guardando">Guardando...</span>
                  <span v-else>Guardar Registro del Día</span>
                </button>
              </div>
            </form>
          </div>

          <!-- Hydration Tracker -->
          <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
            <div class="flex justify-between items-center mb-6">
              <h3 class="text-base font-bold text-gray-700 dark:text-gray-300 flex items-center gap-2">
                <span>💧</span> Hidratación Diaria
              </h3>
              <span class="text-xs font-bold font-mono text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-950/20 px-2.5 py-1 rounded-full">
                {{ diario.agua_vasos }} / 10 vasos
              </span>
            </div>

            <p class="text-xs text-gray-500 dark:text-gray-400 mb-6">Lleva un registro de los vasos de agua consumidos hoy (aprox 250ml por vaso). ¡Mantente hidratado!</p>

            <div class="flex flex-col items-center gap-6">
              <!-- Grid of glasses -->
              <div class="flex flex-wrap justify-center gap-4 max-w-sm">
                <button
                  v-for="index in 10"
                  :key="index"
                  @click="toggleVaso(index)"
                  class="w-12 h-16 rounded-b-xl border-2 border-t-0 border-blue-400/80 dark:border-blue-500 relative overflow-hidden transition-all transform active:scale-95 flex items-end shadow-inner"
                  :class="[index <= diario.agua_vasos ? 'bg-blue-100 dark:bg-blue-950/30' : 'bg-transparent']"
                  :title="index <= diario.agua_vasos ? 'Haga clic para quitar vaso' : 'Haga clic para agregar vaso'"
                  :aria-label="`Vaso ${index} de 10. ${index <= diario.agua_vasos ? 'Lleno, clic para quitar.' : 'Vacío, clic para llenar.'}`"
                  :aria-pressed="index <= diario.agua_vasos"
                >
                  <!-- Glass rim top border -->
                  <div class="absolute top-0 left-0 right-0 h-1 border-t-2 border-blue-400/80 dark:border-blue-500"></div>
                  <!-- Water Level -->
                  <div
                    v-if="index <= diario.agua_vasos"
                    class="w-full bg-gradient-to-t from-blue-500 to-cyan-400 dark:from-blue-600 dark:to-cyan-500 transition-all duration-500 flex items-center justify-center text-[10px] text-white font-bold"
                    style="height: 90%"
                  >
                    💧
                  </div>
                </button>
              </div>

              <!-- Manual adjustments buttons -->
              <div class="flex gap-4">
                <button
                  @click="cambiarAgua('decrementar')"
                  :disabled="diario.agua_vasos === 0"
                  class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl text-xs font-bold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50"
                >
                  - Quitar Vaso
                </button>
                <button
                  @click="cambiarAgua('incrementar')"
                  class="px-4 py-2 bg-blue-600 text-white rounded-xl text-xs font-bold hover:bg-blue-700 shadow-sm"
                >
                  + Agregar Vaso
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import axios from 'axios';
import { useToast } from '../composables/useToast';
import Breadcrumbs from './Breadcrumbs.vue';

const toast = useToast();
const showNotification = (message, type = 'success') => toast.add(message, type);

const fechaSeleccionada = ref(new Date().toISOString().split('T')[0]);
const dailyCalorieGoal = ref(2000); // Default daily goal
const guardando = ref(false);

const diario = ref({
  calorias: 0,
  proteinas: 0,
  carbohidratos: 0,
  grasas: 0,
  agua_vasos: 0
});

const form = ref({
  calorias: '',
  proteinas: '',
  carbohidratos: '',
  grasas: ''
});

// SVG Dash offset representation
const dashOffset = computed(() => {
  const goal = dailyCalorieGoal.value;
  const current = diario.value.calorias;
  const circumference = 439.8; // 2 * pi * radius (70)
  
  if (current >= goal) return 0;
  
  const percentage = current / goal;
  return circumference - (percentage * circumference);
});

const formatFecha = (dateStr) => {
  if (!dateStr) return '';
  const date = new Date(dateStr + 'T00:00:00');
  return date.toLocaleDateString('es-ES', {
    weekday: 'long',
    day: '2-digit',
    month: 'long',
    year: 'numeric'
  });
};

const cambiarDia = (diff) => {
  const current = new Date(fechaSeleccionada.value + 'T00:00:00');
  current.setDate(current.getDate() + diff);
  fechaSeleccionada.value = current.toISOString().split('T')[0];
};

const cargarDiario = async () => {
  try {
    const response = await axios.get('/api/nutricion', {
      params: { fecha: fechaSeleccionada.value }
    });
    diario.value = response.data;
    
    // Auto-fill form values
    form.value.calorias = response.data.calorias || '';
    form.value.proteinas = response.data.proteinas || '';
    form.value.carbohidratos = response.data.carbohidratos || '';
    form.value.grasas = response.data.grasas || '';
  } catch (error) {
    console.error('Error al cargar diario nutricional:', error);
  }
};

const guardarNutricion = async () => {
  guardando.value = true;
  try {
    const response = await axios.post('/api/nutricion', {
      fecha: fechaSeleccionada.value,
      calorias: form.value.calorias || 0,
      proteinas: form.value.proteinas || 0,
      carbohidratos: form.value.carbohidratos || 0,
      grasas: form.value.grasas || 0
    });
    
    showNotification(response.data.message || 'Registro guardado con éxito.', 'success');
    await cargarDiario();
  } catch (error) {
    console.error('Error al guardar nutrición:', error);
    showNotification('Error al guardar el registro.', 'error');
  } finally {
    guardando.value = false;
  }
};

const cambiarAgua = async (accion) => {
  try {
    const response = await axios.post('/api/nutricion/agua', {
      fecha: fechaSeleccionada.value,
      accion: accion
    });
    await cargarDiario();
  } catch (error) {
    console.error('Error al actualizar agua:', error);
    showNotification('Error al actualizar contador de agua.', 'error');
  }
};

const toggleVaso = async (vasoIndex) => {
  // If clicked a vaso that is already filled, decrement, else increment
  if (vasoIndex <= diario.value.agua_vasos) {
    await cambiarAgua('decrementar');
  } else {
    await cambiarAgua('incrementar');
  }
};

watch(fechaSeleccionada, () => {
  cargarDiario();
});

onMounted(() => {
  cargarDiario();
});
</script>

<style scoped>
/* Scrollbar removal helper */
.scrollbar-hide::-webkit-scrollbar {
  display: none;
}
.scrollbar-hide {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>
