<!--
  ObsidianExerciseRow — fila de ejercicio para el Dashboard.

  Mobile-first, estilo "lista numerada" de los mockups:
    - Círculo con número a la izquierda
    - Nombre del ejercicio + meta (musculo / barra)
    - Botón "X/Y series" arriba a la derecha
    - Lista de series debajo como pills (10 × 50kg, 8 × 55kg, etc.)

  Props:
    - index:      number (1-7, número que se muestra en el círculo)
    - name:       string (nombre del ejercicio)
    - meta:       string (info secundaria, ej "Pectoral · Barra plana")
    - target:     string opcional (objetivo, ej "Último: 60 kg")
    - seriesLabel: string (ej "0/4 series")
    - completed:  number (series completadas, color del badge)
    - total:      number (total de series)
    - series:     Array<{ label: string, done: boolean }> (las pills de cada serie)
    - expanded:   boolean (controla si se muestran las pills)
    - highlight:  boolean (resaltar cuando es el ejercicio activo)

  Emits:
    - toggle():  cuando se hace click en el header
-->
<template>
    <div
        :class="[
            'obs-card overflow-hidden transition-all',
            highlight ? 'ring-2 ring-violet-500/50 border-violet-400/40' : '',
        ]"
    >
        <button
            type="button"
            @click="$emit('toggle')"
            class="w-full flex items-center gap-3 p-3.5 md:p-4 text-left hover:bg-gray-50 dark:hover:bg-[var(--color-obsidian-overlay)] transition-colors"
        >
            <span class="obs-exercise-index" :class="doneClass">
                {{ index }}
            </span>

            <div class="flex-1 min-w-0">
                <p class="text-sm md:text-base font-bold text-gray-900 dark:text-white truncate">
                    {{ name }}
                </p>
                <p class="text-[11px] md:text-xs obs-text-secondary truncate mt-0.5">
                    {{ meta }}
                    <span v-if="target" class="obs-text-tertiary"> · {{ target }}</span>
                </p>
            </div>

            <span
                :class="[
                    'shrink-0 text-[10px] md:text-xs font-bold px-2.5 py-1 rounded-full tabular-nums',
                    isComplete
                        ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300'
                        : 'bg-violet-100 text-violet-700 dark:bg-violet-500/20 dark:text-violet-300',
                ]"
            >
                {{ seriesLabel || `${completed}/${total} series` }}
            </span>

            <svg
                :class="[
                    'shrink-0 w-4 h-4 obs-text-tertiary transition-transform',
                    expanded ? 'rotate-180' : '',
                ]"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
                aria-hidden="true"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M19 9l-7 7-7-7"
                />
            </svg>
        </button>

        <!-- Pills de series (solo si está expandido) -->
        <div
            v-if="expanded && series && series.length"
            class="px-3.5 md:px-4 pb-3.5 md:pb-4 pt-1 grid grid-cols-2 sm:grid-cols-4 gap-2 border-t border-gray-100 dark:border-[var(--color-obsidian-border)]"
        >
            <div
                v-for="(s, i) in series"
                :key="i"
                :class="[
                    'rounded-lg px-2.5 py-1.5 text-center text-[11px] md:text-xs font-bold tabular-nums',
                    s.done
                        ? 'bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-500/15 dark:text-emerald-300 dark:border-emerald-500/30'
                        : 'bg-gray-50 text-gray-700 border border-gray-200 dark:bg-[var(--color-obsidian-overlay)] dark:text-gray-300 dark:border-white/10',
                ]"
            >
                <div class="text-[9px] uppercase tracking-wider obs-text-tertiary mb-0.5">
                    S{{ i + 1 }}
                </div>
                {{ s.label }}
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    index: { type: Number, required: true },
    name: { type: String, required: true },
    meta: { type: String, default: '' },
    target: { type: String, default: '' },
    seriesLabel: { type: String, default: '' },
    completed: { type: Number, default: 0 },
    total: { type: Number, default: 0 },
    series: { type: Array, default: () => [] },
    expanded: { type: Boolean, default: false },
    highlight: { type: Boolean, default: false },
});

defineEmits(['toggle']);

const isComplete = computed(() => props.total > 0 && props.completed >= props.total);

const doneClass = computed(() => {
    if (isComplete.value) {
        return 'bg-emerald-100 text-emerald-700 border-emerald-300 dark:bg-emerald-500/20 dark:text-emerald-300 dark:border-emerald-500/40';
    }
    if (props.completed > 0) {
        return 'bg-violet-100 text-violet-700 border-violet-300 dark:bg-violet-500/20 dark:text-violet-300 dark:border-violet-500/40';
    }
    return '';
});
</script>
