<!--
  ObsidianHero — header hero de página con gradiente violeta Kinetic Obsidian.

  Mobile-first. Es el shell visual que aparece arriba de cada pantalla principal
  (Dashboard, Progreso, Historial, Rutinas). Tiene:
    - Subtítulo uppercase (eyebrow)
    - Título grande bold
    - Descripción (opcional)
    - Slot para CTAs a la derecha (en desktop) o abajo (en mobile)
    - Slots header-extra / footer para personalizaciones

  Props:
    - eyebrow:    string (etiqueta corta, ej "ANÁLITICA CORPORAL")
    - title:      string (título principal)
    - subtitle:   string opcional (descripción corta)
    - icon:       string opcional (emoji o glifo grande, se muestra a la izquierda)
-->
<template>
    <header class="obs-hero mb-5 md:mb-7">
        <div class="relative z-10">
            <div class="flex items-start gap-3 md:gap-5">
                <!-- Icono grande (opcional) -->
                <div
                    v-if="icon"
                    class="hidden md:flex shrink-0 w-14 h-14 rounded-2xl bg-white/15 backdrop-blur-sm items-center justify-center text-2xl border border-white/20"
                    aria-hidden="true"
                >
                    {{ icon }}
                </div>

                <div class="flex-1 min-w-0">
                    <p
                        v-if="eyebrow"
                        class="text-[10px] md:text-xs font-bold uppercase tracking-[0.18em] text-white/70 mb-1.5 md:mb-2"
                    >
                        {{ eyebrow }}
                    </p>
                    <h1 class="text-2xl md:text-4xl font-black leading-tight tracking-tight">
                        {{ title }}
                    </h1>
                    <p
                        v-if="subtitle"
                        class="mt-1.5 md:mt-2 text-sm md:text-base text-white/80 max-w-2xl leading-snug"
                    >
                        {{ subtitle }}
                    </p>
                    <slot name="header-extra" />
                </div>

                <!-- Acciones (derecha en desktop, abajo en mobile) -->
                <div
                    v-if="$slots.actions"
                    class="flex flex-col md:flex-row items-stretch md:items-center gap-2 shrink-0"
                >
                    <slot name="actions" />
                </div>
            </div>

            <slot name="footer" />
        </div>
    </header>
</template>

<script setup>
defineProps({
    eyebrow: { type: String, default: '' },
    title: { type: String, required: true },
    subtitle: { type: String, default: '' },
    icon: { type: String, default: '' },
});
</script>
