<!--
  SkeletonLoader — Placeholder animado para estados de carga.

  Variantes:
    - "text"     → línea de texto (alto bajo, ancho variable)
    - "block"    → rectángulo (ancho y alto customizables)
    - "circle"   → círculo (avatar/iconos)
    - "card"     → card completa con shimmer

  Props:
    - variant: "text" | "block" | "circle" | "card" (default "text")
    - width: ancho CSS (default "100%")
    - height: alto CSS (default auto según variant)
    - lines: cantidad de líneas (solo "text", default 1)
    - rounded: clase de border-radius (default "rounded")
-->
<template>
    <div v-if="variant === 'card'" :class="['overflow-hidden', rounded, 'bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-sm']">
        <div class="p-4 space-y-3">
            <div :class="['skeleton-shimmer', 'h-4 w-1/3', rounded]"></div>
            <div :class="['skeleton-shimmer', 'h-3 w-full', rounded]"></div>
            <div :class="['skeleton-shimmer', 'h-3 w-2/3', rounded]"></div>
            <div class="pt-2 flex gap-2">
                <div :class="['skeleton-shimmer', 'h-8 w-20', rounded]"></div>
                <div :class="['skeleton-shimmer', 'h-8 w-20', rounded]"></div>
            </div>
        </div>
    </div>
    <div v-else class="space-y-2">
        <div
            v-for="n in (variant === 'text' ? lines : 1)"
            :key="n"
            :class="[
                'skeleton-shimmer',
                computedClasses,
                rounded,
            ]"
            :style="{
                width: variant === 'text' ? textWidth(n) : width,
                height: variant === 'circle' ? (width === '100%' ? '2.5rem' : width) : height,
            }"
        ></div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    variant: { type: String, default: 'text' }, // text | block | circle | card
    width: { type: String, default: '100%' },
    height: { type: String, default: null },
    lines: { type: Number, default: 1 },
    rounded: { type: String, default: 'rounded' },
});

// Calcula ancho variable para líneas de texto (más natural).
const textWidth = (n) => {
    if (props.lines === 1) return props.width;
    // Primera y última línea más anchas, intermedias más cortas.
    if (n === 1) return '85%';
    if (n === props.lines) return '60%';
    return '95%';
};

const computedClasses = computed(() => {
    if (props.variant === 'circle') return 'rounded-full';
    if (props.variant === 'block') return '';
    return ''; // text: alto se setea por style
});

const height = computed(() => {
    if (props.height) return props.height;
    if (props.variant === 'text') return '0.75rem';
    if (props.variant === 'block') return '4rem';
    return null;
});
</script>

<style scoped>
/* Shimmer animado: una banda clara que pasa de izq a der en loop */
.skeleton-shimmer {
    background: linear-gradient(
        90deg,
        rgba(0, 0, 0, 0.06) 0%,
        rgba(0, 0, 0, 0.12) 50%,
        rgba(0, 0, 0, 0.06) 100%
    );
    background-size: 200% 100%;
    animation: skeleton-shine 1.4s ease-in-out infinite;
}

/* En dark mode usamos tonos gris claro sobre el fondo oscuro */
:global(.dark) .skeleton-shimmer,
.dark .skeleton-shimmer {
    background: linear-gradient(
        90deg,
        rgba(255, 255, 255, 0.06) 0%,
        rgba(255, 255, 255, 0.12) 50%,
        rgba(255, 255, 255, 0.06) 100%
    );
    background-size: 200% 100%;
}

@keyframes skeleton-shine {
    0% {
        background-position: 200% 0;
    }
    100% {
        background-position: -200% 0;
    }
}
</style>
