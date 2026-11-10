<script setup>
defineEmits(['start-workout', 'change-routine']);
</script>

<template>
    <div class="flex items-center gap-space-md">
        <button
            type="button"
            @click="emit('start-workout')"
            class="group flex items-center gap-2 px-space-md py-space-sm rounded-full bg-surface-container-high hover:bg-surface-bright text-on-surface transition-all duration-200"
        >
            <span
                class="material-symbols-outlined text-secondary text-[18px] group-hover:scale-110 transition-transform"
                >bolt</span
            >
            <span class="font-label-md text-label-md">Focus Mode</span>
        </button>
        <button
            type="button"
            @click="emit('change-routine')"
            class="flex items-center gap-1.5 text-secondary hover:text-on-secondary-container font-label-md text-label-md transition-colors px-space-sm py-space-sm"
        >
            <span class="material-symbols-outlined text-[18px]"
                >swap_horiz</span
            >
            <span>Cambiar rutina</span>
        </button>
    </div>
</template>
