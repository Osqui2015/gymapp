<!--
  StatCard — tarjeta simple para mostrar una métrica.
  Props:
    - label: string (etiqueta)
    - value: string | number (valor principal)
    - sub: string (subtítulo opcional)
    - color: 'gray' | 'indigo' | 'emerald' | 'amber' | 'red' (color del valor)
-->
<template>
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
            {{ label }}
        </p>
        <p :class="['text-3xl font-bold mt-1', valueClass]">
            {{ value }}
        </p>
        <p v-if="sub" class="text-xs text-gray-500 dark:text-gray-400 mt-1">
            {{ sub }}
        </p>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    label: { type: String, required: true },
    value: { type: [String, Number], required: true },
    sub: { type: String, default: '' },
    color: { type: String, default: 'gray' },
});

const valueClass = computed(() => {
    return {
        gray: 'text-gray-900 dark:text-white',
        indigo: 'text-indigo-600 dark:text-indigo-400',
        emerald: 'text-emerald-600 dark:text-emerald-400',
        amber: 'text-amber-600 dark:text-amber-400',
        red: 'text-red-600 dark:text-red-400',
    }[props.color] || 'text-gray-900 dark:text-white';
});
</script>
