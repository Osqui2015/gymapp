<template>
  <div>
    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
      {{ label }} (cm)
      <span v-if="hint" class="text-[10px] text-gray-400">({{ hint }})</span>
    </label>
    <input
      :value="value"
      @input="onInput"
      type="number"
      step="0.1"
      class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm"
      :placeholder="placeholder"
    />
  </div>
</template>

<script setup>
defineProps({
    value: { type: [Number, String, null], default: null },
    label: { type: String, required: true },
    hint: { type: String, default: '' },
    placeholder: { type: String, default: '' },
});

const emit = defineEmits(['change']);
const onInput = (e) => {
    const v = e.target.value;
    emit('change', v === '' ? '' : Number(v));
};
</script>
