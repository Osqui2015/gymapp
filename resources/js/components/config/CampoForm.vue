<template>
  <div>
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ label }}</label>
    <input
      :value="modelValue"
      @input="$emit('update:modelValue', $event.target.value)"
      :type="type"
      :required="required"
      :minlength="minlength"
      :placeholder="placeholder"
      class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
    />
  </div>
</template>

<script setup>
defineProps({
    modelValue: { type: [String, Number], default: '' },
    label: { type: String, required: true },
    type: { type: String, default: 'text' },
    required: { type: Boolean, default: false },
    minlength: { type: [String, Number], default: undefined },
    placeholder: { type: String, default: '' },
});

defineEmits(['update:modelValue']);
</script>
