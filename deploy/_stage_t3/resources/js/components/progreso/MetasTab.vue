<template>
    <div class="space-y-8 animate-fadeIn">
        <div class="grid md:grid-cols-3 gap-8">
            <!-- Form -->
            <div
                class="bg-obsidian-card border border-obsidian-border rounded-2xl shadow-card-border p-5 h-fit md:col-span-1 space-y-4"
            >
                <h3 class="text-base font-bold font-display text-white flex items-center gap-2">
                    <span>🎯</span> Establecer Meta
                </h3>
                <form @submit.prevent="$emit('crear', nuevaMeta)" class="space-y-4">
                    <div class="space-y-1.5">
                        <label class="block text-xs font-semibold text-slate-300">
                            Métrica Objetivo
                        </label>
                        <select
                            v-model="nuevaMeta.tipo"
                            class="w-full bg-obsidian-input border border-obsidian-border focus:border-accent-indigo focus:ring-1 focus:ring-accent-indigo rounded-xl px-3 py-2 text-sm text-white font-medium transition-all outline-none cursor-pointer"
                            required
                        >
                            <option class="bg-obsidian-card text-white" value="entrenamiento_semanal">
                                Entrenamientos Semanales (Sesiones)
                            </option>
                            <option class="bg-obsidian-card text-white" value="peso_corporal">
                                Peso Corporal (kg)
                            </option>
                            <option class="bg-obsidian-card text-white" value="cintura_corporal">
                                Medida de Cintura (cm)
                            </option>
                            <option class="bg-obsidian-card text-white" value="brazos_corporal">
                                Medida de Brazos/Bíceps (cm)
                            </option>
                            <option class="bg-obsidian-card text-white" value="pecho_corporal">
                                Medida de Pecho (cm)
                            </option>
                            <option class="bg-obsidian-card text-white" value="otro">
                                Otro
                            </option>
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-xs font-semibold text-slate-300">
                            Valor Objetivo
                        </label>
                        <input
                            v-model.number="nuevaMeta.valor_objetivo"
                            type="number"
                            step="0.01"
                            min="0.1"
                            class="w-full bg-obsidian-input border border-obsidian-border focus:border-accent-indigo focus:ring-1 focus:ring-accent-indigo rounded-xl px-3 py-2 text-sm text-white placeholder-slate-500 font-medium transition-all outline-none"
                            placeholder="Ej: 3 (entrenamientos) o 72.5 (kg)"
                            required
                        />
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-xs font-semibold text-slate-300">
                            Descripción / Notas
                        </label>
                        <input
                            v-model="nuevaMeta.descripcion"
                            type="text"
                            class="w-full bg-obsidian-input border border-obsidian-border focus:border-accent-indigo focus:ring-1 focus:ring-accent-indigo rounded-xl px-3 py-2 text-sm text-white placeholder-slate-500 font-medium transition-all outline-none"
                            placeholder="Ej: Entrenar 3 veces por semana para consistencia"
                            required
                        />
                    </div>

                    <button
                        type="submit"
                        :disabled="creandoMeta"
                        class="w-full py-2.5 px-4 bg-gradient-to-r from-accent-indigo to-accent-violet hover:opacity-95 active:scale-[0.99] text-white font-bold rounded-xl shadow-glow transition-all flex items-center justify-center gap-2 text-sm cursor-pointer disabled:opacity-50"
                    >
                        <span v-if="creandoMeta">Procesando...</span>
                        <span v-else>Establecer Objetivo</span>
                    </button>
                </form>
            </div>

            <!-- Listado -->
            <div class="md:col-span-2 space-y-4">
                <div class="flex justify-between items-center mb-2">
                    <h3 class="text-base font-bold font-display text-white">Mis Objetivos</h3>
                    <span
                        class="px-2.5 py-1 text-xs font-bold rounded-full bg-indigo-500/20 text-indigo-300 border border-indigo-500/30"
                    >
                        {{ metas.filter((m) => m.completada).length }} /
                        {{ metas.length }} completados
                    </span>
                </div>

                <div
                    v-if="metas.length === 0"
                    class="bg-obsidian-card border border-dashed border-obsidian-border rounded-2xl p-8 text-center text-slate-400"
                >
                    <span class="text-4xl block mb-2">🎯</span>
                    <p class="font-bold text-white">No has definido metas personales todavía.</p>
                    <p class="text-xs text-slate-400 mt-1">
                        Establece objetivos de peso, medidas o entrenamiento para mantenerte
                        motivado.
                    </p>
                </div>

                <div v-else class="grid sm:grid-cols-2 gap-4">
                    <article
                        v-for="meta in metas"
                        :key="meta.id"
                        class="bg-obsidian-card border border-obsidian-border rounded-2xl p-5 shadow-card-border relative overflow-hidden transition-all hover:bg-obsidian-elevated/40 flex flex-col justify-between"
                        :class="{
                            'ring-1 ring-accent-emerald/60': meta.completada,
                        }"
                    >
                        <div
                            v-if="meta.completada"
                            class="absolute top-0 right-0 bg-accent-emerald text-obsidian-canvas text-[9px] font-black uppercase px-2 py-1 rounded-bl-lg shadow-sm"
                        >
                            Alcanzada
                        </div>

                        <div>
                            <div class="flex items-center gap-2.5 mb-2">
                                <span class="text-2xl">{{ getMetaEmoji(meta.tipo) }}</span>
                                <h4
                                    class="font-bold text-sm text-white uppercase tracking-wide"
                                >
                                    {{ formatMetaTipo(meta.tipo) }}
                                </h4>
                            </div>
                            <p class="text-xs text-slate-300 mb-3">
                                {{ meta.descripcion }}
                            </p>
                            <p
                                class="text-sm font-black text-accent-indigo font-mono mb-4"
                            >
                                Objetivo: {{ parseFloat(meta.valor_objetivo) }}
                            </p>
                        </div>

                        <div class="flex gap-2 border-t border-obsidian-border/70 pt-3">
                            <button
                                @click="$emit('toggle', meta)"
                                class="flex-1 py-1.5 px-3 rounded-lg text-xs font-bold transition-all flex items-center justify-center gap-1.5 cursor-pointer"
                                :class="
                                    meta.completada
                                        ? 'bg-amber-500/20 text-amber-300 border border-amber-500/30 hover:bg-amber-500/30'
                                        : 'bg-emerald-600 hover:bg-emerald-500 text-white'
                                "
                            >
                                <span v-if="meta.completada">Reabrir</span>
                                <span v-else>✓ Lograda</span>
                            </button>
                            <button
                                @click="$emit('eliminar', meta.id)"
                                class="p-1.5 rounded-lg bg-red-500/15 border border-red-500/20 text-red-400 hover:bg-red-500/25 transition-colors cursor-pointer"
                            >
                                <svg
                                    class="w-4 h-4"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                    />
                                </svg>
                            </button>
                        </div>
                    </article>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';

defineProps({
    metas: { type: Array, required: true },
    creandoMeta: { type: Boolean, required: true },
});

defineEmits(['crear', 'toggle', 'eliminar']);

const nuevaMeta = ref({
    tipo: 'entrenamiento_semanal',
    descripcion: '',
    valor_objetivo: '',
});

const getMetaEmoji = (tipo) => {
    const emojis = {
        entrenamiento_semanal: '🏋️‍♂️',
        peso_corporal: '⚖️',
        cintura_corporal: '📏',
        brazos_corporal: '💪',
        pecho_corporal: '👕',
        otro: '🎯',
    };
    return emojis[tipo] || '🎯';
};

const formatMetaTipo = (tipo) => {
    const labels = {
        entrenamiento_semanal: 'Entrenamientos/Semana',
        peso_corporal: 'Peso Corporal (kg)',
        cintura_corporal: 'Medida Cintura (cm)',
        brazos_corporal: 'Medida Brazos (cm)',
        pecho_corporal: 'Medida Pecho (cm)',
        otro: 'Otro Objetivo',
    };
    return labels[tipo] || tipo;
};
</script>
