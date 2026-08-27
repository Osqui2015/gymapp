<!--
  BodyWeightChart — Chart de evolución de peso con línea punteada del goal.

  Basado en la pantalla "Home" de openGym: peso actual grande, flecha verde/roja
  según dirección, delta al goal, y chart con línea punteada del objetivo.

  Props:
    - data:     [{ fecha, peso }]  histórico ordenado por fecha
    - goal:     number | null      peso objetivo en kg
    - latest:   { peso, fecha } | null
    - delta:    number | null      diferencia peso - goal (positivo = sobre goal)
    - direction: 'down' | 'up' | null  'down' = goal es perder peso, 'up' = goal es ganar

  Eventos:
    - update:goal  (newValue)  cuando el usuario edita el peso objetivo
-->
<template>
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6 shadow-sm">
        <!-- Header con peso actual + edit del goal -->
        <div class="flex flex-wrap items-start justify-between gap-4 mb-5">
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Peso corporal</p>
                <div v-if="latest" class="flex items-baseline gap-2 mt-1">
                    <span class="text-4xl font-black text-gray-900 dark:text-white">{{ latest.peso.toFixed(1) }}</span>
                    <span class="text-lg font-semibold text-gray-500">kg</span>
                    <span
                        v-if="totalChange !== null"
                        :class="['text-sm font-semibold ml-2', totalChange < 0 ? 'text-emerald-600' : totalChange > 0 ? 'text-rose-600' : 'text-gray-500']"
                    >
                        {{ totalChange > 0 ? '+' : '' }}{{ totalChange.toFixed(1) }} kg
                    </span>
                </div>
                <p v-if="!latest" class="text-sm text-gray-500 dark:text-gray-400 mt-1">Sin registros aún</p>
                <p v-if="latest" class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ formatDate(latest.fecha) }}</p>
            </div>

            <div class="flex flex-col items-end gap-1">
                <label class="text-xs text-gray-500 dark:text-gray-400">Peso objetivo (kg)</label>
                <div class="flex items-center gap-1">
                    <input
                        v-model.number="goalLocal"
                        type="number"
                        step="0.1"
                        min="30"
                        max="300"
                        class="w-20 px-2 py-1 text-right text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                        @blur="onGoalBlur"
                        @keyup.enter="onGoalBlur"
                    />
                    <button
                        v-if="goalLocal !== goal"
                        type="button"
                        @click="onGoalBlur"
                        class="text-xs px-2 py-1 bg-indigo-600 text-white rounded hover:bg-indigo-700"
                    >
                        Guardar
                    </button>
                </div>
                <p v-if="delta !== null" class="text-xs font-semibold mt-1" :class="deltaClass">
                    <span v-if="direction === 'down' && delta < 0">↓ {{ Math.abs(delta).toFixed(1) }} kg perdido</span>
                    <span v-else-if="direction === 'down' && delta > 0">{{ delta.toFixed(1) }} kg por perder</span>
                    <span v-else-if="direction === 'up' && delta > 0">↑ {{ delta.toFixed(1) }} kg ganado</span>
                    <span v-else-if="direction === 'up' && delta < 0">{{ Math.abs(delta).toFixed(1) }} kg por ganar</span>
                    <span v-else-if="delta === 0">¡Objetivo logrado!</span>
                </p>
            </div>
        </div>

        <!-- Chart -->
        <div v-if="chartData.length > 0" class="h-64 w-full">
            <ResponsiveContainer width="100%" height="100%">
                <LineChart :data="chartData" :margin="{ top: 5, right: 20, left: -10, bottom: 5 }">
                    <CartesianGrid stroke="#e5e7eb" stroke-dasharray="3 3" :stroke-opacity="0.4" />
                    <XAxis
                        :data-key="'fecha'"
                        :tick-formatter="formatTick"
                        stroke="#9ca3af"
                        style="font-size: 11px"
                    />
                    <YAxis
                        :domain="yDomain"
                        stroke="#9ca3af"
                        style="font-size: 11px"
                        tick-formatter="(v) => `${v}`"
                    />
                    <Tooltip
                        :content-style="{ background: '#1f2937', border: 'none', borderRadius: '8px', color: '#fff', fontSize: '12px' }"
                        :item-style="{ color: '#fff' }"
                        :label-style="{ color: '#a3e635', fontWeight: 'bold' }"
                        :formatter="(v) => `${v} kg`"
                    />
                    <!-- Línea horizontal punteada del goal -->
                    <ReferenceLine
                        v-if="goal"
                        :y="goal"
                        stroke="#a3e635"
                        stroke-dasharray="6 4"
                        stroke-width="2"
                        :label="{ value: `Objetivo: ${goal} kg`, position: 'right', fill: '#a3e635', fontSize: 11, fontWeight: 'bold' }"
                    />
                    <Line
                        type="monotone"
                        :data-key="'peso'"
                        stroke="#4f46e5"
                        stroke-width="2.5"
                        dot="{ r: 3, fill: '#4f46e5' }"
                        active-dot="{ r: 5, fill: '#4f46e5' }"
                    />
                </LineChart>
            </ResponsiveContainer>
        </div>

        <div v-else class="text-center py-12 text-gray-400 text-sm">
            <p>No hay registros de peso todavía.</p>
            <p class="mt-1 text-xs">Anotá tu primer peso en la sección de Progreso y vas a ver tu evolución acá.</p>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, ReferenceLine } from 'recharts';

const props = defineProps({
    data: { type: Array, default: () => [] },
    goal: { type: Number, default: null },
    latest: { type: Object, default: null },
    delta: { type: Number, default: null },
    direction: { type: String, default: null },
    totalChange: { type: Number, default: null },
});

const emit = defineEmits(['update:goal']);

const goalLocal = ref(props.goal);

watch(() => props.goal, (v) => { goalLocal.value = v; });

const chartData = computed(() => {
    return props.data.map(d => ({
        fecha: d.fecha,
        peso: d.peso,
    }));
});

const yDomain = computed(() => {
    if (chartData.value.length === 0) return [0, 100];
    const pesos = chartData.value.map(d => d.peso);
    if (props.goal) pesos.push(props.goal);
    const min = Math.min(...pesos);
    const max = Math.max(...pesos);
    const range = max - min;
    const padding = range > 0 ? range * 0.1 : 2;
    return [Math.max(0, Math.floor(min - padding)), Math.ceil(max + padding)];
});

const deltaClass = computed(() => {
    if (props.delta === null) return 'text-gray-500';
    if (props.delta === 0) return 'text-emerald-600';
    if (props.direction === 'down' && props.delta < 0) return 'text-emerald-600';
    if (props.direction === 'up' && props.delta > 0) return 'text-emerald-600';
    return 'text-rose-600';  // lejos del goal
});

function formatDate(iso) {
    if (!iso) return '';
    const d = new Date(iso + 'T00:00:00');
    return new Intl.DateTimeFormat('es-MX', { day: '2-digit', month: 'short', year: 'numeric' }).format(d);
}

function formatTick(iso) {
    if (!iso) return '';
    const d = new Date(iso + 'T00:00:00');
    return new Intl.DateTimeFormat('es-MX', { month: 'short', year: '2-digit' }).format(d);
}

function onGoalBlur() {
    if (goalLocal.value !== props.goal && goalLocal.value > 0) {
        emit('update:goal', goalLocal.value);
    } else if (!goalLocal.value) {
        // Si está vacío, también lo mandamos (null) para borrar el goal
        emit('update:goal', null);
    }
}
</script>
