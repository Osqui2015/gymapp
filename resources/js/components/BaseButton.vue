<template>
  <button
    :type="type"
    :disabled="disabled || loading"
    :class="['inline-flex items-center justify-center gap-2 font-semibold rounded-lg transition-all', variantClass, sizeClass, block ? 'w-full' : '', loading ? 'opacity-70 cursor-wait' : '']"
    v-bind="$attrs"
  >
    <svg v-if="loading" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
    </svg>
    <svg v-else-if="icon && iconPosition === 'left'" :class="iconSizeClass" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="icon" />
    </svg>
    <slot />
    <svg v-if="!loading && icon && iconPosition === 'right'" :class="iconSizeClass" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="icon" />
    </svg>
  </button>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    variant: {
        type: String,
        default: 'primary',
        validator: (v) => ['primary', 'secondary', 'success', 'danger', 'warning', 'ghost', 'outline'].includes(v),
    },
    size: {
        type: String,
        default: 'md',
        validator: (v) => ['xs', 'sm', 'md', 'lg', 'xl'].includes(v),
    },
    type: { type: String, default: 'button' },
    icon: { type: String, default: '' },
    iconPosition: {
        type: String,
        default: 'left',
        validator: (v) => ['left', 'right'].includes(v),
    },
    loading: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    block: { type: Boolean, default: false },
});

const variantClass = computed(() => ({
    primary: 'bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm',
    secondary: 'bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200',
    success: 'bg-green-600 hover:bg-green-700 text-white shadow-sm',
    danger: 'bg-red-600 hover:bg-red-700 text-white shadow-sm',
    warning: 'bg-amber-500 hover:bg-amber-600 text-white shadow-sm',
    ghost: 'hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200',
    outline: 'border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200',
}[props.variant]));

const sizeClass = computed(() => ({
    xs: 'text-xs px-2 py-1',
    sm: 'text-sm px-3 py-1.5',
    md: 'text-sm px-4 py-2',
    lg: 'text-base px-5 py-2.5',
    xl: 'text-base px-6 py-3',
}[props.size]));

const iconSizeClass = computed(() => ({
    xs: 'w-3 h-3',
    sm: 'w-3.5 h-3.5',
    md: 'w-4 h-4',
    lg: 'w-5 h-5',
    xl: 'w-5 h-5',
}[props.size]));

defineOptions({ inheritAttrs: false });
</script>
