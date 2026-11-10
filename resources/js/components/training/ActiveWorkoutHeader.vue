<!--
    ActiveWorkoutHeader.vue

    Top bar del ActiveWorkoutModal: botón minimizar, día + rutina, cronómetro,
    pausa/reanudar y botón finalizar.

    Props:
      - dia: día de la sesión
      - rutinaNombre: nombre de la rutina
      - formattedTime: cronómetro ya formateado (HH:MM:SS / MM:SS)
      - isPaused: si la sesión está en pausa

    Emite:
      - minimize
      - toggle-pause
      - finish
-->
<template>
    <header
        class="px-3 py-2 sm:px-4 sm:py-2.5 bg-[var(--color-obsidian-surface)]/95 border-b border-[var(--color-obsidian-border)] flex items-center justify-between gap-2.5 backdrop-blur-xl shrink-0"
    >
        <div class="flex items-center gap-2 sm:gap-3 min-w-0">
            <button
                type="button"
                @click="$emit('minimize')"
                class="w-8 h-8 sm:w-9 sm:h-9 rounded-full bg-[var(--color-obsidian-elevated)] hover:bg-[var(--color-obsidian-overlay)] text-gray-300 hover:text-white transition-colors cursor-pointer border border-[var(--color-obsidian-border)] flex items-center justify-center shrink-0"
                aria-label="Minimizar sesión"
                title="Volver / Minimizar (continúa en segundo plano)"
            >
                <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M15 19l-7-7 7-7"
                    />
                </svg>
            </button>

            <div class="min-w-0">
                <p
                    class="text-[10px] font-black uppercase tracking-[0.16em] text-violet-300 truncate flex items-center gap-1.5"
                >
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    {{ dia }} · {{ rutinaNombre }}
                </p>
                <div class="flex items-center gap-2 mt-0.5">
                    <span class="text-xl sm:text-2xl font-mono font-black tracking-tight tabular-nums">
                        {{ formattedTime }}
                    </span>
                    <span v-if="isPaused" class="obs-pill obs-pill-orange">
                        ⏸ Pausa
                    </span>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <!-- Botón Pausa / Reanudar -->
            <button
                type="button"
                @click="$emit('toggle-pause')"
                class="p-2 sm:p-2.5 rounded-xl border border-[var(--color-obsidian-border-strong)] bg-[var(--color-obsidian-elevated)] hover:bg-[var(--color-obsidian-overlay)] text-gray-200 transition-all cursor-pointer"
                :title="isPaused ? 'Reanudar cronómetro' : 'Pausar cronómetro'"
                :aria-label="isPaused ? 'Reanudar' : 'Pausar'"
            >
                <svg
                    v-if="!isPaused"
                    class="w-4 h-4 sm:w-5 sm:h-5 text-amber-400"
                    fill="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z" />
                </svg>
                <svg
                    v-else
                    class="w-4 h-4 sm:w-5 sm:h-5 text-emerald-400"
                    fill="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path d="M8 5v14l11-7z" />
                </svg>
            </button>

            <!-- Botón Finalizar -->
            <button
                type="button"
                @click="$emit('finish')"
                class="px-3 sm:px-4 py-2 sm:py-2.5 rounded-xl bg-gradient-to-br from-[var(--color-violet-deep)] via-[var(--color-violet-primary)] to-[var(--color-violet-light)] hover:brightness-110 text-white text-xs font-black shadow-[0_8px_24px_var(--color-violet-glow)] transition-all cursor-pointer active:scale-95 flex items-center gap-1.5"
            >
                <svg class="w-3.5 h-3.5 fill-current shrink-0" viewBox="0 0 24 24">
                    <rect x="5" y="5" width="14" height="14" rx="2.5" />
                </svg>
                <span>Finalizar</span>
            </button>
        </div>
    </header>
</template>

<script setup>
defineProps({
    dia: {
        type: String,
        default: '',
    },
    rutinaNombre: {
        type: String,
        default: '',
    },
    formattedTime: {
        type: String,
        default: '00:00',
    },
    isPaused: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['minimize', 'toggle-pause', 'finish']);
</script>
