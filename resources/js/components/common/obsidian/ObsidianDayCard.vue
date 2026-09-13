<!--
  ObsidianDayCard — card destacada del día de entrenamiento activo.

  Es la card grande del Dashboard que muestra "Día 1: Torso" con su
  metadata (7 ejercicios, 22 series, ~50 min) y un CTA "Comenzar
  entrenamiento". Mobile-first.

  Props:
    - dayLabel:     string (ej "Día 1: Torso")
    - exercises:    number (cantidad de ejercicios)
    - series:       number (cantidad total de series)
    - duration:     string (ej "~50 min")
    - progressPct:  number 0-100 (progreso actual de la sesión)
    - progressLabel: string (ej "0 / 22 series (0%)")
    - personalizedLabel: string opcional (ej "Personalizada 3 Días")
    - focusModeLabel:    string opcional (ej "Focus Mode")
    - ctaLabel:     string (texto del CTA principal)
    - ctaIcon:      string opcional (emoji o glifo)
-->
<template>
    <div class="obs-day-card p-4 md:p-6">
        <div class="flex items-start justify-between gap-3 mb-3 md:mb-4 flex-wrap">
            <div class="flex items-center gap-2 flex-wrap">
                <ObsidianPill v-if="personalizedLabel" variant="violet" dot>
                    {{ personalizedLabel }}
                </ObsidianPill>
                <ObsidianPill v-if="focusModeLabel" variant="emerald">
                    ⚡ {{ focusModeLabel }}
                </ObsidianPill>
            </div>
            <button
                v-if="$slots['header-action']"
                type="button"
                class="text-xs font-bold text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
            >
                <slot name="header-action" />
            </button>
        </div>

        <h2 class="text-2xl md:text-3xl font-black text-gray-900 dark:text-white leading-tight">
            {{ dayLabel }}
        </h2>

        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-2 text-xs md:text-sm">
            <span class="obs-text-secondary font-semibold">
                <span class="tabular-nums">{{ exercises }}</span> ejercicios
            </span>
            <span class="obs-text-tertiary">·</span>
            <span class="obs-text-secondary font-semibold">
                <span class="tabular-nums">{{ series }}</span> series
            </span>
            <span class="obs-text-tertiary">·</span>
            <span class="obs-text-secondary font-semibold">{{ duration }}</span>
        </div>

        <button
            v-if="ctaLabel"
            type="button"
            @click="$emit('cta')"
            class="obs-cta-primary w-full mt-4 md:mt-5 text-sm md:text-base py-3 md:py-3.5"
        >
            <span
                v-if="ctaIcon"
                class="w-7 h-7 rounded-full bg-white/20 flex items-center justify-center text-sm"
                aria-hidden="true"
            >
                {{ ctaIcon }}
            </span>
            {{ ctaLabel }}
        </button>

        <!-- Progreso de sesión -->
        <div v-if="progressLabel" class="mt-4 md:mt-5">
            <div class="flex items-center justify-between mb-1.5">
                <span class="text-[11px] md:text-xs obs-text-secondary font-semibold uppercase tracking-wider">
                    Progreso de la sesión
                </span>
                <span class="text-xs md:text-sm font-bold text-gray-900 dark:text-white tabular-nums">
                    {{ progressLabel }}
                </span>
            </div>
            <div class="obs-progress-track">
                <div
                    class="obs-progress-fill"
                    :style="{ width: progressPct + '%' }"
                    role="progressbar"
                    :aria-valuenow="progressPct"
                    aria-valuemin="0"
                    aria-valuemax="100"
                ></div>
            </div>
        </div>
    </div>
</template>

<script setup>
import ObsidianPill from './ObsidianPill.vue';

defineProps({
    dayLabel: { type: String, required: true },
    exercises: { type: Number, default: 0 },
    series: { type: Number, default: 0 },
    duration: { type: String, default: '' },
    progressPct: { type: Number, default: 0 },
    progressLabel: { type: String, default: '' },
    personalizedLabel: { type: String, default: '' },
    focusModeLabel: { type: String, default: '' },
    ctaLabel: { type: String, default: '' },
    ctaIcon: { type: String, default: '▶' },
});

defineEmits(['cta']);
</script>
