<template>
    <section
        v-show="activeTab === 'matrix'"
        class="grid grid-cols-1 lg:grid-cols-12 gap-space-lg"
    >
        <!-- Left Column: Streak & Activity Heatmap + Weekly Calendar (7 Columns) -->
        <div class="lg:col-span-7 flex flex-col gap-space-lg">
            <!-- Top Row: Streak Card + Activity Heatmap -->
            <div class="grid grid-cols-1 md:grid-cols-12 gap-space-md">
                <div class="md:col-span-5">
                    <StreakCard :data="statsResumen" />
                </div>
                <div class="md:col-span-7">
                    <ActivityHeatmap :data="statsHeatmap" />
                </div>
            </div>
            <!-- Bottom Row: Weekly Calendar Strip -->
            <WeekCalendar :user-id="selectedAlumnoId" />
        </div>

        <!-- Right Column: Esfuerzo (RIR / RPE Telemetry) (5 Columns) -->
        <div class="lg:col-span-5">
            <EffortCard :user-id="selectedAlumnoId" />
        </div>
    </section>
</template>

<script setup>
import StreakCard from '../StreakCard.vue';
import ActivityHeatmap from '../ActivityHeatmap.vue';
import EffortCard from '../EffortCard.vue';
import WeekCalendar from '../WeekCalendar.vue';

defineProps({
    activeTab: { type: String, default: 'matrix' },
    statsResumen: { type: Object, default: () => ({}) },
    statsHeatmap: { type: Object, default: () => ({ days: [] }) },
    selectedAlumnoId: { type: [Number, String], default: null },
});
</script>
