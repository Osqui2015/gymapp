<template>
    <div class="space-y-1">
        <div class="flex items-center justify-between">
            <label class="text-xs font-semibold text-text-2">
                {{ label }} <span class="text-text-3 font-normal">(cm)</span>
            </label>
            <span
                v-if="hint"
                :class="badgeClass || 'text-text-3 bg-surface-2 border-border'"
                class="text-[10px] px-2 py-0.5 rounded-full border font-medium"
            >
                {{ hint }}
            </span>
        </div>
        <input
            :value="value"
            @input="onInput"
            type="number"
            step="0.1"
            class="w-full bg-obsidian-input border border-obsidian-border/80 focus:border-accent-indigo focus:ring-1 focus:ring-accent-indigo rounded-xl px-3.5 py-2.5 text-sm font-semibold text-text placeholder-text-3 transition-all outline-none"
            :placeholder="placeholder"
        />
    </div>
</template>

<script setup>
defineProps({
    value: { type: [Number, String, null], default: null },
    label: { type: String, required: true },
    hint: { type: String, default: '' },
    badgeClass: { type: String, default: '' },
    placeholder: { type: String, default: '' },
});

const emit = defineEmits(['change']);
const onInput = (e) => {
    const v = e.target.value;
    emit('change', v === '' ? '' : Number(v));
};
</script>

