<!--
  EmptyStateIllustrated — versión mejorada con SVGs personalizados.

  Variantes predefinidas (illustrations):
    - 'no-data'      → sin datos aún
    - 'no-results'   → búsqueda sin resultados
    - 'no-rutinas'   → sin rutinas creadas
    - 'no-historial' → sin historial
    - 'no-ejercicios'→ catálogo vacío
    - 'no-notifs'    → sin notificaciones
    - 'no-alumnos'   → trainer sin alumnos
    - 'error'        → algo salió mal
    - 'welcome'      → onboarding / primera vez

  Uso:
    <EmptyStateIllustrated
      variant="no-rutinas"
      title="No tenés rutinas todavía"
      description="Creá tu primera rutina para empezar a entrenar."
      cta-text="Crear rutina"
      @cta="..."
    />
-->
<template>
    <div class="flex flex-col items-center justify-center text-center py-10 px-6">
        <!-- Ilustración SVG -->
        <div class="mb-6 max-w-xs">
            <component :is="illustration" />
        </div>

        <!-- Título y descripción -->
        <h3 class="text-lg md:text-xl font-bold text-gray-900 dark:text-white mb-2">
            {{ title }}
        </h3>
        <p v-if="description" class="text-sm md:text-base text-gray-500 dark:text-gray-400 max-w-md mx-auto mb-6">
            {{ description }}
        </p>

        <!-- CTA -->
        <div v-if="$slots.cta || ctaText" class="flex justify-center">
            <slot name="cta">
                <button
                    @click="$emit('cta')"
                    type="button"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg transition-all shadow-md hover:shadow-lg"
                >
                    <svg v-if="ctaIcon" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="ctaIcon" />
                    </svg>
                    {{ ctaText }}
                </button>
            </slot>
        </div>
    </div>
</template>

<script setup>
import { computed, h } from 'vue';

const props = defineProps({
    variant: {
        type: String,
        default: 'no-data',
        validator: (v) => [
            'no-data',
            'no-results',
            'no-rutinas',
            'no-historial',
            'no-ejercicios',
            'no-notifs',
            'no-alumnos',
            'error',
            'welcome',
        ].includes(v),
    },
    title: { type: String, required: true },
    description: { type: String, default: '' },
    ctaText: { type: String, default: '' },
    ctaIcon: { type: String, default: '' },
});

defineEmits(['cta']);

// === Ilustraciones SVG ===
// Cada una es un componente render-function. Más rico que un simple emoji,
// y se ve consistente con el resto del design system.

const ICON_PROPS = () => ({
    width: '160',
    height: '120',
    viewBox: '0 0 200 150',
    xmlns: 'http://www.w3.org/2000/svg',
    fill: 'none',
    class: 'mx-auto',
});

const ILLUSTRATIONS = {
    'no-data': () => h('svg', ICON_PROPS(), [
        h('defs', {}, [
            h('linearGradient', { id: 'empty-grad-1', x1: '0', y1: '0', x2: '1', y2: '1' }, [
                h('stop', { offset: '0%', 'stop-color': '#c7d2fe' }),
                h('stop', { offset: '100%', 'stop-color': '#a78bfa' }),
            ]),
        ]),
        h('rect', { x: '40', y: '50', width: '120', height: '80', rx: '12', fill: 'url(#empty-grad-1)', opacity: '0.3' }),
        h('circle', { cx: '100', cy: '40', r: '16', fill: '#a78bfa' }),
        h('path', { d: 'M85 40 Q100 25 115 40', stroke: '#7c3aed', 'stroke-width': '2', 'stroke-linecap': 'round' }),
        h('line', { x1: '60', y1: '80', x2: '140', y2: '80', stroke: '#a78bfa', 'stroke-width': '3', 'stroke-linecap': 'round' }),
        h('line', { x1: '60', y1: '95', x2: '120', y2: '95', stroke: '#a78bfa', 'stroke-width': '3', 'stroke-linecap': 'round', opacity: '0.6' }),
        h('line', { x1: '60', y1: '110', x2: '100', y2: '110', stroke: '#a78bfa', 'stroke-width': '3', 'stroke-linecap': 'round', opacity: '0.3' }),
    ]),

    'no-results': () => h('svg', ICON_PROPS(), [
        h('defs', {}, [
            h('linearGradient', { id: 'search-grad', x1: '0', y1: '0', x2: '1', y2: '1' }, [
                h('stop', { offset: '0%', 'stop-color': '#fde68a' }),
                h('stop', { offset: '100%', 'stop-color': '#fb923c' }),
            ]),
        ]),
        h('circle', { cx: '90', cy: '70', r: '32', fill: 'url(#search-grad)', opacity: '0.4' }),
        h('circle', { cx: '90', cy: '70', r: '32', stroke: '#f59e0b', 'stroke-width': '4', fill: 'none' }),
        h('line', { x1: '113', y1: '93', x2: '140', y2: '120', stroke: '#f59e0b', 'stroke-width': '6', 'stroke-linecap': 'round' }),
        h('text', { x: '90', y: '78', 'text-anchor': 'middle', 'font-size': '24', 'font-weight': 'bold', fill: '#d97706' }, '?'),
    ]),

    'no-rutinas': () => h('svg', ICON_PROPS(), [
        h('defs', {}, [
            h('linearGradient', { id: 'rutina-grad', x1: '0', y1: '0', x2: '1', y2: '1' }, [
                h('stop', { offset: '0%', 'stop-color': '#5eead4' }),
                h('stop', { offset: '100%', 'stop-color': '#3b82f6' }),
            ]),
        ]),
        // Pesas (mancuerna)
        h('rect', { x: '50', y: '70', width: '100', height: '10', rx: '5', fill: 'url(#rutina-grad)' }),
        h('rect', { x: '40', y: '55', width: '20', height: '40', rx: '6', fill: '#3b82f6' }),
        h('rect', { x: '140', y: '55', width: '20', height: '40', rx: '6', fill: '#3b82f6' }),
        // líneas de "repeticiones" animadas
        h('path', { d: 'M30 95 Q50 85 70 95 Q90 105 110 95 Q130 85 150 95', stroke: '#5eead4', 'stroke-width': '2', fill: 'none', opacity: '0.5' }),
        // Sparkle
        h('circle', { cx: '160', cy: '40', r: '3', fill: '#5eead4' }),
        h('circle', { cx: '40', cy: '30', r: '2', fill: '#3b82f6' }),
    ]),

    'no-historial': () => h('svg', ICON_PROPS(), [
        h('defs', {}, [
            h('linearGradient', { id: 'hist-grad', x1: '0', y1: '0', x2: '1', y2: '1' }, [
                h('stop', { offset: '0%', 'stop-color': '#fdba74' }),
                h('stop', { offset: '100%', 'stop-color': '#dc2626' }),
            ]),
        ]),
        // Calendario
        h('rect', { x: '40', y: '40', width: '120', height: '90', rx: '8', fill: 'url(#hist-grad)', opacity: '0.3' }),
        h('rect', { x: '40', y: '40', width: '120', height: '20', fill: '#dc2626' }),
        h('line', { x1: '60', y1: '32', x2: '60', y2: '48', stroke: '#dc2626', 'stroke-width': '4', 'stroke-linecap': 'round' }),
        h('line', { x1: '140', y1: '32', x2: '140', y2: '48', stroke: '#dc2626', 'stroke-width': '4', 'stroke-linecap': 'round' }),
        // días del calendario (algunos con check)
        h('rect', { x: '50', y: '70', width: '20', height: '14', rx: '2', fill: '#fdba74' }),
        h('rect', { x: '75', y: '70', width: '20', height: '14', rx: '2', fill: '#fdba74' }),
        h('rect', { x: '100', y: '70', width: '20', height: '14', rx: '2', fill: 'white' }),
        h('rect', { x: '125', y: '70', width: '20', height: '14', rx: '2', fill: 'white' }),
        h('rect', { x: '50', y: '90', width: '20', height: '14', rx: '2', fill: 'white' }),
        h('rect', { x: '75', y: '90', width: '20', height: '14', rx: '2', fill: 'white' }),
        h('rect', { x: '100', y: '90', width: '20', height: '14', rx: '2', fill: 'white' }),
        h('rect', { x: '125', y: '90', width: '20', height: '14', rx: '2', fill: 'white' }),
        h('rect', { x: '50', y: '110', width: '20', height: '14', rx: '2', fill: 'white' }),
        h('rect', { x: '75', y: '110', width: '20', height: '14', rx: '2', fill: 'white' }),
    ]),

    'no-ejercicios': () => h('svg', ICON_PROPS(), [
        h('defs', {}, [
            h('linearGradient', { id: 'ej-grad', x1: '0', y1: '0', x2: '1', y2: '1' }, [
                h('stop', { offset: '0%', 'stop-color': '#86efac' }),
                h('stop', { offset: '100%', 'stop-color': '#10b981' }),
            ]),
        ]),
        // Lista con +
        h('rect', { x: '40', y: '30', width: '100', height: '14', rx: '3', fill: '#10b981', opacity: '0.3' }),
        h('rect', { x: '40', y: '55', width: '100', height: '14', rx: '3', fill: '#10b981', opacity: '0.3' }),
        h('rect', { x: '40', y: '80', width: '100', height: '14', rx: '3', fill: '#10b981', opacity: '0.3' }),
        // +
        h('circle', { cx: '155', cy: '115', r: '20', fill: 'url(#ej-grad)' }),
        h('line', { x1: '145', y1: '115', x2: '165', y2: '115', stroke: 'white', 'stroke-width': '3', 'stroke-linecap': 'round' }),
        h('line', { x1: '155', y1: '105', x2: '155', y2: '125', stroke: 'white', 'stroke-width': '3', 'stroke-linecap': 'round' }),
    ]),

    'no-notifs': () => h('svg', ICON_PROPS(), [
        // Campana
        h('path', { d: 'M70 90 Q70 60 100 55 Q130 60 130 90 L140 110 L60 110 Z', fill: '#a5b4fc' }),
        h('rect', { x: '90', y: '115', width: '20', height: '6', rx: '3', fill: '#6366f1' }),
        h('circle', { cx: '140', cy: '40', r: '4', fill: '#fbbf24' }),
        // ZZZ
        h('text', { x: '155', y: '40', 'font-size': '12', fill: '#fbbf24', 'font-weight': 'bold' }, 'z'),
        h('text', { x: '162', y: '32', 'font-size': '9', fill: '#fbbf24', 'font-weight': 'bold' }, 'z'),
    ]),

    'no-alumnos': () => h('svg', ICON_PROPS(), [
        h('circle', { cx: '70', cy: '60', r: '15', fill: '#c4b5fd' }),
        h('path', { d: 'M50 95 Q50 80 70 80 Q90 80 90 95 L90 110 L50 110 Z', fill: '#a78bfa' }),
        h('circle', { cx: '130', cy: '60', r: '15', fill: '#c4b5fd', opacity: '0.5' }),
        h('path', { d: 'M110 95 Q110 80 130 80 Q150 80 150 95 L150 110 L110 110 Z', fill: '#a78bfa', opacity: '0.5' }),
        // +
        h('circle', { cx: '100', cy: '135', r: '12', fill: '#8b5cf6' }),
        h('line', { x1: '93', y1: '135', x2: '107', y2: '135', stroke: 'white', 'stroke-width': '2', 'stroke-linecap': 'round' }),
        h('line', { x1: '100', y1: '128', x2: '100', y2: '142', stroke: 'white', 'stroke-width': '2', 'stroke-linecap': 'round' }),
    ]),

    'error': () => h('svg', ICON_PROPS(), [
        h('circle', { cx: '100', cy: '75', r: '50', fill: '#fecaca', opacity: '0.4' }),
        h('circle', { cx: '100', cy: '75', r: '50', stroke: '#ef4444', 'stroke-width': '4', fill: 'none' }),
        h('line', { x1: '100', y1: '55', x2: '100', y2: '85', stroke: '#ef4444', 'stroke-width': '5', 'stroke-linecap': 'round' }),
        h('circle', { cx: '100', cy: '100', r: '3', fill: '#ef4444' }),
    ]),

    'welcome': () => h('svg', ICON_PROPS(), [
        h('defs', {}, [
            h('linearGradient', { id: 'welcome-grad', x1: '0', y1: '0', x2: '1', y2: '1' }, [
                h('stop', { offset: '0%', 'stop-color': '#fbbf24' }),
                h('stop', { offset: '100%', 'stop-color': '#f97316' }),
            ]),
        ]),
        // Mano saludando
        h('circle', { cx: '100', cy: '80', r: '30', fill: 'url(#welcome-grad)', opacity: '0.3' }),
        h('path', { d: 'M85 60 Q100 45 115 60 L115 80 L100 70 L85 80 Z', fill: '#f59e0b' }),
        h('circle', { cx: '100', cy: '85', r: '8', fill: '#fbbf24' }),
        // estrellas / sparkles
        h('text', { x: '40', y: '50', 'font-size': '18', fill: '#fbbf24' }, '✦'),
        h('text', { x: '160', y: '45', 'font-size': '14', fill: '#f97316' }, '✦'),
        h('text', { x: '170', y: '110', 'font-size': '12', fill: '#fbbf24' }, '✦'),
    ]),
};

const illustration = computed(() => ILLUSTRATIONS[props.variant] || ILLUSTRATIONS['no-data']);
</script>
