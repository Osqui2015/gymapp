<!--
  RutinasSugeridas — muestra sugerencias de rutinas basadas en reglas (no ML).

  Usa el endpoint /api/rutinas/sugeridas que devuelve un payload con:
    - perfil: análisis del historial del user
    - sugeridas: array de rutinas rankeadas con score y razones

  Muestra cada sugerencia con:
    - Header (nivel + modalidad + score)
    - Razones de por qué se sugiere
    - CTA "Elegir esta rutina"
-->
<template>
    <div class="bg-gradient-to-br from-indigo-50 to-purple-50 dark:from-indigo-950/30 dark:to-purple-950/30 rounded-2xl border border-indigo-200 dark:border-indigo-800/50 p-5">
        <div class="flex items-start gap-3 mb-4">
            <div class="text-3xl">🤖</div>
            <div class="flex-1">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Rutinas sugeridas para vos</h3>
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    Basado en tu historial{{ perfil?.dias_por_mes ? ` (${perfil.dias_por_mes} días entrenados este mes)` : '' }}.
                </p>
            </div>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="space-y-2">
            <div v-for="i in 3" :key="i" class="bg-white dark:bg-gray-800 rounded-xl p-4 animate-pulse">
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/3 mb-2"></div>
                <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-1/2"></div>
            </div>
        </div>

        <!-- Sugerencias -->
        <div v-else-if="sugeridas.length" class="space-y-3">
            <div
                v-for="(s, idx) in sugeridas"
                :key="s.id"
                class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm hover:shadow-md transition-shadow"
            >
                <div class="flex items-start justify-between gap-3 mb-2">
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-xs font-bold uppercase text-indigo-600 dark:text-indigo-400">
                                #{{ idx + 1 }}
                            </span>
                            <h4 class="font-bold text-gray-900 dark:text-white">
                                {{ s.nivel }} {{ s.modalidad }}
                            </h4>
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                · {{ s.ejercicios_count }} ejercicios
                            </span>
                        </div>
                    </div>
                    <div class="flex flex-col items-end gap-0.5 flex-shrink-0">
                        <div class="text-xs font-bold text-indigo-600 dark:text-indigo-400">
                            {{ s.score }} pts
                        </div>
                        <div class="text-[10px] text-gray-400">afinidad</div>
                    </div>
                </div>

                <!-- Razones -->
                <ul v-if="s.razones.length" class="space-y-1 mb-3 text-sm">
                    <li
                        v-for="(razon, ri) in s.razones"
                        :key="ri"
                        class="flex items-start gap-1.5 text-gray-600 dark:text-gray-300"
                    >
                        <span class="text-emerald-500 mt-0.5">✓</span>
                        <span>{{ razon }}</span>
                    </li>
                </ul>

                <!-- CTA -->
                <button
                    v-if="onSelect"
                    @click="onSelect(s)"
                    class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline"
                >
                    Elegir esta rutina →
                </button>
            </div>
        </div>

        <!-- Empty state -->
        <div v-else class="text-center text-sm text-gray-500 dark:text-gray-400 py-8">
            <p>No encontramos sugerencias. Probá explorando todas las rutinas.</p>
        </div>

        <!-- Perfil resumen -->
        <div v-if="perfil && !loading" class="mt-4 pt-4 border-t border-indigo-200/50 dark:border-indigo-800/30 text-xs text-gray-500 dark:text-gray-400">
            <details>
                <summary class="cursor-pointer hover:text-indigo-600 dark:hover:text-indigo-400 font-medium">
                    Ver tu perfil de entrenamiento
                </summary>
                <div class="mt-2 space-y-1 pl-3">
                    <p>• Nivel estimado: <span class="font-semibold">{{ perfil.nivel_estimado }}</span></p>
                    <p>• Días por mes: <span class="font-semibold">{{ perfil.dias_por_mes }}</span></p>
                    <p v-if="Object.keys(perfil.top_grupos || {}).length">
                        • Grupos principales:
                        <span v-for="(pct, grupo) in perfil.top_grupos" :key="grupo" class="font-semibold">
                            {{ grupo }} ({{ Math.round(pct * 100) }}%){{ ' ' }}
                        </span>
                    </p>
                </div>
            </details>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps({
    limit: { type: Number, default: 5 },
    onSelect: { type: Function, default: null },
});

const loading = ref(false);
const sugeridas = ref([]);
const perfil = ref(null);

const cargar = async () => {
    loading.value = true;
    try {
        const { data } = await axios.get('/api/rutinas/sugeridas', {
            params: { limit: props.limit },
        });
        sugeridas.value = data.sugeridas || [];
        perfil.value = data.perfil || null;
    } catch (e) {
        console.error('[sugeridas] error:', e);
        sugeridas.value = [];
    } finally {
        loading.value = false;
    }
};

onMounted(cargar);

// Exponer `Object.keys` para el template (necesario en `<script setup>`)
const Object_ = Object;
defineExpose({ Object: Object_, recargar: cargar });
</script>
