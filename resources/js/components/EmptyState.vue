<template>
  <div class="text-center py-10 px-6">
    <!-- Ilustración / emoji -->
    <div
      v-if="icon || emoji"
      class="mx-auto mb-5 flex items-center justify-center"
      :class="iconWrapperClass"
    >
      <svg v-if="icon" :class="iconClass" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="icon" />
      </svg>
      <span v-else-if="emoji" class="text-6xl">{{ emoji }}</span>
    </div>

    <!-- Título -->
    <h3 class="text-lg md:text-xl font-bold text-gray-900 dark:text-white mb-2">
      <slot name="title">{{ title }}</slot>
    </h3>

    <!-- Descripción -->
    <p v-if="$slots.description || description" class="text-sm md:text-base text-gray-500 dark:text-gray-400 max-w-md mx-auto mb-6">
      <slot name="description">{{ description }}</slot>
    </p>

    <!-- CTA principal -->
    <div v-if="$slots.cta || ctaText" class="flex justify-center">
      <button
        v-if="!$slots.cta"
        @click="$emit('cta')"
        type="button"
        class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg transition-all shadow-md hover:shadow-lg"
      >
        <svg v-if="ctaIcon" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="ctaIcon" />
        </svg>
        {{ ctaText }}
      </button>
      <slot v-else name="cta" />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    title: { type: String, default: '' },
    description: { type: String, default: '' },
    icon: { type: String, default: '' },      // SVG path
    emoji: { type: String, default: '' },     // alternativa emoji
    ctaText: { type: String, default: '' },
    ctaIcon: { type: String, default: '' },
    variant: { type: String, default: 'default' }, // default, compact, large
});

defineEmits(['cta']);

const iconWrapperClass = computed(() => {
    if (props.variant === 'large') return 'w-24 h-24 rounded-full bg-gradient-to-br from-indigo-100 to-purple-100 dark:from-indigo-950/50 dark:to-purple-950/50';
    if (props.variant === 'compact') return 'w-12 h-12 rounded-full bg-gray-100 dark:bg-gray-800';
    return 'w-20 h-20 rounded-full bg-gradient-to-br from-indigo-100 to-purple-100 dark:from-indigo-950/50 dark:to-purple-950/50';
});

const iconClass = computed(() => {
    if (props.variant === 'large') return 'w-12 h-12 text-indigo-600 dark:text-indigo-400';
    if (props.variant === 'compact') return 'w-6 h-6 text-gray-400';
    return 'w-10 h-10 text-indigo-600 dark:text-indigo-400';
});
</script>
