<template>
  <div
    class="w-full bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden"
    :class="sizeClass"
  >
    <div
      class="h-full rounded-full transition-all duration-700 ease-out relative overflow-hidden"
      :class="[colorClass, animate ? 'animate-progress-fill' : '', striped ? 'bg-stripes' : '']"
      :style="{ width: `${clamped}%` }"
      role="progressbar"
      :aria-valuenow="Math.round(clamped)"
      aria-valuemin="0"
      aria-valuemax="100"
    >
      <slot />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    value: { type: Number, required: true }, // 0-100
    color: { type: String, default: 'indigo' }, // indigo, emerald, amber, rose, sky
    size: { type: String, default: 'md' }, // sm, md, lg
    animate: { type: Boolean, default: true },
    striped: { type: Boolean, default: false },
});

const clamped = computed(() => Math.min(100, Math.max(0, Number(props.value) || 0)));

const colorClass = computed(() => {
    const colors = {
        indigo: 'bg-gradient-to-r from-indigo-500 to-purple-500',
        emerald: 'bg-gradient-to-r from-emerald-500 to-teal-500',
        amber: 'bg-gradient-to-r from-amber-500 to-orange-500',
        rose: 'bg-gradient-to-r from-rose-500 to-pink-500',
        sky: 'bg-gradient-to-r from-sky-500 to-cyan-500',
        red: 'bg-gradient-to-r from-red-500 to-rose-500',
    };
    return colors[props.color] || colors.indigo;
});

const sizeClass = computed(() => {
    return {
        sm: 'h-1.5',
        md: 'h-2.5',
        lg: 'h-3.5',
    }[props.size] || 'h-2.5';
});
</script>

<style scoped>
@keyframes progress-stripes {
    from { background-position: 1rem 0; }
    to   { background-position: 0 0; }
}

@keyframes progress-shine {
    0%   { box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.4); }
    50%  { box-shadow: 0 0 0 4px rgba(255, 255, 255, 0); }
    100% { box-shadow: 0 0 0 0 rgba(255, 255, 255, 0); }
}

@keyframes progress-pulse {
    0%, 100% { opacity: 1; }
    50%      { opacity: 0.85; }
}

.animate-progress-fill {
    animation: progress-shine 1.5s ease-out, progress-pulse 2.5s ease-in-out 1.5s;
}

.bg-stripes {
    background-image: linear-gradient(
        45deg,
        rgba(255, 255, 255, 0.15) 25%,
        transparent 25%,
        transparent 50%,
        rgba(255, 255, 255, 0.15) 50%,
        rgba(255, 255, 255, 0.15) 75%,
        transparent 75%,
        transparent
    );
    background-size: 1rem 1rem;
    animation: progress-stripes 0.6s linear infinite;
}
</style>
