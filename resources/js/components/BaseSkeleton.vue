<template>
  <transition name="skeleton-fade" appear>
    <div class="animate-pulse" :class="containerClass">
    <!-- Variante: text (1 línea por defecto) -->
    <template v-if="variant === 'text'">
      <div
        v-for="n in count"
        :key="n"
        :class="['bg-gray-200 dark:bg-gray-700 rounded', lineClass]"
        :style="n > 1 ? { width: widthFor(n) } : undefined"
      ></div>
    </template>

    <!-- Variante: card (rectángulo grande) -->
    <template v-else-if="variant === 'card'">
      <div
        v-for="n in count"
        :key="n"
        class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6 shadow-sm space-y-4"
      >
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 bg-gray-200 dark:bg-gray-700 rounded-full"></div>
          <div class="flex-1 space-y-2">
            <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/2"></div>
            <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-1/3"></div>
          </div>
        </div>
        <div class="space-y-2">
          <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded"></div>
          <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-5/6"></div>
        </div>
      </div>
    </template>

    <!-- Variante: stat-card (cuadrado con valor) -->
    <template v-else-if="variant === 'stat-card'">
      <div
        v-for="n in count"
        :key="n"
        class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm"
      >
        <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-2/3 mb-3"></div>
        <div class="h-8 bg-gray-200 dark:bg-gray-700 rounded w-1/2"></div>
      </div>
    </template>

    <!-- Variante: table (filas) -->
    <template v-else-if="variant === 'table'">
      <div
        v-for="n in count"
        :key="n"
        class="flex items-center gap-4 py-3 border-b border-gray-100 dark:border-gray-700 last:border-b-0"
      >
        <div class="w-10 h-10 bg-gray-200 dark:bg-gray-700 rounded-full flex-shrink-0"></div>
        <div class="flex-1 space-y-2">
          <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-1/3"></div>
          <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded w-1/2"></div>
        </div>
        <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-16"></div>
      </div>
    </template>

    <!-- Variante: circle (avatar) -->
    <template v-else-if="variant === 'circle'">
      <div
        v-for="n in count"
        :key="n"
        :style="{ width: size, height: size }"
        class="bg-gray-200 dark:bg-gray-700 rounded-full"
      ></div>
    </template>
  </div>
  </transition>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    variant: {
        type: String,
        default: 'text',
        validator: (v) => ['text', 'card', 'stat-card', 'table', 'circle'].includes(v),
    },
    count: { type: Number, default: 1 },
    width: { type: String, default: '100%' }, // width de la primera línea (text variant)
    size: { type: String, default: '3rem' },  // para variant=circle
    class: { type: String, default: '' },
});

const containerClass = computed(() => props.class || (props.variant === 'text' ? 'space-y-2' : 'space-y-3'));
const lineClass = computed(() => `h-${props.variant === 'text' ? '3' : '4'}`);

const widthFor = (n) => {
    // Hace que cada línea sea un poco más corta que la anterior (efecto más natural)
    const variations = ['100%', '95%', '85%', '90%', '80%'];
    return variations[(n - 1) % variations.length];
};
</script>

<style scoped>
/* Transición de entrada/salida del skeleton (fade + slight slide) */
.skeleton-fade-enter-active {
    transition: opacity 0.25s ease-out;
}
.skeleton-fade-leave-active {
    transition: opacity 0.15s ease-in;
}
.skeleton-fade-enter-from,
.skeleton-fade-leave-to {
    opacity: 0;
}
</style>
