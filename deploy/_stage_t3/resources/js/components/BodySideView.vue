<!--
  BodySideView — Renderiza UN solo lado (front O back) del body map.
  Usado por BodyMap.vue en modo showBothViews (sidebar).

  En vez de un componente "vivo" con su propio watcher/state, este es
  un presentacional puro: recibe pathsData y emite hover/click. La
  paleta de colores la decide el padre (BodyMap.vue) pasándola ya
  computada para evitar duplicar la lógica de dark mode.
-->
<template>
    <svg
        v-if="pathsData"
        :viewBox="pathsData.vb"
        class="w-full h-auto"
        preserveAspectRatio="xMidYMid meet"
    >
        <g v-for="(pathList, slug) in pathsData.paths" :key="slug">
            <path
                v-for="(d, i) in pathList"
                :key="`${slug}-${i}`"
                :d="d"
                :data-slug="slug"
                :fill="fillFor(slug)"
                :stroke="strokeFor(slug)"
                :stroke-width="hoveredSlug === slug ? 1.5 : 0.5"
                class="transition-all cursor-pointer"
                @mouseenter="$emit('hover', slug)"
                @mouseleave="$emit('hover', null)"
                @click="$emit('muscle-click', slug)"
            />
        </g>
    </svg>
</template>

<script setup>
const props = defineProps({
    pathsData: { type: Object, required: true },
    levels: { type: Object, default: () => ({}) },
    muscleLabels: { type: Object, default: () => ({}) },
    hoveredSlug: { type: String, default: null },
    isDark: { type: Boolean, default: false },
});

defineEmits(['hover', 'muscle-click']);

function fillFor(slug) {
    const level = props.levels[slug] || 0;
    if (props.isDark) {
        if (level === 0) return '#475569';
        if (level === 1) return '#312e81';
        if (level === 2) return '#4338ca';
        if (level === 3) return '#6366f1';
        if (level === 4) return '#a5b4fc';
        return '#475569';
    } else {
        if (level === 0) return '#1f2937';
        if (level === 1) return '#312e81';
        if (level === 2) return '#4338ca';
        if (level === 3) return '#6366f1';
        if (level === 4) return '#818cf8';
        return '#1f2937';
    }
}

function strokeFor(slug) {
    if (props.hoveredSlug === slug) return '#fbbf24';
    return props.isDark ? '#ffffff20' : '#00000020';
}
</script>
