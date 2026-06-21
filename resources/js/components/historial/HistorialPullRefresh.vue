<!--
  Indicador visual de pull-to-refresh.
  Se monta arriba del contenedor scrollable y crece según `offset`.
  Cambia de "pull" a "release" cuando se cruza el threshold.
-->
<template>
    <div
        v-show="visible"
        :style="{ height: offset + 'px' }"
        class="flex items-center justify-center overflow-hidden transition-[height] duration-150"
        :aria-live="refreshing ? 'assertive' : 'polite'"
        role="status"
    >
        <div class="flex flex-col items-center gap-1 text-xs font-semibold text-gray-500 dark:text-gray-400">
            <svg
                class="w-6 h-6"
                :class="[
                    refreshing ? 'animate-spin text-indigo-600' :
                    release ? 'text-indigo-600 rotate-180' :
                    'text-gray-400',
                ]"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
                aria-hidden="true"
            >
                <path
                    v-if="!release && !refreshing"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M19 14l-7 7m0 0l-7-7m7 7V3"
                />
                <path
                    v-else
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M5 10l7-7m0 0l7 7m-7-7v18"
                />
            </svg>
            <span v-if="refreshing">Actualizando…</span>
            <span v-else-if="release">Soltá para actualizar</span>
            <span v-else>Deslizá hacia abajo</span>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    offset: { type: Number, default: 0 },
    threshold: { type: Number, default: 70 },
    refreshing: { type: Boolean, default: false },
});

const release = computed(() => props.offset >= props.threshold);
const visible = computed(() => props.offset > 4 || props.refreshing);
</script>
