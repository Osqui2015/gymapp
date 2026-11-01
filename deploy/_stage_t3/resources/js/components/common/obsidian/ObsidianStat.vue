<!--
  ObsidianStat — tarjeta de métrica individual del sistema Kinetic Obsidian.

  Reemplaza a StatCard con la nueva estética. Mobile-first, esquinas grandes,
  borde sutil, jerarquía tipográfica fuerte (label chiquito uppercase + valor
  grande + sub contextual).

  Props:
    - label:    string (etiqueta uppercase)
    - value:    string | number (valor principal)
    - sub:      string (subtítulo/descripción opcional)
    - trend:    string opcional ('+12%', '-2 días', etc.) — se muestra con color
                según el valor (positivo = emerald, negativo = rose)
    - icon:     string opcional (emoji o glifo para mostrar arriba a la derecha)
    - accent:   'violet' | 'emerald' | 'orange' | 'rose' | 'amber' | 'gray'
                (color del valor principal + acento lateral)
-->
<template>
    <div class="obs-card p-3.5 md:p-4 relative overflow-hidden">
        <!-- Acento lateral sutil -->
        <span
            v-if="accent !== 'gray'"
            :class="['absolute left-0 top-3 bottom-3 w-1 rounded-r-full', accentBarClass]"
            aria-hidden="true"
        ></span>

        <div class="flex items-start justify-between gap-2 mb-1.5">
            <p
                class="text-[10px] md:text-xs font-bold uppercase tracking-[0.08em] text-gray-500 dark:text-gray-400 leading-tight"
            >
                {{ label }}
            </p>
            <span
                v-if="icon"
                class="text-base md:text-lg opacity-80 leading-none"
                aria-hidden="true"
            >
                {{ icon }}
            </span>
        </div>

        <div class="flex items-baseline gap-1.5">
            <span :class="['text-2xl md:text-3xl font-black tabular-nums leading-none', valueClass]">
                {{ value }}
            </span>
            <span v-if="unit" class="text-xs font-semibold obs-text-secondary">{{ unit }}</span>
        </div>

        <div v-if="sub || trend" class="mt-1.5 flex items-center gap-1.5 flex-wrap">
            <span v-if="trend" :class="['text-xs font-bold', trendClass]">{{ trend }}</span>
            <span v-if="sub" class="text-[11px] md:text-xs obs-text-secondary leading-tight">
                {{ sub }}
            </span>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    label: { type: String, required: true },
    value: { type: [String, Number], required: true },
    unit: { type: String, default: '' },
    sub: { type: String, default: '' },
    trend: { type: String, default: '' },
    icon: { type: String, default: '' },
    accent: {
        type: String,
        default: 'violet',
        validator: (v) => ['violet', 'emerald', 'orange', 'rose', 'amber', 'gray'].includes(v),
    },
});

const valueClass = computed(() => {
    return (
        {
            violet: 'text-violet-700 dark:text-violet-300',
            emerald: 'text-emerald-700 dark:text-emerald-300',
            orange: 'text-orange-700 dark:text-orange-300',
            rose: 'text-rose-700 dark:text-rose-300',
            amber: 'text-amber-700 dark:text-amber-300',
            gray: 'text-gray-900 dark:text-white',
        }[props.accent] || 'text-gray-900 dark:text-white'
    );
});

const accentBarClass = computed(() => {
    return (
        {
            violet: 'bg-gradient-to-b from-violet-400 to-violet-600',
            emerald: 'bg-gradient-to-b from-emerald-400 to-emerald-600',
            orange: 'bg-gradient-to-b from-orange-400 to-orange-600',
            rose: 'bg-gradient-to-b from-rose-400 to-rose-600',
            amber: 'bg-gradient-to-b from-amber-400 to-amber-600',
            gray: '',
        }[props.accent] || 'bg-gradient-to-b from-violet-400 to-violet-600'
    );
});

const trendClass = computed(() => {
    if (!props.trend) return '';
    if (props.trend.startsWith('-')) return 'text-rose-600 dark:text-rose-400';
    if (props.trend.startsWith('+')) return 'text-emerald-600 dark:text-emerald-400';
    return 'text-gray-600 dark:text-gray-400';
});
</script>
