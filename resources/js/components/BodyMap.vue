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
        <!-- Toggle de género (opcional, default male) -->
        <div v-if="showGenderToggle" class="flex justify-center gap-2 mb-3">
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

        <!-- Toggle de vista front/back -->
        <div class="flex justify-center gap-2 mb-3">
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

        <!-- Loading state -->
        <div v-if="!pathsData" class="flex items-center justify-center py-12 text-gray-400">
            <svg class="animate-spin w-8 h-8" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
            </svg>
        </div>

        <!-- SVG del body map -->
        <svg
            v-else
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

        <!-- Leyenda de color -->
        <div class="mt-3 text-center text-xs text-gray-500 dark:text-gray-400">
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

        <!-- Tooltip del músculo hover -->
        <div v-if="hoveredSlug && hoveredLabel" class="text-center mt-2 text-sm font-semibold text-gray-700 dark:text-gray-300">
            {{ hoveredLabel }}
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';

const props = defineProps({
    levels: { type: Object, default: () => ({}) },
    mode: { type: String, default: 'balance' },  // 'balance' | 'fatigue' | 'strength'
    initialGender: { type: String, default: 'male' },
    initialView: { type: String, default: 'front' },
    showGenderToggle: { type: Boolean, default: false },
    muscleLabels: { type: Object, default: () => ({}) },  // { chest: 'Pecho', ... }
});

defineEmits(['muscle-click']);

const gender = ref(props.initialGender);
const view = ref(props.initialView);
const pathsData = ref(null);
const hoveredSlug = ref(null);

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

onMounted(() => loadPaths(gender.value, view.value));
watch([gender, view], ([g, v]) => loadPaths(g, v));

const hoveredLabel = computed(() => {
    if (!hoveredSlug.value) return null;
    return props.muscleLabels[hoveredSlug.value] || hoveredSlug.value;
});

/**
 * Calcula el color de fill según el level (0-4) y el mode.
 * - balance/strength: gradiente indigo (0 gris → 4 indigo fuerte)
 * - fatigue: rojo (fatigado) / amarillo (recuperando) / gris (listo)
 */
function fillFor(slug) {
    const level = props.levels[slug] || 0;

    if (props.mode === 'fatigue') {
        if (level === 4) return '#ef4444';     // rojo fatigado
        if (level === 2) return '#facc15';     // amarillo recuperando
        return '#374151';                       // gris oscuro (listo)
    }

    // balance o strength: gradiente indigo
    if (level === 0) return '#1f2937';          // gris oscuro (no entrenado)
    if (level === 1) return '#312e81';
    if (level === 2) return '#4338ca';
    if (level === 3) return '#6366f1';
    if (level === 4) return '#818cf8';
    return '#1f2937';
}

function strokeFor(slug) {
    if (hoveredSlug.value === slug) return '#fbbf24';  // amarillo al hover
    return '#00000020';
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
