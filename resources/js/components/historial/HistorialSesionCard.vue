<template>
    <!-- Card Expandida (Estilo Card 1 del mockup) -->
    <div
        v-if="expanded"
        class="bg-[#171b26] rounded-xl p-3.5 sm:p-4 flex flex-col gap-3 shadow-md border border-slate-800/60"
    >
        <!-- Header con fecha y toggler -->
        <div class="flex items-start justify-between gap-2">
            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2 flex-wrap mb-1">
                    <span
                        v-if="fechaHighlight"
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-[#8083ff] text-[#0d0096] text-xs font-bold"
                    >
                        {{ fechaHighlight }}
                    </span>
                    <span class="text-sm font-bold text-white font-display">
                        {{ fechaLabel || fecha }}
                    </span>
                </div>
                <p class="text-xs text-[#c7c4d7] flex items-center gap-2 flex-wrap">
                    <span v-if="duracion" class="inline-flex items-center gap-1">
                        <span class="material-symbols-outlined text-[14px]">schedule</span>
                        {{ duracion }}
                    </span>
                    <span v-if="duracion && series" class="text-slate-600">•</span>
                    <span v-if="series" class="inline-flex items-center gap-1">
                        <span class="material-symbols-outlined text-[14px]">fitness_center</span>
                        {{ series }} series
                    </span>
                    <span v-if="tonelaje" class="text-slate-600">•</span>
                    <span v-if="tonelaje">{{ tonelaje }}</span>
                </p>
            </div>
            <button
                type="button"
                @click="$emit('toggle')"
                class="w-8 h-8 rounded-full bg-[#262a35] flex items-center justify-center text-[#c7c4d7] hover:text-white hover:bg-[#313540] transition-colors shrink-0"
                aria-label="Colapsar sesión"
            >
                <span class="material-symbols-outlined text-[20px]">expand_less</span>
            </button>
        </div>

        <!-- Lista de ejercicios de la sesión -->
        <div v-if="ejercicios && ejercicios.length" class="flex flex-col gap-2 mt-0.5">
            <div
                v-for="(ej, i) in ejercicios"
                :key="i"
                class="bg-[#1c1f2a] p-3 rounded-lg flex flex-col gap-1.5"
            >
                <div class="flex items-center justify-between gap-2">
                    <div class="flex items-center gap-1.5 min-w-0">
                        <span class="text-sm font-bold text-white truncate">
                            {{ ej.nombre }}
                        </span>
                        <span
                            v-if="ej.prBadge"
                            class="inline-flex items-center gap-0.5 bg-[#571bc1] text-[#c4abff] px-1.5 py-0.5 rounded text-[10px] font-bold shrink-0"
                        >
                            ⭐ PR {{ ej.prBadge }}
                        </span>
                    </div>
                    <span class="text-xs font-bold text-[#c0c1ff] shrink-0">
                        {{ ej.cantSeries }} {{ ej.cantSeries === 1 ? 'serie' : 'series' }}
                    </span>
                </div>

                <!-- Chips de series -->
                <div v-if="ej.sets && ej.sets.length" class="flex items-center gap-1.5 flex-wrap">
                    <span
                        v-for="(s, idx) in ej.sets"
                        :key="idx"
                        :class="[
                            'px-2 py-0.5 rounded text-[11px] font-medium transition-colors',
                            s.esPR || (s.label && s.label.includes('RIR 0'))
                                ? 'bg-[#262a35] text-[#4edea3] font-bold border border-[#4edea3]/30'
                                : 'bg-[#262a35] text-white'
                        ]"
                    >
                        {{ s.label }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Footer con botones de acción -->
        <div v-if="showActions" class="pt-1 flex items-center justify-between gap-2">
            <button
                type="button"
                @click="$emit('verDetalles')"
                class="flex-1 py-2 px-3 rounded-lg bg-[#262a35] hover:bg-[#313540] text-white text-xs font-semibold flex items-center justify-center gap-1.5 transition-colors active:scale-95"
            >
                <span class="material-symbols-outlined text-[16px]">visibility</span>
                <span>Detalles completos</span>
            </button>
            <button
                type="button"
                @click="$emit('repetirSesion')"
                class="flex-1 py-2 px-3 rounded-lg bg-[#c0c1ff] text-[#1000a9] text-xs font-bold flex items-center justify-center gap-1.5 hover:opacity-90 active:scale-98 transition-all shadow-sm"
            >
                <span class="material-symbols-outlined text-[16px]">replay</span>
                <span>Repetir sesión</span>
            </button>
        </div>
    </div>

    <!-- Card Compacta (Estilo Card 2 del mockup para sesiones previas) -->
    <div
        v-else
        @click="$emit('toggle')"
        class="bg-[#171b26] rounded-xl p-3.5 sm:p-4 flex items-center justify-between gap-3 shadow-sm border border-slate-800/40 hover:bg-[#1c202d] transition-colors cursor-pointer group"
    >
        <div class="min-w-0 flex-1">
            <div class="flex items-center gap-2 mb-1">
                <span class="text-xs text-[#c7c4d7] uppercase font-semibold font-display">
                    {{ fechaHighlight || fecha }}
                </span>
                <span class="text-sm font-bold text-white">
                    {{ fechaLabel || 'Día de entrenamiento' }}
                </span>
            </div>
            <p class="text-xs text-[#c7c4d7] truncate">
                {{ series }} series finalizadas • {{ tonelaje }} <span v-if="rirProm">• RIR prom: {{ rirProm }}</span>
            </p>
        </div>
        <button
            type="button"
            class="w-8 h-8 rounded-full bg-[#262a35] group-hover:bg-[#313540] flex items-center justify-center text-[#c7c4d7] group-hover:text-white transition-colors shrink-0"
            aria-label="Expandir sesión"
        >
            <span class="material-symbols-outlined text-[20px]">chevron_right</span>
        </button>
    </div>
</template>

<script setup>
defineProps({
    fecha: { type: String, required: true },
    fechaHighlight: { type: String, default: '' },
    fechaLabel: { type: String, default: '' },
    duracion: { type: String, default: '' },
    series: { type: [Number, String], default: 0 },
    tonelaje: { type: String, default: '' },
    rirProm: { type: [String, Number], default: '' },
    ejercicios: { type: Array, default: () => [] },
    expanded: { type: Boolean, default: true },
    showActions: { type: Boolean, default: true },
});

defineEmits(['toggle', 'verDetalles', 'repetirSesion']);
</script>

