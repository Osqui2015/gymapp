<!--
  HistorialSesionCard — card de sesión del historial con la lista de ejercicios.

  Replica exactamente el card expandible del mockup Figma de Historial:
    - Header con fecha destacada + día de rutina (eyebrow)
    - Metadata (duración · series · tonelaje)
    - Lista de ejercicios con nombre + meta + PR badge (si tiene) + cant series
    - Pills de series (10 × 50kg, 8 × 55kg, etc.) — pill verde si es PR
    - Footer con botones "Detalles completos" / "Repetir sesión" (CTA violeta)

  Props:
    - fecha: string (ej "Ayer, 23 Oct" o "21 OCTUBRE")
    - fechaLabel: string opcional (subtítulo, ej "Día 1 (Torso)")
    - duracion: string (ej "52 min")
    - series: number|string
    - tonelaje: string opcional (ej "4.8 t total")
    - ejercicios: Array<{
          nombre, meta, sets: [{label, esPR?}], cantSeries, esActual?
      }>
    - expanded: boolean (default true)
    - isActive: boolean (si es la sesión actual/en curso)
    - prCount: number (PRs superados en la sesión)

  Emits:
    - toggle(): cuando se hace click en el header
    - verDetalles(): botón "Detalles completos"
    - repetirSesion(): botón "Repetir sesión"
-->
<template>
    <div class="obs-card-elevated overflow-hidden">
        <!-- Header clickable -->
        <button
            type="button"
            @click="$emit('toggle')"
            class="w-full px-4 md:px-5 py-3.5 md:py-4 flex items-center justify-between gap-3 hover:bg-[var(--color-obsidian-overlay)] transition-colors text-left"
        >
            <div class="min-w-0">
                <div class="flex items-center gap-2 mb-1">
                    <span
                        v-if="fechaHighlight"
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-violet-500/20 text-violet-300 text-[10px] font-black uppercase tracking-wider border border-violet-500/30"
                    >
                        {{ fechaHighlight }}
                    </span>
                    <p class="text-base md:text-lg font-black text-white truncate">
                        {{ fecha }}
                    </p>
                </div>
                <div class="flex items-center gap-2 text-[11px] md:text-xs obs-text-secondary">
                    <span v-if="duracion" class="flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        {{ duracion }}
                    </span>
                    <span v-if="duracion && series" class="obs-text-tertiary">·</span>
                    <span v-if="series">
                        💪 <strong class="text-white font-bold tabular-nums">{{ series }}</strong> series
                    </span>
                    <span v-if="series && tonelaje" class="obs-text-tertiary">·</span>
                    <span v-if="tonelaje">
                        <strong class="text-white font-bold tabular-nums">{{ tonelaje }}</strong>
                    </span>
                </div>
            </div>
            <svg
                :class="['w-5 h-5 obs-text-tertiary transition-transform shrink-0', expanded ? 'rotate-180' : '']"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
                aria-hidden="true"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <!-- Lista de ejercicios (solo si expanded) -->
        <div
            v-if="expanded && ejercicios && ejercicios.length"
            class="border-t border-[var(--color-obsidian-border)] divide-y divide-[var(--color-obsidian-border)]"
        >
            <div
                v-for="(ej, i) in ejercicios"
                :key="i"
                class="px-4 md:px-5 py-3.5 space-y-2"
            >
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0 flex-1">
                        <p class="text-sm md:text-base font-bold text-white truncate">
                            {{ ej.nombre }}
                        </p>
                        <p class="text-[11px] obs-text-secondary mt-0.5 truncate">
                            {{ ej.meta }}
                        </p>
                    </div>
                    <div class="shrink-0 flex items-center gap-1.5">
                        <span
                            v-if="ej.prBadge"
                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-amber-500/15 text-amber-300 text-[10px] font-black border border-amber-500/30"
                        >
                            ⭐ PR {{ ej.prBadge }}
                        </span>
                        <span class="obs-pill obs-pill-neutral">
                            {{ ej.cantSeries }} {{ ej.cantSeries === 1 ? 'serie' : 'series' }}
                        </span>
                    </div>
                </div>
                <!-- Pills de series -->
                <div
                    v-if="ej.sets && ej.sets.length"
                    class="flex flex-wrap gap-1.5"
                >
                    <span
                        v-for="(s, idx) in ej.sets"
                        :key="idx"
                        :class="[
                            'inline-block rounded-lg px-2.5 py-1 text-[11px] font-bold tabular-nums',
                            s.esPR
                                ? 'bg-amber-500/15 text-amber-300 border border-amber-500/30'
                                : 'bg-[var(--color-obsidian-surface)] text-gray-300 border border-[var(--color-obsidian-border)]',
                        ]"
                    >
                        {{ s.label }}
                    </span>
                </div>
            </div>

            <!-- Footer con botones -->
            <div
                v-if="showActions"
                class="px-4 md:px-5 py-3 bg-[var(--color-obsidian-surface)] border-t border-[var(--color-obsidian-border)] flex items-center gap-2"
            >
                <button
                    type="button"
                    @click="$emit('verDetalles')"
                    class="flex-1 obs-cta-secondary text-xs"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    Detalles completos
                </button>
                <button
                    type="button"
                    @click="$emit('repetirSesion')"
                    class="flex-1 inline-flex items-center justify-center gap-2 rounded-2xl px-5 py-2.5 text-xs font-bold bg-[var(--color-violet-primary)] text-white hover:bg-[var(--color-violet-light)] active:scale-[0.98] shadow-[0_8px_24px_var(--color-violet-glow)] transition-all"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Repetir sesión
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    fecha: { type: String, required: true },
    fechaHighlight: { type: String, default: '' },
    duracion: { type: String, default: '' },
    series: { type: [Number, String], default: 0 },
    tonelaje: { type: String, default: '' },
    ejercicios: { type: Array, default: () => [] },
    expanded: { type: Boolean, default: true },
    showActions: { type: Boolean, default: true },
});

defineEmits(['toggle', 'verDetalles', 'repetirSesion']);
</script>
