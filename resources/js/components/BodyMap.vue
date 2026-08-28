<!--
  BodyMap — Mapa corporal interactivo con 3 vistas (balance / fatigue / strength).

  Basado en la geometría SVG del proyecto MuscleMap de Melih Colpan (MIT),
  distribuida vía openGym (frontend/src/lib/body-paths.js). Los paths son
  geométricos puros y libres de copyright creativo.

  Props:
    - gender: 'male' | 'female' (default 'male')
    - view:   'front' | 'back' (default 'front')
    - levels: { chest: 0-4, abs: 0-4, ... } (lo que devuelve useMuscleLoad)
    - mode:   'balance' | 'fatigue' | 'strength' (afecta la leyenda de color)
    - onMuscleClick: function(slug)  (opcional, para abrir detalle)

  Carga: importa el archivo de paths lazy con import() según gender+view.
  Los archivos viven en resources/js/lib/bodyPaths/{gender}-{view}.js y suman
  ~100KB total (male+female × front+back), pero solo se carga 1 a la vez.
-->
<template>
    <div class="w-full">
        <!-- Toggle de género (oculto en modo compact) -->
        <div v-if="showGenderToggle && !compact" class="flex justify-center gap-2 mb-3">
            <button
                v-for="g in ['male', 'female']"
                :key="g"
                @click="gender = g"
                :class="[
                    'px-3 py-1 text-xs font-medium rounded-lg transition-colors',
                    gender === g
                        ? 'bg-indigo-600 text-white'
                        : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-200',
                ]"
            >
                {{ g === 'male' ? 'Hombre' : 'Mujer' }}
            </button>
        </div>

        <!-- Toggle de vista front/back: visible siempre que NO este en showBothViews.
             En sidebar desktop con showBothViews=true no se ve (ya se ven los 2 lados).
             En sidebar mobile con compact=true si se ve (el user alterna manualmente). -->
        <div v-if="!showBothViews" class="flex justify-center gap-2 mb-3">
            <button
                v-for="v in ['front', 'back']"
                :key="v"
                @click="view = v"
                :class="[
                    'px-3 py-1 text-xs font-medium rounded-lg transition-colors',
                    view === v
                        ? 'bg-gray-900 text-white dark:bg-white dark:text-gray-900'
                        : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-200',
                ]"
            >
                {{ v === 'front' ? 'Frente' : 'Espalda' }}
            </button>
        </div>

        <!-- Loading state: skeleton con forma de cuerpo humano (shimmer) -->
        <div v-if="isLoading" :class="[showBothViews ? 'grid grid-cols-2 gap-2' : 'flex justify-center', 'py-4 px-2']">
            <div
                v-for="side in (showBothViews ? 2 : 1)"
                :key="side"
                class="flex flex-col items-center"
            >
                <span v-if="showBothViews" class="text-[10px] uppercase tracking-wider text-gray-300 dark:text-gray-600 mb-1 font-semibold">
                    {{ side === 1 ? 'Frente' : 'Espalda' }}
                </span>
                <!-- Silueta estilizada de cuerpo humano en gris claro con shimmer.
                     viewBox igual al de los paths reales para que no salte el layout. -->
                <svg
                    viewBox="0 0 250 600"
                    class="w-full h-auto max-w-[180px]"
                    preserveAspectRatio="xMidYMid meet"
                    aria-hidden="true"
                >
                    <!-- Cabeza -->
                    <circle cx="125" cy="50" r="30" class="fill-gray-200 dark:fill-gray-700" />
                    <!-- Cuello -->
                    <rect x="115" y="78" width="20" height="20" class="fill-gray-200 dark:fill-gray-700" />
                    <!-- Tronco -->
                    <path d="M 70 100 Q 125 95 180 100 L 195 240 Q 125 250 55 240 Z" class="fill-gray-200 dark:fill-gray-700" />
                    <!-- Brazo izq -->
                    <path d="M 70 110 L 30 130 L 15 240 L 35 250 L 55 140 Z" class="fill-gray-200 dark:fill-gray-700" />
                    <!-- Brazo der -->
                    <path d="M 180 110 L 220 130 L 235 240 L 215 250 L 195 140 Z" class="fill-gray-200 dark:fill-gray-700" />
                    <!-- Pierna izq -->
                    <path d="M 90 250 L 80 430 L 75 560 L 105 560 L 110 430 L 125 250 Z" class="fill-gray-200 dark:fill-gray-700" />
                    <!-- Pierna der -->
                    <path d="M 160 250 L 170 430 L 175 560 L 145 560 L 140 430 L 125 250 Z" class="fill-gray-200 dark:fill-gray-700" />
                    <!-- Shimmer overlay -->
                    <rect x="0" y="0" width="250" height="600" class="animate-shimmer" />
                </svg>
            </div>
        </div>

        <!-- SVG del body map -->
        <svg
            v-else-if="!showBothViews"
            :viewBox="pathsData.vb"
            class="w-full h-auto max-w-md mx-auto"
            preserveAspectRatio="xMidYMid meet"
        >
            <g v-for="(pathList, slug) in pathsData.paths" :key="slug">
                <path
                    v-for="(d, i) in pathList"
                    :key="`${slug}-${i}`"
                    :d="d"
                    :data-slug="slug"
                    :fill="fillFor(slug)"
                    :stroke="strokeFor(slug)"
                    :stroke-width="hoveredSlug === slug ? 1.5 : 0.5"
                    class="transition-all cursor-pointer"
                    @mouseenter="hoveredSlug = slug"
                    @mouseleave="hoveredSlug = null"
                    @click="$emit('muscle-click', slug)"
                />
            </g>
        </svg>

        <!-- Vista doble: frente + espalda lado a lado (modo sidebar) -->
        <div v-else class="grid grid-cols-2 gap-2">
            <div class="flex flex-col items-center">
                <span class="text-[10px] uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1 font-semibold">Frente</span>
                <BodySideView
                    v-if="frontPaths"
                    :paths-data="frontPaths"
                    :levels="props.levels"
                    :muscle-labels="props.muscleLabels"
                    :hovered-slug="hoveredSlug"
                    :is-dark="isDark"
                    @hover="hoveredSlug = $event"
                    @muscle-click="$emit('muscle-click', $event)"
                />
            </div>
            <div class="flex flex-col items-center">
                <span class="text-[10px] uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1 font-semibold">Espalda</span>
                <BodySideView
                    v-if="backPaths"
                    :paths-data="backPaths"
                    :levels="props.levels"
                    :muscle-labels="props.muscleLabels"
                    :hovered-slug="hoveredSlug"
                    :is-dark="isDark"
                    @hover="hoveredSlug = $event"
                    @muscle-click="$emit('muscle-click', $event)"
                />
            </div>
        </div>

        <!-- Leyenda de color (oculta en modo compact) -->
        <div v-if="!compact" class="mt-3 text-center text-xs text-gray-500 dark:text-gray-400">
            <slot name="legend">
                <div v-if="mode === 'balance'" class="flex items-center justify-center gap-2">
                    <span>Menor volumen</span>
                    <div class="flex gap-0.5">
                        <div v-for="i in 5" :key="i" :class="['w-5 h-3 rounded-sm', legendBgClass(i - 1)]"></div>
                    </div>
                    <span>Mayor volumen</span>
                </div>
                <div v-else-if="mode === 'fatigue'" class="flex items-center justify-center gap-3">
                    <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-sm bg-red-500"></span> Fatigado</span>
                    <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-sm bg-yellow-400"></span> Recuperando</span>
                    <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-sm bg-gray-300 dark:bg-gray-600"></span> Listo</span>
                </div>
                <div v-else-if="mode === 'strength'" class="flex items-center justify-center gap-2">
                    <span>Menor 1RM</span>
                    <div class="flex gap-0.5">
                        <div v-for="i in 5" :key="i" :class="['w-5 h-3 rounded-sm', legendBgClass(i - 1)]"></div>
                    </div>
                    <span>Mayor 1RM</span>
                </div>
            </slot>
        </div>

        <!-- Tooltip del músculo hover (oculto en modo compact) -->
        <div v-if="!compact && hoveredSlug && hoveredLabel" class="text-center mt-2 text-sm font-semibold text-gray-700 dark:text-gray-300">
            {{ hoveredLabel }}
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue';
import BodySideView from './BodySideView.vue';

const props = defineProps({
    levels: { type: Object, default: () => ({}) },
    mode: { type: String, default: 'balance' },  // 'balance' | 'fatigue' | 'strength'
    initialGender: { type: String, default: 'male' },
    initialView: { type: String, default: 'front' },
    showGenderToggle: { type: Boolean, default: false },
    muscleLabels: { type: Object, default: () => ({}) },  // { chest: 'Pecho', ... }
    // Modo compact: oculta toggles (género/vista) y leyenda para usar
    // embebido en sidebars o headers donde el chrome no entra.
    compact: { type: Boolean, default: false },
    // Mostrar frente y espalda lado a lado (solo útil en sidebars anchos).
    showBothViews: { type: Boolean, default: false },
    // Segundo set de levels (modo "comparar"). Músculos solo en este set
    // se pintan en cyan; músculos en ambos sets (levels + comparisonLevels)
    // se pintan en un color mezclado (morado).
    comparisonLevels: { type: Object, default: null },
});

defineEmits(['muscle-click']);

const gender = ref(props.initialGender);
const view = ref(props.initialView);
const pathsData = ref(null);
const hoveredSlug = ref(null);

// === Dark mode reactivo ===
// Leemos la class `dark` del <html> y observamos cambios via MutationObserver,
// así el body map se re-pinta cuando el user togglea el tema sin recargar.
const isDark = ref(typeof document !== 'undefined' && document.documentElement.classList.contains('dark'));
let darkObserver = null;
onMounted(() => {
    if (typeof document === 'undefined') return;
    darkObserver = new MutationObserver(() => {
        isDark.value = document.documentElement.classList.contains('dark');
    });
    darkObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
});
onBeforeUnmount(() => { darkObserver?.disconnect(); });

// Cargar paths según gender+view (lazy import para no inflar el bundle principal)
async function loadPaths(g, v) {
    try {
        const mod = await import(`../lib/bodyPaths/${g}-${v}.js`);
        pathsData.value = mod.default;
    } catch (e) {
        console.error(`[BodyMap] No se pudo cargar ${g}-${v}.js`, e);
        pathsData.value = { vb: '0 0 100 100', paths: {} };
    }
}

// Para el modo showBothViews: cargamos ambas vistas en paralelo.
const frontPaths = ref(null);
const backPaths = ref(null);
async function loadSidePaths(g) {
    const [f, b] = await Promise.allSettled([
        import(`../lib/bodyPaths/${g}-front.js`).then(m => m.default),
        import(`../lib/bodyPaths/${g}-back.js`).then(m => m.default),
    ]);
    frontPaths.value = f.status === 'fulfilled' ? f.value : { vb: '0 0 100 100', paths: {} };
    backPaths.value = b.status === 'fulfilled' ? b.value : { vb: '0 0 100 100', paths: {} };
}

onMounted(() => {
    if (props.showBothViews) {
        loadSidePaths(gender.value);
    } else {
        loadPaths(gender.value, view.value);
    }
});
watch([gender], (g) => {
    if (props.showBothViews) loadSidePaths(g);
});
watch([gender, view], ([g, v]) => {
    if (!props.showBothViews) loadPaths(g, v);
});

// Loading reactivo: depende del modo (single vs both views).
const isLoading = computed(() => {
    if (props.showBothViews) return !frontPaths.value || !backPaths.value;
    return !pathsData.value;
});

const hoveredLabel = computed(() => {
    if (!hoveredSlug.value) return null;
    return props.muscleLabels[hoveredSlug.value] || hoveredSlug.value;
});

/**
 * Calcula el color de fill según el level (0-4), el mode y el tema actual.
 *
 * El problema que resuelve: en light mode el fondo del sidebar es gris-claro
 * y los músculos en #1f2937 (gris-oscuro) contrastan bien. En dark mode el
 * fondo es gris-oscuro y ese mismo color de músculo desaparece. Por eso
 * invertimos la paleta en dark mode: músculos base en gris-claro, y los
 * iluminados en indigo saturado que se ve bien sobre ambos fondos.
 */
function fillFor(slug) {
    const level = props.levels[slug] || 0;

    if (props.mode === 'fatigue') {
        // En fatiga los colores rojo/amarillo ya contrastan en ambos modos.
        if (level === 4) return '#ef4444';     // rojo fatigado
        if (level === 2) return '#facc15';     // amarillo recuperando
        return isDark.value ? '#94a3b8' : '#374151';  // listo
    }

    // Modo "comparar": si el musculo esta en comparisonLevels (con su nivel),
    // pintamos en cyan. Si esta en AMBOS sets, color mezclado (morado).
    if (props.comparisonLevels && Object.keys(props.comparisonLevels).length > 0) {
        const inMain = level > 0;
        const cmpLevel = props.comparisonLevels[slug] || 0;
        const inCmp = cmpLevel > 0;
        if (inMain && inCmp) {
            // Mezcla: morado (indigo + cyan). En dark invertimos para que destaque.
            return isDark.value ? '#c084fc' : '#7c3aed';
        }
        if (inCmp) {
            // Solo en comparación: cyan. En dark un poco más claro.
            const intensity = cmpLevel >= 4 ? '#67e8f9' : cmpLevel >= 2 ? '#22d3ee' : '#06b6d4';
            return isDark.value && cmpLevel < 4 ? '#67e8f9' : intensity;
        }
        // Solo en main: cae al flujo de abajo
    }

    // balance / strength: gradiente indigo con base que se adapta al tema.
    if (isDark.value) {
        // Dark: base gris-claro para que se vea sobre fondo oscuro, e indigo
        // fuerte en level 4 para destacar.
        if (level === 0) return '#475569';     // slate-600
        if (level === 1) return '#312e81';     // indigo-900
        if (level === 2) return '#4338ca';     // indigo-700
        if (level === 3) return '#6366f1';     // indigo-500
        if (level === 4) return '#a5b4fc';     // indigo-300 (contraste alto)
        return '#475569';
    } else {
        // Light: como estaba, músculos oscuros sobre fondo claro.
        if (level === 0) return '#1f2937';     // gray-800
        if (level === 1) return '#312e81';
        if (level === 2) return '#4338ca';
        if (level === 3) return '#6366f1';
        if (level === 4) return '#818cf8';
        return '#1f2937';
    }
}

function strokeFor(slug) {
    if (hoveredSlug.value === slug) return '#fbbf24';  // amarillo al hover
    return isDark.value ? '#ffffff20' : '#00000020';
}

// Para la leyenda
function legendBgClass(level) {
    if (level === 0) return 'bg-gray-300 dark:bg-gray-600';
    if (level === 1) return 'bg-indigo-900';
    if (level === 2) return 'bg-indigo-700';
    if (level === 3) return 'bg-indigo-500';
    if (level === 4) return 'bg-indigo-300';
    return 'bg-gray-300';
}
</script>
