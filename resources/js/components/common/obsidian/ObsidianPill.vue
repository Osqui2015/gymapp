<!--
  ObsidianPill — chip/pill reutilizable del sistema Kinetic Obsidian.

  Variantes:
    - violet  → pill violeta (default, "Personalizada 3 Días", "Focus Mode")
    - emerald → pill verde ("+12% este mes", "Recomendado")
    - orange  → pill naranja (racha)
    - neutral → pill gris (estado genérico)
    - ghost   → pill transparente (botón secundario pequeño)

  Props:
    - variant: 'violet' | 'emerald' | 'orange' | 'neutral' | 'ghost'
    - icon:    string opcional (emoji o glifo corto)
    - dot:     boolean — muestra un dot a la izquierda (mejor que un emoji para estado)
-->
<template>
    <span :class="['obs-pill', variantClass]">
        <span
            v-if="dot"
            :class="['w-1.5 h-1.5 rounded-full', dotClass]"
            aria-hidden="true"
        ></span>
        <span v-else-if="icon" aria-hidden="true">{{ icon }}</span>
        <slot />
    </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    variant: {
        type: String,
        default: 'violet',
        validator: (v) => ['violet', 'emerald', 'orange', 'neutral', 'ghost'].includes(v),
    },
    icon: { type: String, default: '' },
    dot: { type: Boolean, default: false },
});

const variantClass = computed(() => {
    return (
        {
            violet: 'obs-pill-violet',
            emerald: 'obs-pill-emerald',
            orange: 'obs-pill-orange',
            neutral: 'obs-pill-neutral',
            ghost: 'obs-pill-ghost',
        }[props.variant] || 'obs-pill-violet'
    );
});

const dotClass = computed(() => {
    return (
        {
            violet: 'bg-violet-500',
            emerald: 'bg-emerald-500',
            orange: 'bg-orange-500',
            neutral: 'bg-gray-500',
            ghost: 'bg-gray-400',
        }[props.variant] || 'bg-violet-500'
    );
});
</script>
