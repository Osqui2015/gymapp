<!--
  ObsidianWeekCalendar — calendario semanal compacto (L M X J V S D).

  Mobile-first, 7 columnas con día activo resaltado. Cada celda puede tener
  un dot indicador (sesión registrada). Estilo del mockup de Historial.

  Props:
    - weekDays:  Array<{ key: string, label: string, date?: number, isToday?: boolean,
                          hasSession?: boolean, isActive?: boolean }>
                 Si no se pasa, se genera con el lunes de esta semana.
    - onSelect:  function(day) — opcional, callback al tocar un día
-->
<template>
    <div class="obs-card p-3 md:p-4">
        <div class="grid grid-cols-7 gap-1.5 md:gap-2">
            <button
                v-for="day in computedDays"
                :key="day.key"
                type="button"
                @click="onSelect?.(day)"
                :disabled="!onSelect"
                :class="[
                    'flex flex-col items-center justify-center py-2 md:py-3 rounded-xl transition-all relative',
                    day.isToday
                        ? 'bg-[var(--color-violet-primary)] text-white shadow-[0_4px_14px_var(--color-violet-glow)]'
                        : day.isActive
                          ? 'bg-violet-50 text-violet-700 border border-violet-200 dark:bg-[var(--color-violet-soft)] dark:text-violet-300 dark:border-[var(--color-violet-primary)]'
                          : 'bg-gray-50 text-gray-700 hover:bg-gray-100 dark:bg-[var(--color-obsidian-overlay)] dark:text-gray-300 dark:hover:bg-[var(--color-obsidian-elevated)]',
                    !onSelect ? 'cursor-default' : 'cursor-pointer active:scale-95',
                ]"
            >
                <span class="text-[10px] md:text-xs font-bold uppercase tracking-wide opacity-80">
                    {{ day.label }}
                </span>
                <span class="text-sm md:text-base font-black tabular-nums mt-0.5">
                    {{ day.date }}
                </span>
                <span
                    v-if="day.hasSession"
                    :class="[
                        'absolute bottom-1 w-1.5 h-1.5 rounded-full',
                        day.isToday ? 'bg-white' : 'obs-week-dot',
                    ]"
                    aria-hidden="true"
                ></span>
            </button>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    weekDays: {
        type: Array,
        default: () => [],
    },
    onSelect: {
        type: Function,
        default: null,
    },
});

// Genera L M X J V S D de la semana actual si no se pasaron weekDays.
const computedDays = computed(() => {
    if (props.weekDays && props.weekDays.length) return props.weekDays;

    const labels = ['L', 'M', 'X', 'J', 'V', 'S', 'D'];
    const today = new Date();
    // Obtener lunes de esta semana (en Argentina la semana arranca lunes)
    const dayOfWeek = (today.getDay() + 6) % 7; // 0 = lunes
    const monday = new Date(today);
    monday.setDate(today.getDate() - dayOfWeek);

    return labels.map((label, i) => {
        const d = new Date(monday);
        d.setDate(monday.getDate() + i);
        const isToday = d.toDateString() === today.toDateString();
        return {
            key: `d${i}`,
            label,
            date: d.getDate(),
            isToday,
            hasSession: false,
            isActive: false,
        };
    });
});
</script>
