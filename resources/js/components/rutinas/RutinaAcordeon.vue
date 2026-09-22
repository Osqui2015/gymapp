<template>
    <!-- Collapsed Routine Card -->
    <div
        v-if="!open"
        @click="$emit('toggle')"
        class="p-3.5 rounded-xl bg-obsidian-900 border border-slate-800/80 flex items-center justify-between hover:border-slate-700 transition-colors cursor-pointer select-none"
    >
        <div class="flex items-center gap-3">
            <h3 class="text-sm sm:text-base font-bold text-white">{{ modalidad.nombre }}</h3>
            <span
                class="px-2 py-0.5 rounded-full text-xs font-semibold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30"
            >
                {{ displayDiasBadge }}
            </span>
            <span
                v-if="modalidad.dias && modalidad.dias.length"
                class="hidden sm:inline-flex items-center gap-1.5 text-xs text-slate-400 font-medium"
            >
                <span>• {{ totalEjerciciosModalidad }} ejercicios</span>
                <span class="hidden md:inline">• {{ modalidad.dias.map(d => d.nombre).join(', ') }}</span>
            </span>
            <slot name="header-extra" :modalidad="modalidad" />
        </div>
        <div class="flex items-center gap-2.5">
            <!-- Favorite button -->
            <button
                type="button"
                @click.stop="$emit('toggle-favorite', { nivel, modalidad: modalidad.nombre })"
                :aria-label="esFavorita ? 'Quitar de favoritos' : 'Marcar como favorita'"
                class="transition-colors p-1"
                :class="esFavorita ? 'text-amber-400' : 'text-slate-500 hover:text-amber-400'"
            >
                <svg
                    class="w-4 h-4"
                    :class="{ 'fill-amber-400': esFavorita }"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    viewBox="0 0 24 24"
                >
                    <path
                        d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    ></path>
                </svg>
            </button>
            <div
                class="h-6 w-6 rounded-md bg-obsidian-800 flex items-center justify-center text-slate-400"
            >
                <svg
                    class="w-4 h-4"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    viewBox="0 0 24 24"
                >
                    <path
                        d="M19 9l-7 7-7-7"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    ></path>
                </svg>
            </div>
        </div>
    </div>

    <!-- Expanded Routine Card -->
    <div
        v-else
        class="rounded-2xl bg-obsidian-900 border border-indigo-900/40 overflow-hidden shadow-xl shadow-black/40 transition-all"
    >
        <!-- Card Accordion Header (Expanded) -->
        <div
            @click="$emit('toggle')"
            class="p-3.5 sm:p-4 bg-obsidian-850 border-b border-slate-800 flex items-center justify-between cursor-pointer select-none"
        >
            <div class="flex items-center gap-3">
                <h3 class="text-base sm:text-lg font-bold text-white">{{ modalidad.nombre }}</h3>
                <span
                    class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30"
                >
                    {{ displayDiasBadge }}
                </span>
                <span
                    v-if="modalidad.dias && modalidad.dias.length"
                    class="hidden sm:inline-flex items-center gap-1.5 text-xs text-slate-400 font-medium"
                >
                    <span>• {{ totalEjerciciosModalidad }} ejercicios</span>
                </span>
                <slot name="header-extra" :modalidad="modalidad" />
            </div>
            <div class="flex items-center gap-2.5">
                <!-- Favorite button -->
                <button
                    type="button"
                    @click.stop="$emit('toggle-favorite', { nivel, modalidad: modalidad.nombre })"
                    :aria-label="esFavorita ? 'Quitar de favoritos' : 'Marcar como favorita'"
                    class="text-amber-400 hover:text-amber-300 transition-colors p-1"
                >
                    <svg
                        class="w-4 h-4"
                        :class="esFavorita ? 'fill-amber-400' : 'fill-none'"
                        stroke="currentColor"
                        stroke-width="1.8"
                        viewBox="0 0 24 24"
                    >
                        <path
                            d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"
                        ></path>
                    </svg>
                </button>
                <!-- Chevron up (Expanded indicator) -->
                <div
                    class="h-6 w-6 rounded-md bg-obsidian-800 flex items-center justify-center text-slate-400"
                >
                    <svg
                        class="w-4 h-4"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        viewBox="0 0 24 24"
                    >
                        <path
                            d="M5 15l7-7 7 7"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        ></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Routine Breakdown Content -->
        <div class="p-3.5 sm:p-4 space-y-4">
            <div v-for="dia in modalidad.dias" :key="dia.nombre" class="space-y-3">
                <!-- Day is OPEN -->
                <template v-if="openDias.includes(dia.nombre)">
                    <!-- Day Header Pill/Bar (Open) -->
                    <button
                        type="button"
                        @click="$emit('toggle-dia', dia.nombre)"
                        class="w-full flex items-center justify-between px-3 py-2 rounded-xl bg-obsidian-800/80 border border-slate-700/60 text-left transition-colors hover:border-slate-600"
                    >
                        <div
                            class="flex items-center gap-2 text-indigo-300 text-xs sm:text-sm font-semibold"
                        >
                            <svg
                                class="w-4 h-4 text-indigo-400 shrink-0"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                ></path>
                            </svg>
                            <span>
                                {{ dia.nombre }}
                                <span class="text-slate-400 font-normal">({{ dia.ejercicios.length }} ejercicios)</span>
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-[11px] font-medium text-slate-400">
                                {{ getDiaFocus(dia, modalidad.nombre) }}
                            </span>
                            <svg
                                class="w-4 h-4 text-slate-400 shrink-0"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    d="M5 15l7-7 7 7"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                ></path>
                            </svg>
                        </div>
                    </button>

                    <!-- Exercise List / Table View -->
                    <div class="space-y-2">
                        <!-- List Header Columns -->
                        <div
                            class="grid grid-cols-12 px-3 text-[11px] font-semibold uppercase tracking-wider text-slate-400 pb-1"
                        >
                            <span class="col-span-5">Ejercicio</span>
                            <span class="col-span-2 text-center">Series</span>
                            <span class="col-span-2 text-center">Reps</span>
                            <span class="col-span-3 text-right">Descanso</span>
                        </div>

                        <!-- Exercise Items -->
                        <div
                            v-for="ejercicio in dia.ejercicios"
                            :key="ejercicio.id"
                            class="grid grid-cols-12 items-center p-2.5 rounded-xl bg-obsidian-850/90 border border-slate-800/70 hover:border-slate-700 transition-colors"
                            :class="getSuperserieClass(ejercicio)"
                        >
                            <div class="col-span-5 pr-1 min-w-0">
                                <span class="text-xs sm:text-sm font-semibold text-white block truncate">
                                    {{ ejercicio.ejercicio_nombre }}
                                </span>
                                <span class="text-[10px] text-slate-400 truncate block">
                                    {{ getMuscleSubtitle(ejercicio) }}
                                </span>
                                <span
                                    v-if="ejercicio.superserie_grupo"
                                    class="mt-0.5 inline-flex items-center rounded-md bg-indigo-500/20 px-1.5 py-0.5 text-[9px] font-bold text-indigo-300 border border-indigo-500/30"
                                >
                                    Superserie {{ ejercicio.superserie_grupo }}
                                </span>
                            </div>
                            <div class="col-span-2 flex justify-center">
                                <span
                                    class="h-6 w-6 rounded-md bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 flex items-center justify-center text-xs font-bold"
                                >
                                    {{ ejercicio.series }}
                                </span>
                            </div>
                            <div class="col-span-2 text-center text-xs text-slate-200 font-medium">
                                {{ formatReps(ejercicio.reps_min, ejercicio.reps_max) }}
                            </div>
                            <div class="col-span-3 text-right text-xs font-semibold text-amber-400">
                                {{ formatDescanso(ejercicio.descanso_min) }}
                            </div>
                        </div>
                    </div>

                    <!-- Slot por-dia: botones extra (ej. Importar este día) -->
                    <slot name="dia-footer" :dia="dia" :modalidad="modalidad" />

                    <!-- Quick Load Action Button for this Day -->
                    <button
                        v-if="showQuickInput"
                        type="button"
                        @click="$emit('quick-input', dia)"
                        class="w-full py-2.5 px-3 rounded-xl bg-emerald-600/20 hover:bg-emerald-600/30 border border-emerald-500/40 text-emerald-400 font-bold text-xs flex items-center justify-center gap-2 transition-all active:scale-[0.98]"
                        :aria-label="`Carga rápida de series para ${dia.nombre}`"
                    >
                        <span class="text-emerald-300 text-sm">⚡</span>
                        <span>Carga rápida de {{ dia.nombre }}</span>
                    </button>
                </template>

                <!-- Day is COLLAPSED -->
                <button
                    v-else
                    type="button"
                    @click="$emit('toggle-dia', dia.nombre)"
                    class="w-full flex items-center justify-between p-3 rounded-xl bg-obsidian-850 border border-slate-800 text-left hover:border-slate-700 transition-colors"
                >
                    <div
                        class="flex items-center gap-2 text-slate-300 text-xs sm:text-sm font-semibold"
                    >
                        <svg
                            class="w-4 h-4 text-slate-400 shrink-0"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            viewBox="0 0 24 24"
                        >
                            <path
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            ></path>
                        </svg>
                        <span>
                            {{ dia.nombre }}
                            <span class="text-slate-400 font-normal">({{ dia.ejercicios.length }} ejercicios)</span>
                        </span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-[11px] font-medium text-slate-500">
                            {{ getDiaFocus(dia, modalidad.nombre) }}
                        </span>
                        <svg
                            class="w-4 h-4 text-slate-400 shrink-0"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            viewBox="0 0 24 24"
                        >
                            <path
                                d="M19 9l-7 7-7-7"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            ></path>
                        </svg>
                    </div>
                </button>
            </div>

            <!-- Main Selection Routine Button -->
            <button
                v-if="showSelectButton"
                type="button"
                @click="$emit('select')"
                class="w-full py-3 rounded-xl bg-gradient-to-r from-purple-600 via-indigo-600 to-purple-600 text-white font-bold text-sm shadow-lg glow-purple transition-all active:scale-[0.98]"
            >
                Seleccionar {{ selectLabel || `${nivel} - ${modalidad.nombre}` }}
            </button>

            <!-- Footer slot (botones extra como Compartir/Eliminar o Importar completa) -->
            <slot name="footer" :modalidad="modalidad" />
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    modalidad: { type: Object, required: true },
    nivel: { type: String, required: true },
    open: { type: Boolean, required: true },
    openDias: { type: Array, required: true },
    titleClass: { type: String, default: 'text-white' },
    showSelectButton: { type: Boolean, default: false },
    selectLabel: { type: String, default: '' },
    showQuickInput: { type: Boolean, default: false },
});

defineEmits(['toggle', 'toggle-dia', 'select', 'quick-input', 'toggle-favorite']);

// Badge de días (ej. '2 días', '6 días', etc.)
const displayDiasBadge = computed(() => {
    if (props.modalidad?.frecuencia) return props.modalidad.frecuencia;
    if (props.nivel === 'Intermedio' && props.modalidad?.nombre === '3 Días') {
        return '6 días';
    }
    const count = props.modalidad?.dias?.length || 0;
    return `${count} días`;
});

const totalEjerciciosModalidad = computed(() => {
    return (props.modalidad?.dias || []).reduce(
        (acc, d) => acc + (d.ejercicios?.length || 0),
        0
    );
});

// El flag is_favorita se computa en el backend y se propaga a todas las filas del grupo
const esFavorita = computed(() => {
    const primerEj = props.modalidad?.dias?.[0]?.ejercicios?.[0];
    return primerEj?.is_favorita === true;
});

// Subtítulos musculares para replicar exactamente los de la captura
const muscleSubtitles = {
    'Sentadilla': 'Cuádriceps / Glúteo',
    'Press de Banca': 'Pecho / Tríceps',
    'Remo sentado': 'Espalda alta',
    'Press de hombros': 'Deltoides',
    'Curl de bíceps': 'Bíceps braquial',
    'Extensión de tríceps': 'Tríceps polea',
    'Extensión tríceps': 'Tríceps polea',
    'Elevación de talones para pantorrillas': 'Pantorrillas',
    'Elevación talones': 'Pantorrillas',
    'Plancha abdominal': 'Core isométrico',
};

const getMuscleSubtitle = (ejercicio) => {
    const name = ejercicio.ejercicio_nombre?.trim();
    if (muscleSubtitles[name]) return muscleSubtitles[name];
    if (ejercicio.ejercicio?.grupo_muscular) return ejercicio.ejercicio.grupo_muscular;
    if (ejercicio.grupo_muscular) return ejercicio.grupo_muscular;
    if (ejercicio.ejercicio?.target) return ejercicio.ejercicio.target;
    return 'General';
};

// Enfoque o etiqueta del día (ej. "Full Body A", "Torso", etc.)
const getDiaFocus = (dia, modalidadNombre) => {
    const name = dia.nombre || '';
    const match = name.match(/\((.*?)\)/);
    if (match && match[1]) return match[1];

    if (modalidadNombre?.includes('2 Días') || modalidadNombre === '2 Días') {
        return name.includes('1') ? 'Full Body A' : 'Full Body B';
    }
    if (modalidadNombre?.includes('3 Días') || modalidadNombre === '3 Días') {
        if (name.includes('1')) return 'Torso / Empuje';
        if (name.includes('2')) return 'Pierna / Jalón';
        return 'Full Body';
    }
    if (modalidadNombre?.includes('4 Días') || modalidadNombre === '4 Días') {
        if (name.includes('1')) return 'Torso A';
        if (name.includes('2')) return 'Pierna A';
        if (name.includes('3')) return 'Torso B';
        return 'Pierna B';
    }
    return '';
};

// Formato limpio de repeticiones (ej. "8 - 12", "30-60 s")
const formatReps = (min, max) => {
    if (!min && !max) return '-';
    const sMin = String(min || '');
    const sMax = String(max || '');

    if (sMin.includes('30') && sMax.includes('60')) return '30-60 s';

    const cleanMin = sMin.replace(/N\/A\s*\((.*?)\)/i, '$1').replace(/\s*seg/i, ' s');
    const cleanMax = sMax.replace(/N\/A\s*\((.*?)\)/i, '$1').replace(/\s*seg/i, ' s');

    if (!cleanMin) return cleanMax;
    if (!cleanMax) return cleanMin;
    if (cleanMin === cleanMax) return cleanMin;
    return `${cleanMin} - ${cleanMax}`;
};

// Formato de tiempo de descanso (ej. "1.50 min")
const formatDescanso = (descanso) => {
    if (!descanso && descanso !== 0) return '1.00 min';
    const num = parseFloat(descanso);
    if (isNaN(num)) return `${descanso} min`;
    return `${num.toFixed(2)} min`;
};

const getSuperserieClass = (ejercicio) => {
    const grupo = ejercicio.superserie_grupo;
    if (!grupo) return '';
    switch (grupo) {
        case 1:
            return 'border-l-2 border-indigo-500 bg-indigo-950/20';
        case 2:
            return 'border-l-2 border-emerald-500 bg-emerald-950/20';
        case 3:
            return 'border-l-2 border-pink-500 bg-pink-950/20';
        case 4:
            return 'border-l-2 border-amber-500 bg-amber-950/20';
        default:
            return 'border-l-2 border-gray-500 bg-gray-950/20';
    }
};
</script>
