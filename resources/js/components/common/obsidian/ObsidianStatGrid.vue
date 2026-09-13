<!--
  ObsidianStatGrid — grid de 4 stats (2x2 en mobile, 4 columnas en desktop).

  Wrapper que muestra 4 ObsidianStat en grid responsive. Usado en Progreso
  e Historial (mockups muestran grid 2x2 mobile + 4 cols desktop).

  Props:
    - stats: Array<{ label, value, sub, trend, icon, accent }> (4 elementos)
-->
<template>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-2.5 md:gap-3">
        <ObsidianStat
            v-for="(s, i) in stats"
            :key="i"
            :label="s.label"
            :value="s.value"
            :unit="s.unit"
            :sub="s.sub"
            :trend="s.trend"
            :icon="s.icon"
            :accent="s.accent || 'violet'"
        />
    </div>
</template>

<script setup>
import ObsidianStat from './ObsidianStat.vue';

defineProps({
    stats: {
        type: Array,
        required: true,
        validator: (arr) => arr.length === 4,
    },
});
</script>
