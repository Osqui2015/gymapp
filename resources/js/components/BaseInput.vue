<template>
  <div :class="['w-full', wrapperClass]">
    <label v-if="label" :for="inputId" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
      {{ label }}
      <span v-if="required" class="text-red-500">*</span>
    </label>

    <div class="relative">
      <span v-if="$slots.prefix" class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400 pointer-events-none">
        <slot name="prefix" />
      </span>
      <span v-else-if="icon" class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400 pointer-events-none">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="icon" />
        </svg>
      </span>

      <input
        :id="inputId"
        ref="inputRef"
        :type="type"
        :value="modelValue"
        :placeholder="placeholder"
        :required="required"
        :disabled="disabled"
        :readonly="readonly"
        :min="min"
        :max="max"
        :step="step"
        :autocomplete="autocomplete"
        :inputmode="inputmode"
        :class="[
          'w-full border rounded-lg dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors',
          sizeClass,
          hasIcon ? (iconRight || clearable || $slots.suffix ? 'pl-10 pr-10' : 'pl-10') : '',
          error ? 'border-red-500 dark:border-red-500' : 'border-gray-300 dark:border-gray-600',
          disabled ? 'opacity-60 cursor-not-allowed' : '',
        ]"
        @input="onInput"
        @blur="$emit('blur', $event)"
        @focus="$emit('focus', $event)"
        @keyup.enter="$emit('enter', $event)"
      />

      <span v-if="$slots.suffix || iconRight || clearable" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400">
        <button
          v-if="clearable && modelValue && !disabled"
          @click="clear"
          type="button"
          class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
          aria-label="Limpiar"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
        <slot v-else name="suffix" />
      </span>
    </div>

    <p v-if="hint && !error" class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ hint }}</p>
    <p v-if="error" class="mt-1 text-xs text-red-500">{{ error }}</p>
  </div>
</template>

<script setup>
import { computed, ref, useSlots } from 'vue';

const props = defineProps({
    modelValue: { type: [String, Number], default: '' },
    label: { type: String, default: '' },
    type: { type: String, default: 'text' },
    placeholder: { type: String, default: '' },
    hint: { type: String, default: '' },
    error: { type: String, default: '' },
    required: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    readonly: { type: Boolean, default: false },
    clearable: { type: Boolean, default: false },
    size: {
        type: String,
        default: 'md',
        validator: (v) => ['sm', 'md', 'lg'].includes(v),
    },
    icon: { type: String, default: '' },
    iconRight: { type: String, default: '' },
    wrapperClass: { type: String, default: '' },
    min: { type: [String, Number], default: undefined },
    max: { type: [String, Number], default: undefined },
    step: { type: [String, Number], default: undefined },
    autocomplete: { type: String, default: undefined },
    inputmode: { type: String, default: undefined },
});

const emit = defineEmits(['update:modelValue', 'blur', 'focus', 'enter', 'clear']);

const inputRef = ref(null);
const slots = useSlots();
const inputId = computed(() => `base-input-${Math.random().toString(36).slice(2, 9)}`);

const sizeClass = computed(() => ({
    sm: 'px-3 py-1.5 text-sm',
    md: 'px-4 py-2 text-sm',
    lg: 'px-4 py-3 text-base',
}[props.size]));

const hasIcon = computed(() => !!(props.icon || slots.prefix || props.iconRight || slots.suffix || props.clearable));

const onInput = (e) => emit('update:modelValue', e.target.value);

const clear = () => {
    emit('update:modelValue', '');
    emit('clear');
    inputRef.value?.focus();
};
</script>
