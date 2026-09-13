<template>
    <div class="obs-day-card p-4 md:p-6 mb-5 md:mb-6">
        <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
            <div class="min-w-0 flex-1">
                <p class="text-[10px] md:text-xs font-black uppercase tracking-[0.18em] obs-text-secondary mb-1.5 md:mb-2">
                    Plan actual
                </p>
                <h2 class="text-2xl md:text-4xl font-black text-gray-900 dark:text-white leading-tight tracking-tight">
                    {{ nivel }}
                </h2>
                <div class="flex flex-wrap items-center gap-2 md:gap-3 mt-3 md:mt-4 text-xs md:text-sm">
                    <span class="obs-pill obs-pill-neutral">
                        {{ dias }}
                    </span>
                    <span class="obs-pill obs-pill-emerald">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        {{ diaActual }}
                    </span>
                </div>
            </div>

            <button
                @click="$emit('cambiar')"
                class="obs-cta-secondary self-start"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                </svg>
                Cambiar rutina
            </button>
        </div>
    </div>
</template>

<script setup>
defineProps({
    nivel: { type: String, required: true },
    dias: { type: String, required: true },
    diaActual: { type: String, required: true },
});

defineEmits(['cambiar']);
</script>
