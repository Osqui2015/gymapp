<template>
    <div>
        <!-- Alumno CON rutina asignada (Kinetic Obsidian) -->
        <div v-if="userRutina" class="mb-6 md:mb-8">
            <ObsidianHero
                eyebrow="TU RUTINA ASIGNADA"
                :title="`${userRutina.nivel} ${userRutina.modalidad}`"
                :subtitle="`Día actual: ${userRutina.dia_actual}`"
                class="mb-5 md:mb-7"
            >
                <template #actions>
                    <a
                        href="/dashboard"
                        class="obs-cta-secondary !bg-white/15 !text-white !border-white/20 hover:!bg-white/25"
                    >
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M6.4 4.6A1 1 0 005 6v8a1 1 0 001.6.8l5-4a1 1 0 000-1.6l-5-4a1 1 0 00-.2-.2zM14 5a1 1 0 10-2 0v10a1 1 0 102 0V5z" />
                        </svg>
                        Comenzar Entrenamiento
                    </a>
                </template>
            </ObsidianHero>

            <div class="obs-card p-4 md:p-6">
                <h3 class="text-base md:text-lg font-black text-gray-900 dark:text-white mb-4">
                    Información de tu rutina
                </h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-2.5 md:gap-3">
                    <div class="text-center p-3.5 md:p-4 rounded-xl bg-gray-50 dark:bg-[var(--color-obsidian-overlay)] border border-gray-200 dark:border-[var(--color-obsidian-border)]">
                        <p class="text-xl md:text-2xl font-black text-violet-700 dark:text-violet-300 tabular-nums">
                            {{ userRutina.nivel }}
                        </p>
                        <p class="text-xs obs-text-secondary mt-1 uppercase tracking-wider font-bold">
                            Nivel
                        </p>
                    </div>
                    <div class="text-center p-3.5 md:p-4 rounded-xl bg-gray-50 dark:bg-[var(--color-obsidian-overlay)] border border-gray-200 dark:border-[var(--color-obsidian-border)]">
                        <p class="text-xl md:text-2xl font-black text-emerald-700 dark:text-emerald-300 tabular-nums">
                            {{ userRutina.modalidad }}
                        </p>
                        <p class="text-xs obs-text-secondary mt-1 uppercase tracking-wider font-bold">
                            Frecuencia
                        </p>
                    </div>
                    <div class="text-center p-3.5 md:p-4 rounded-xl bg-gray-50 dark:bg-[var(--color-obsidian-overlay)] border border-gray-200 dark:border-[var(--color-obsidian-border)]">
                        <p class="text-xl md:text-2xl font-black text-orange-700 dark:text-orange-300 tabular-nums">
                            {{ userRutina.dia_actual }}
                        </p>
                        <p class="text-xs obs-text-secondary mt-1 uppercase tracking-wider font-bold">
                            Progreso
                        </p>
                    </div>
                    <div class="text-center p-3.5 md:p-4 rounded-xl bg-gray-50 dark:bg-[var(--color-obsidian-overlay)] border border-gray-200 dark:border-[var(--color-obsidian-border)]">
                        <p class="text-xl md:text-2xl font-black text-amber-700 dark:text-amber-300 tabular-nums">
                            Personal
                        </p>
                        <p class="text-xs obs-text-secondary mt-1 uppercase tracking-wider font-bold">
                            Asignada por
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alumno SIN rutina -->
        <div v-else class="text-center py-12 md:py-16">
            <div class="obs-card-elevated p-6 md:p-8 max-w-md mx-auto">
                <svg
                    class="w-14 h-14 md:w-16 md:h-16 text-violet-400 mx-auto mb-4"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                    />
                </svg>
                <h3 class="text-lg md:text-xl font-black text-gray-900 dark:text-white mb-2">
                    Rutina Pendiente
                </h3>
                <p class="obs-text-secondary mb-4 text-sm">
                    Tu trainer aún no te ha asignado una rutina de entrenamiento.
                </p>
                <p class="text-xs obs-text-tertiary">
                    Contacta a tu trainer para que configure tu plan de entrenamiento.
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
import ObsidianHero from '../common/obsidian/ObsidianHero.vue';

defineProps({
    userRutina: { type: Object, default: null },
});
</script>
