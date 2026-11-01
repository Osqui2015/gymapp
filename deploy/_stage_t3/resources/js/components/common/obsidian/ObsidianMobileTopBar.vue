<!--
  ObsidianMobileTopBar — header sticky mobile del sistema Kinetic Obsidian.

  Replica exactamente el top bar del mockup Figma:
    - Logo "GymApp" + chip racha 🔥 al lado (ej "2 DÍAS")
    - Bell de notificaciones (slot)
    - Avatar del user (slot o default fallback)

  Mobile-first. Sticky top-0 con backdrop-blur.

  Props:
    - racha: number (días consecutivos — si > 0 muestra el chip 🔥)
    - userInitials: string opcional (1-2 chars para el avatar por defecto)
-->
<template>
    <header
        class="md:hidden sticky top-0 z-30 bg-[var(--color-obsidian-base)]/85 backdrop-blur-xl border-b border-[var(--color-obsidian-border)] px-4 h-14 flex items-center justify-between gap-3"
    >
        <!-- Logo + racha -->
        <div class="flex items-center gap-2 min-w-0">
            <span class="text-base font-black text-white tracking-tight">GymApp</span>
            <ObsidianPill v-if="racha > 0" variant="orange" icon="🔥">
                {{ racha }} DÍAS
            </ObsidianPill>
        </div>

        <!-- Bell + Avatar -->
        <div class="flex items-center gap-2 shrink-0">
            <slot name="notification">
                <button
                    type="button"
                    class="w-9 h-9 rounded-full flex items-center justify-center bg-[var(--color-obsidian-elevated)] border border-[var(--color-obsidian-border)] text-gray-300 hover:bg-[var(--color-obsidian-overlay)] hover:text-white transition-colors"
                    aria-label="Notificaciones"
                >
                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                </button>
            </slot>
            <slot name="avatar">
                <div class="w-9 h-9 rounded-full bg-gradient-to-br from-[var(--color-violet-deep)] via-[var(--color-violet-primary)] to-[var(--color-violet-light)] flex items-center justify-center text-white text-sm font-black shadow-[0_2px_8px_var(--color-violet-glow)]">
                    {{ userInitials || 'U' }}
                </div>
            </slot>
        </div>
    </header>
</template>

<script setup>
import ObsidianPill from './ObsidianPill.vue';

defineProps({
    racha: { type: Number, default: 0 },
    userInitials: { type: String, default: '' },
});
</script>
