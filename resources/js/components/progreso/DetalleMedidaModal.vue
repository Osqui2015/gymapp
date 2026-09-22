<template>
    <Teleport to="body">
        <div
            v-if="modal.mostrar"
            class="fixed inset-0 bg-black/75 backdrop-blur-md z-50 flex items-center justify-center p-4"
            @click.self="$emit('cerrar')"
        >
            <div
                class="bg-obsidian-card border border-obsidian-border rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto animate-scaleIn text-white shadow-glow"
            >
                <div class="p-6">
                    <div
                        class="flex items-center justify-between mb-6 pb-4 border-b border-obsidian-border/70"
                    >
                        <h3
                            class="text-base font-bold font-display text-white flex items-center gap-2"
                        >
                            <span>📋</span> Detalle de Progreso:
                            {{ formatFecha(modal.progreso.fecha) }}
                        </h3>
                        <button
                            @click="$emit('cerrar')"
                            class="p-2 hover:bg-obsidian-elevated text-slate-400 hover:text-white rounded-xl transition-colors cursor-pointer"
                        >
                            <svg
                                class="w-5 h-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    </div>

                    <div class="space-y-6">
                        <!-- Datos Personales -->
                        <div
                            v-if="
                                modal.progreso.peso ||
                                modal.progreso.altura ||
                                modal.progreso.edad ||
                                modal.progreso.sexo
                            "
                            class="border-b border-obsidian-border/70 pb-4"
                        >
                            <h4
                                class="font-bold text-sm text-slate-300 mb-3 flex items-center gap-2"
                            >
                                <span>👤</span> Datos Generales
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                                <div
                                    v-if="modal.progreso.peso"
                                    class="flex justify-between items-center p-2.5 rounded-xl bg-obsidian-surface border border-obsidian-border/60"
                                >
                                    <span class="text-slate-400 text-xs">Peso:</span>
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold font-mono text-white"
                                            >{{ modal.progreso.peso }} kg</span
                                        >
                                        <span
                                            v-if="
                                                modal.comparacion.peso &&
                                                modal.comparacion.peso.diferencia !== null
                                            "
                                            :class="diffClass(modal.comparacion.peso.diferencia)"
                                        >
                                            {{ modal.comparacion.peso.diferencia > 0 ? '+' : ''
                                            }}{{ modal.comparacion.peso.diferencia }}
                                        </span>
                                    </div>
                                </div>
                                <div
                                    v-if="modal.progreso.grasa_corporal"
                                    class="flex justify-between items-center p-2.5 rounded-xl bg-obsidian-surface border border-obsidian-border/60"
                                >
                                    <span class="text-slate-400 text-xs">% Grasa:</span>
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold font-mono text-white"
                                            >{{ modal.progreso.grasa_corporal }}%</span
                                        >
                                        <span
                                            v-if="
                                                modal.comparacion.grasa_corporal &&
                                                modal.comparacion.grasa_corporal.diferencia !== null
                                            "
                                            :class="
                                                diffClass(
                                                    modal.comparacion.grasa_corporal.diferencia
                                                )
                                            "
                                        >
                                            {{
                                                modal.comparacion.grasa_corporal.diferencia > 0
                                                    ? '+'
                                                    : ''
                                            }}{{ modal.comparacion.grasa_corporal.diferencia }}%
                                        </span>
                                    </div>
                                </div>
                                <div
                                    v-if="modal.progreso.altura"
                                    class="flex justify-between items-center p-2.5 rounded-xl bg-obsidian-surface border border-obsidian-border/60"
                                >
                                    <span class="text-slate-400 text-xs">Altura:</span>
                                    <span class="font-bold font-mono text-white"
                                        >{{ modal.progreso.altura }} cm</span
                                    >
                                </div>
                                <div
                                    v-if="modal.progreso.edad"
                                    class="flex justify-between items-center p-2.5 rounded-xl bg-obsidian-surface border border-obsidian-border/60"
                                >
                                    <span class="text-slate-400 text-xs">Edad:</span>
                                    <span class="font-bold font-mono text-white"
                                        >{{ modal.progreso.edad }} años</span
                                    >
                                </div>
                                <div
                                    v-if="modal.progreso.sexo"
                                    class="flex justify-between items-center p-2.5 rounded-xl bg-obsidian-surface border border-obsidian-border/60"
                                >
                                    <span class="text-slate-400 text-xs">Sexo:</span>
                                    <span class="font-bold capitalize text-white">{{
                                        modal.progreso.sexo
                                    }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Medidas -->
                        <div>
                            <h4
                                class="font-bold text-sm text-slate-300 mb-3 flex items-center gap-2"
                            >
                                <span>📏</span> Medidas Corporales
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                                <div
                                    v-for="campo in camposMedidas"
                                    :key="campo"
                                    v-show="modal.comparacion[campo]"
                                    class="flex justify-between items-center p-2.5 rounded-xl bg-obsidian-surface border border-obsidian-border/60"
                                >
                                    <span class="text-slate-400 text-xs capitalize"
                                        >{{ labelCampos[campo] }}:</span
                                    >
                                    <div
                                        class="flex items-center gap-2"
                                        v-if="modal.comparacion[campo]"
                                    >
                                        <span class="font-bold font-mono text-white"
                                            >{{ modal.comparacion[campo].actual }} cm</span
                                        >
                                        <span
                                            v-if="modal.comparacion[campo].diferencia !== null"
                                            :class="diffClass(modal.comparacion[campo].diferencia)"
                                        >
                                            {{ modal.comparacion[campo].diferencia > 0 ? '+' : ''
                                             }}{{ modal.comparacion[campo].diferencia }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tips contextuales -->
                        <div
                            v-if="
                                modal.comparacion.cintura &&
                                modal.comparacion.cintura.diferencia < 0
                            "
                            class="p-4 bg-emerald-500/10 border border-emerald-500/30 rounded-xl"
                        >
                            <p class="text-xs text-emerald-300">
                                <strong>🎉 ¡Excelente!</strong> Tu cintura ha disminuido
                                {{ Math.abs(modal.comparacion.cintura.diferencia) }} cm. Esto indica
                                una pérdida de tejido adiposo (grasa corporal). ¡Continúa así!
                            </p>
                        </div>

                        <div
                            v-if="
                                modal.comparacion.brazos && modal.comparacion.brazos.diferencia > 0
                            "
                            class="p-4 bg-indigo-500/10 border border-indigo-500/30 rounded-xl"
                        >
                            <p class="text-xs text-indigo-300">
                                <strong>💪 ¡Excelente progresión!</strong> Tus brazos han aumentado
                                {{ modal.comparacion.brazos.diferencia }} cm. Esto sugiere una
                                ganancia de hipertrofia y masa muscular. ¡Sigue entrenando duro!
                            </p>
                        </div>
                    </div>

                    <div
                        class="mt-8 pt-4 border-t border-obsidian-border/70 flex justify-end"
                    >
                        <button
                            @click="$emit('cerrar')"
                            class="px-5 py-2.5 bg-obsidian-surface hover:bg-obsidian-elevated border border-obsidian-border text-white font-bold rounded-xl transition-colors text-sm cursor-pointer"
                        >
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
defineProps({
    modal: { type: Object, required: true },
    formatFecha: { type: Function, required: true },
});

defineEmits(['cerrar']);

const camposMedidas = [
    'cuello',
    'hombros',
    'pecho',
    'brazos',
    'cintura',
    'cadera',
    'muslos',
    'pantorrillas',
];
const labelCampos = {
    cuello: 'Cuello',
    hombros: 'Hombros',
    pecho: 'Pecho',
    brazos: 'Brazos',
    cintura: 'Cintura',
    cadera: 'Cadera',
    muslos: 'Muslos',
    pantorrillas: 'Pantorrillas',
};

const diffClass = (d) => {
    if (d > 0)
        return 'text-xs font-semibold px-2 py-0.5 rounded-full text-emerald-300 bg-emerald-500/20 border border-emerald-500/30';
    if (d < 0)
        return 'text-xs font-semibold px-2 py-0.5 rounded-full text-red-300 bg-red-500/20 border border-red-500/30';
    return 'text-xs font-semibold px-2 py-0.5 rounded-full text-slate-400 bg-obsidian-elevated border border-obsidian-border';
};
</script>
