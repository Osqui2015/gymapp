<template>
    <section
        v-show="activeTab === 'matrix'"
        class="px-space-lg py-space-md rounded-xl bg-surface-container-high flex flex-col sm:flex-row items-center justify-between gap-space-md shadow-sm"
    >
        <div class="flex items-center gap-space-md">
            <div class="w-3 h-3 rounded-full bg-tertiary animate-ping"></div>
            <span class="font-label-md text-label-md text-on-surface-variant">
                Sesión programada en curso
            </span>
            <span class="text-body-md text-on-surface">
                Día actual:
                <strong class="text-secondary font-bold">
                    {{ userRutina?.dia_actual || 'Día 1' }}
                </strong>
            </span>
        </div>
        <RescheduleButton
            v-if="!isTrainerOrAdmin"
            :current-day="userRutina?.dia_actual || 'Día 1'"
            @rescheduled="$emit('rescheduled', $event)"
        />
    </section>
</template>

<script setup>
import RescheduleButton from '../RescheduleButton.vue';

defineProps({
    activeTab: { type: String, default: 'matrix' },
    userRutina: { type: Object, default: null },
    isTrainerOrAdmin: { type: Boolean, default: false },
});

defineEmits(['rescheduled']);
</script>
