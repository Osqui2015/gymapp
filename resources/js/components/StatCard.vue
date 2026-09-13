<!--
  StatCard — tarjeta simple para mostrar una métrica.
  Props:
    - label: string (etiqueta)
    - value: string | number (valor principal)
    - sub: string (subtítulo opcional)
    - color: 'gray' | 'indigo' | 'emerald' | 'amber' | 'red' (color del valor)
    - variant: 'default' | 'obsidian' (Kinetic Obsidian cuando es 'obsidian')
-->
<template>
    <ObsidianStat
        v-if="variant === 'obsidian'"
        :label="label"
        :value="value"
        :sub="sub"
        :accent="obsidianAccent"
    />
    <div
        v-else
        class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm"
    >
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
import ObsidianStat from './common/obsidian/ObsidianStat.vue';

const props = defineProps({
    label: { type: String, required: true },
    value: { type: [String, Number], required: true },
    sub: { type: String, default: '' },
    color: { type: String, default: 'gray' },
    variant: { type: String, default: 'default' },
});

const valueClass = computed(() => {
    return (
        {
            gray: 'text-gray-900 dark:text-white',
            indigo: 'text-indigo-600 dark:text-indigo-400',
            emerald: 'text-emerald-600 dark:text-emerald-400',
            amber: 'text-amber-600 dark:text-amber-400',
            red: 'text-red-600 dark:text-red-400',
        }[props.color] || 'text-gray-900 dark:text-white'
    );
});

// Mapear colores legacy → accent de ObsidianStat
const obsidianAccent = computed(() => {
    return (
        {
            gray: 'gray',
            indigo: 'violet',
            emerald: 'emerald',
            amber: 'amber',
            red: 'rose',
        }[props.color] || 'violet'
    );
});
</script>
