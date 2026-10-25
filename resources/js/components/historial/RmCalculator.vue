<template>
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Calculadora Manual (4 cols) -->
        <section
            class="lg:col-span-4 bg-[#131924] border border-[#232D42] rounded-xl shadow-lg overflow-hidden h-fit"
        >
            <div class="p-4 bg-[#18202F]/60 border-b border-[#232D42] flex items-center gap-2">
                <div class="w-2.5 h-2.5 rounded bg-indigo-500"></div>
                <h3 class="text-sm font-bold text-white tracking-wide uppercase">
                    Calculadora de 1RM
                </h3>
            </div>

            <div class="p-5 space-y-4">
                <div>
                    <label class="block text-[11px] font-medium text-slate-400 uppercase tracking-wider mb-1">
                        Peso Levantado (kg)
                    </label>
                    <div class="flex items-center gap-1.5">
                        <button
                            type="button"
                            @click="weight = Math.max(0.5, Number((Number(weight || 0) - 2.5).toFixed(2)))"
                            class="h-10 px-2.5 rounded-lg bg-[#101521] border border-[#232D42] text-xs font-mono font-bold text-slate-300 hover:text-white active:scale-95 transition-all cursor-pointer"
                        >
                            -2.5
                        </button>
                        <input
                            v-model.number="weight"
                            type="number"
                            inputmode="decimal"
                            min="0.1"
                            step="0.5"
                            class="w-full h-10 bg-[#18202F] border border-[#232D42] text-sm font-mono font-bold text-center text-white rounded-lg px-3 py-2 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 transition-all"
                        />
                        <button
                            type="button"
                            @click="weight = Number((Number(weight || 0) + 2.5).toFixed(2))"
                            class="h-10 px-2.5 rounded-lg bg-indigo-600/20 border border-indigo-500/40 text-xs font-mono font-bold text-indigo-300 hover:bg-indigo-600/30 active:scale-95 transition-all cursor-pointer"
                        >
                            +2.5
                        </button>
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-medium text-slate-400 uppercase tracking-wider mb-1">
                        Repeticiones Realizadas
                    </label>
                    <div class="flex items-center gap-1.5">
                        <button
                            type="button"
                            @click="reps = Math.max(1, (parseInt(reps, 10) || 1) - 1)"
                            class="h-10 px-3 rounded-lg bg-[#101521] border border-[#232D42] text-xs font-mono font-bold text-slate-300 hover:text-white active:scale-95 transition-all cursor-pointer"
                        >
                            -1
                        </button>
                        <input
                            v-model.number="reps"
                            type="number"
                            inputmode="numeric"
                            min="1"
                            max="30"
                            class="w-full h-10 bg-[#18202F] border border-[#232D42] text-sm font-mono font-bold text-center text-white rounded-lg px-3 py-2 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 transition-all"
                        />
                        <button
                            type="button"
                            @click="reps = Math.min(30, (parseInt(reps, 10) || 0) + 1)"
                            class="h-10 px-3 rounded-lg bg-emerald-500/20 border border-emerald-500/40 text-xs font-mono font-bold text-emerald-300 hover:bg-emerald-500/30 active:scale-95 transition-all cursor-pointer"
                        >
                            +1
                        </button>
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-medium text-slate-400 uppercase tracking-wider mb-1">
                        Fórmula de Estimación
                    </label>
                    <div class="grid grid-cols-2 gap-2">
                        <button
                            type="button"
                            @click="formula = 'epley'"
                            class="py-2 text-xs font-semibold rounded-lg border transition-all cursor-pointer"
                            :class="
                                formula === 'epley'
                                    ? 'bg-indigo-600 text-white border-indigo-500 shadow-md shadow-indigo-600/30'
                                    : 'bg-[#18202F] text-slate-400 hover:text-white border-[#232D42]'
                            "
                        >
                            Epley
                        </button>
                        <button
                            type="button"
                            @click="formula = 'lander'"
                            class="py-2 text-xs font-semibold rounded-lg border transition-all cursor-pointer"
                            :class="
                                formula === 'lander'
                                    ? 'bg-indigo-600 text-white border-indigo-500 shadow-md shadow-indigo-600/30'
                                    : 'bg-[#18202F] text-slate-400 hover:text-white border-[#232D42]'
                            "
                        >
                            Lander
                        </button>
                    </div>
                </div>

                <!-- Output -->
                <div
                    class="mt-4 p-4 bg-[#0B0F17]/80 border border-indigo-500/30 rounded-xl text-center relative overflow-hidden"
                >
                    <div class="absolute -right-6 -top-6 w-20 h-20 rounded-full bg-indigo-500/15 blur-xl pointer-events-none"></div>
                    <p class="text-[11px] text-indigo-400 uppercase tracking-widest font-bold">
                        1RM Estimado
                    </p>
                    <p class="text-3xl sm:text-4xl font-extrabold text-white font-mono mt-1">
                        {{ estimated1RM.toFixed(1) }}
                        <span class="text-xs font-normal text-slate-400">kg</span>
                    </p>
                </div>

                <!-- Porcentajes -->
                <div
                    v-if="estimated1RM > 0"
                    class="pt-3 border-t border-[#232D42]"
                >
                    <p class="text-[11px] font-bold text-slate-300 uppercase tracking-wider mb-2.5">
                        Tabla de Porcentajes 1RM
                    </p>
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <div
                            v-for="p in percentages1RM"
                            :key="p.percentage"
                            class="flex items-center justify-between p-2 px-3 bg-[#101521] border border-[#232D42]/80 rounded-lg"
                        >
                            <span class="font-bold text-indigo-400 font-mono">
                                {{ p.percentage }}%
                            </span>
                            <span class="font-extrabold text-white font-mono">
                                {{ p.weight }} kg
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Récords Personales e Historial de 1RM (8 cols) -->
        <section
            class="lg:col-span-8 bg-[#131924] border border-[#232D42] rounded-xl shadow-lg overflow-hidden flex flex-col justify-between"
        >
            <div>
                <div
                    class="p-4 bg-[#18202F]/60 border-b border-[#232D42] flex flex-col sm:flex-row sm:items-center justify-between gap-3"
                >
                    <div class="flex items-center gap-2 flex-wrap">
                        <div class="w-2.5 h-2.5 rounded bg-amber-400"></div>
                        <h3 class="text-sm font-bold text-white tracking-wide uppercase">
                            Récords e 1RM Históricos
                        </h3>
                        <span class="text-xs bg-[#232D42] text-slate-300 px-2 py-0.5 rounded-full font-mono">
                            {{ filteredRows.length }} ejercicios
                        </span>
                    </div>

                    <div class="flex items-center gap-2">
                        <div class="relative w-full sm:w-52">
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Buscar ejercicio..."
                                class="w-full bg-[#101521] border border-[#232D42] text-xs text-white rounded-lg pl-8 pr-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-indigo-500 placeholder-slate-500"
                            />
                            <svg
                                class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2 pointer-events-none"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                ></path>
                            </svg>
                        </div>

                        <select
                            v-model="formula"
                            class="bg-[#101521] border border-[#232D42] rounded-lg px-3 py-1.5 text-xs font-semibold text-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-500 cursor-pointer"
                        >
                            <option value="epley">Fórmula Epley</option>
                            <option value="lander">Fórmula Lander</option>
                        </select>
                    </div>
                </div>

                <!-- Mobile Cards View (md:hidden) -->
                <div class="md:hidden divide-y divide-[#232D42]/60">
                    <div
                        v-for="(row, idx) in filteredRows"
                        :key="`mob-rm-${row.name ?? row.date}`"
                        class="p-3.5 hover:bg-[#18202F]/60 transition-colors flex items-center justify-between gap-3"
                    >
                        <div class="min-w-0">
                            <div class="font-bold text-white text-xs flex items-center gap-1.5">
                                <span class="truncate">{{ row.name }}</span>
                                <span
                                    v-if="idx < 3"
                                    class="w-2 h-2 rounded-full bg-amber-400 shrink-0"
                                ></span>
                            </div>
                            <div class="text-[11px] text-slate-400 font-mono mt-0.5">
                                PR: {{ row.weight.toFixed(1) }} kg × {{ row.reps }} reps · {{ row.date }}
                            </div>
                        </div>
                        <span
                            class="inline-flex items-center gap-1 font-mono text-xs font-bold px-2.5 py-1 rounded border shrink-0"
                            :class="
                                idx === 0
                                    ? 'text-amber-400 bg-amber-400/10 border-amber-400/30'
                                    : 'text-indigo-300 bg-indigo-500/10 border-indigo-500/30'
                            "
                        >
                            ★ {{ row.rm.toFixed(1) }} kg
                        </span>
                    </div>
                    <div v-if="filteredRows.length === 0" class="py-8 text-center text-xs text-slate-400 px-4">
                        No hay suficientes datos del historial con pesos y repeticiones para estimar récords.
                    </div>
                </div>

                <!-- Desktop Table -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr
                                class="border-b border-[#232D42] bg-[#101521]/70 text-[11px] font-semibold text-slate-400 uppercase tracking-wider"
                            >
                                <th class="py-3 px-4">Ejercicio</th>
                                <th class="py-3 px-4 text-center">Récord de Carga (PR)</th>
                                <th class="py-3 px-4 text-right">1RM Estimado</th>
                                <th class="py-3 px-4 text-center">Fecha del Récord</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#232D42]/60 font-medium">
                            <tr
                                v-for="(row, idx) in filteredRows"
                                :key="row.name ?? row.date"
                                class="hover:bg-[#18202F]/60 transition-colors"
                            >
                                <td class="py-3.5 px-4 font-bold text-white">
                                    <div class="flex items-center gap-2">
                                        <span>{{ row.name }}</span>
                                        <span
                                            v-if="idx < 3"
                                            class="w-2 h-2 rounded-full bg-amber-400"
                                            title="Top 3 Fuerza"
                                        ></span>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-center font-mono text-slate-300">
                                    {{ row.weight.toFixed(1) }} kg × {{ row.reps }} reps
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <span
                                        class="inline-flex items-center gap-1 font-mono font-bold px-2 py-0.5 rounded border"
                                        :class="
                                            idx === 0
                                                ? 'text-amber-400 bg-amber-400/10 border-amber-400/30'
                                                : 'text-indigo-300 bg-indigo-500/10 border-indigo-500/30'
                                        "
                                    >
                                        ★ {{ row.rm.toFixed(1) }} kg
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-center font-mono text-slate-400">
                                    {{ row.date }}
                                </td>
                            </tr>
                            <tr v-if="filteredRows.length === 0">
                                <td colspan="4" class="py-8 text-center text-slate-400">
                                    No hay suficientes datos del historial con pesos y repeticiones para estimar récords.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="px-4 py-3 bg-[#101521] border-t border-[#232D42] flex items-center justify-between text-xs text-slate-400">
                <span>Mostrando {{ filteredRows.length }} ejercicios con 1RM estimado</span>
                <span class="font-mono text-indigo-400 uppercase">{{ formula }}</span>
            </div>
        </section>
    </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';

const props = defineProps({
    calculator: { type: Object, required: true },
    rmFormula: { type: String, required: true },
    historical1RMs: { type: Array, required: true },
});

const emit = defineEmits(['update:calculator', 'update:rmFormula']);

const searchQuery = ref('');

const filteredRows = computed(() => {
    const q = searchQuery.value.trim().toLowerCase();
    if (!q) return props.historical1RMs || [];
    return (props.historical1RMs || []).filter((r) => (r.name || '').toLowerCase().includes(q));
});

// --- Estado editable ---
// Antes el componente copiaba el prop entero a un ref local y encadenaba dos
// watches deep (prop → local → emit → prop → …): al tipear entraba en bucle
// recursivo ("Maximum recursive updates exceeded").
// Ahora los campos editables son refs simples. El watch del prop sólo actúa
// si el valor cambió de verdad, así que el eco termina en el primer vuelta.
const weight = ref(props.calculator.weight);
const reps = ref(props.calculator.reps);

watch(
    () => props.calculator.weight,
    (v) => {
        if (v !== weight.value) weight.value = v;
    }
);
watch(
    () => props.calculator.reps,
    (v) => {
        if (v !== reps.value) reps.value = v;
    }
);

watch([weight, reps], ([w, r]) => {
    emit('update:calculator', { ...props.calculator, weight: w, reps: r });
});

// La fórmula tiene UNA sola fuente. Antes el <select> actualizaba `rmFormula`
// pero el 1RM se calculaba con la fórmula local: cambiar la fórmula no
// recalculaba nada.
const formula = computed({
    get: () => props.rmFormula,
    set: (v) => emit('update:rmFormula', v),
});

const estimated1RM = computed(() => {
    const w = parseFloat(weight.value);
    const r = parseInt(reps.value);
    if (!w || !r || w <= 0 || r <= 0) return 0;
    if (formula.value === 'epley') {
        return w * (1 + r / 30);
    }
    return (100 * w) / (101.3 - 2.6712 * r);
});

const percentages1RM = computed(() => {
    const rm = estimated1RM.value;
    if (!rm) return [];
    return [95, 90, 85, 80, 75, 70, 65, 60].map((p) => ({
        percentage: p,
        weight: (rm * (p / 100)).toFixed(1),
    }));
});
</script>
