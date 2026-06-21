<template>
  <transition name="slide-fade">
    <div
      v-if="modelValue.activo"
      class="fixed bottom-20 md:bottom-6 right-6 z-50 w-80 bg-slate-900 text-white rounded-2xl shadow-2xl border border-slate-800 p-4 transform transition-all duration-300"
    >
      <div class="flex items-center justify-between mb-3">
        <div class="min-w-0">
          <p class="text-[10px] uppercase tracking-[0.2em] text-indigo-400 font-semibold">Descanso Activo</p>
          <h4 class="text-xs font-bold truncate">{{ modelValue.ejercicioNombre }}</h4>
        </div>
        <button
          @click="$emit('saltar')"
          class="text-slate-400 hover:text-white transition-colors"
          aria-label="Cerrar temporizador"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <div class="flex items-center justify-between gap-4">
        <div class="flex items-center gap-3">
          <div class="relative flex items-center justify-center w-14 h-14">
            <svg class="absolute w-full h-full transform -rotate-90">
              <circle cx="28" cy="28" r="24" stroke="#1e293b" stroke-width="3.5" fill="transparent" />
              <circle
                cx="28"
                cy="28"
                r="24"
                stroke="#6366f1"
                stroke-width="3.5"
                fill="transparent"
                :stroke-dasharray="150.796"
                :stroke-dashoffset="dashOffset"
                stroke-linecap="round"
                class="transition-all duration-300"
              />
            </svg>
            <span class="text-xs font-mono font-bold">{{ formattedRemainingTime }}</span>
          </div>
        </div>

        <div class="flex items-center gap-2 flex-1 justify-end">
          <button
            @click="$emit('pausar-reanudar')"
            class="p-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-white transition-colors"
            :title="modelValue.pausado ? 'Reanudar' : 'Pausar'"
            :aria-label="modelValue.pausado ? 'Reanudar temporizador' : 'Pausar temporizador'"
          >
            <svg v-if="modelValue.pausado" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd" />
            </svg>
            <svg v-else class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM7 8a1 1 0 012 0v4a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v4a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
          </button>
          <button
            @click="$emit('agregar-30s')"
            class="px-2.5 py-2 text-xs font-semibold rounded-xl bg-slate-800 hover:bg-slate-700 text-white transition-colors"
            title="Añadir 30s"
            aria-label="Añadir 30 segundos al temporizador"
          >
            <span>+30s</span>
          </button>
          <button
            @click="$emit('saltar')"
            class="px-2.5 py-2 text-xs font-semibold rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white transition-colors"
            title="Saltar"
            aria-label="Saltar al siguiente ejercicio"
          >
            <span>Saltar</span>
          </button>
        </div>
      </div>
    </div>
  </transition>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    modelValue: {
        type: Object,
        required: true,
        // { activo, totalSegundos, segundosRestantes, ejercicioNombre, pausado }
    },
});

defineEmits(['pausar-reanudar', 'agregar-30s', 'saltar']);

const formattedRemainingTime = computed(() => {
    const mins = Math.floor(props.modelValue.segundosRestantes / 60);
    const secs = props.modelValue.segundosRestantes % 60;
    return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
});

const dashOffset = computed(() => {
    if (props.modelValue.totalSegundos === 0) return 0;
    const circumference = 150.796;
    const ratio = props.modelValue.segundosRestantes / props.modelValue.totalSegundos;
    return circumference - ratio * circumference;
});
</script>

<style scoped>
.slide-fade-enter-active {
    transition: all 0.3s ease-out;
}
.slide-fade-leave-active {
    transition: all 0.3s cubic-bezier(1, 0.5, 0.8, 1);
}
.slide-fade-enter-from,
.slide-fade-leave-to {
    transform: translateY(20px) scale(0.95);
    opacity: 0;
}
</style>