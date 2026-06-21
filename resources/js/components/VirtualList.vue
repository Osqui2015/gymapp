<template>
  <div
    ref="containerRef"
    class="overflow-y-auto"
    :style="{ height: typeof height === 'number' ? `${height}px` : height }"
    @scroll="onScroll"
  >
    <!-- Spacer para los items no renderizados arriba -->
    <div :style="{ height: `${offsetY}px` }" />

    <!-- Items visibles -->
    <div>
      <slot
        v-for="(item, i) in visibleItems"
        :key="getKey(item, startIndex + i)"
        :item="item"
        :index="startIndex + i"
      />
    </div>

    <!-- Spacer para los items no renderizados abajo -->
    <div :style="{ height: `${totalHeight - offsetY - visibleHeight}px` }" />

    <!-- Mensaje si no hay items -->
    <div v-if="!items || items.length === 0" class="py-8 text-center text-gray-500 dark:text-gray-400">
      <slot name="empty">No hay elementos para mostrar</slot>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue';

const props = defineProps({
    items: { type: Array, required: true },
    itemHeight: { type: Number, default: 60 },
    height: { type: [String, Number], default: 400 },
    overscan: { type: Number, default: 5 }, // items extra a renderizar arriba/abajo
    keyField: { type: String, default: 'id' },
});

const containerRef = ref(null);
const scrollTop = ref(0);
const containerHeight = ref(0);
let resizeObserver = null;

const getKey = (item, index) => {
    if (typeof item === 'object' && item !== null) {
        return item[props.keyField] ?? index;
    }
    return index;
};

const startIndex = computed(() => Math.max(0, Math.floor(scrollTop.value / props.itemHeight) - props.overscan));
const visibleCount = computed(() => Math.ceil(containerHeight.value / props.itemHeight) + props.overscan * 2);
const endIndex = computed(() => Math.min(props.items.length, startIndex.value + visibleCount.value));

const visibleItems = computed(() => props.items.slice(startIndex.value, endIndex.value));

const offsetY = computed(() => startIndex.value * props.itemHeight);
const totalHeight = computed(() => props.items.length * props.itemHeight);
const visibleHeight = computed(() => (endIndex.value - startIndex.value) * props.itemHeight);

const onScroll = (e) => {
    scrollTop.value = e.target.scrollTop;
};

const measure = () => {
    if (containerRef.value) {
        containerHeight.value = containerRef.value.clientHeight;
    }
};

onMounted(() => {
    measure();
    if (typeof ResizeObserver !== 'undefined' && containerRef.value) {
        resizeObserver = new ResizeObserver(measure);
        resizeObserver.observe(containerRef.value);
    }
});

onBeforeUnmount(() => {
    if (resizeObserver) resizeObserver.disconnect();
});

watch(() => props.items.length, () => {
    if (containerRef.value) containerRef.value.scrollTop = 0;
});
</script>
