<!--
  AdminReports — dashboard de reportes para admin.
  Muestra: retención, churn, frecuencia de entrenamiento, top alumnos.
-->
<template>
    <div class="space-y-6">
        <!-- Header con refresh -->
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Reportes</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Métricas de retención, churn y actividad.
                    <span v-if="cachedAt" class="ml-2 text-xs">
                        Actualizado: {{ formatRelative(cachedAt) }}
                    </span>
                </p>
            </div>
            <button
                @click="cargar(true)"
                :disabled="loading"
                class="px-3 py-2 text-sm font-semibold text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-950/30 rounded-lg transition-colors disabled:opacity-50"
            >
                {{ loading ? 'Cargando...' : '🔄 Refrescar' }}
            </button>
        </div>

        <!-- Loading inicial -->
        <div v-if="loading && !data" class="grid gap-4 md:grid-cols-3">
            <BaseSkeleton variant="stat-card" :count="3" />
        </div>

        <template v-else-if="data">
            <!-- Cards de retención -->
            <section>
                <h3 class="text-sm font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">
                    Retención mensual
                </h3>
                <div class="grid gap-4 md:grid-cols-4">
                    <StatCard
                        label="Activos mes pasado"
                        :value="data.retencion.activos_mes_pasado"
                        color="gray"
                    />
                    <StatCard
                        label="Activos mes actual"
                        :value="data.retencion.activos_mes_actual"
                        color="indigo"
                    />
                    <StatCard
                        label="Retención"
                        :value="`${data.retencion.tasa_retencion}%`"
                        :sub="`${data.retencion.retenidos} de ${data.retencion.activos_mes_pasado}`"
                        :color="data.retencion.tasa_retencion >= 70 ? 'emerald' : data.retencion.tasa_retencion >= 40 ? 'amber' : 'red'"
                    />
                    <StatCard
                        label="Churn"
                        :value="`${data.retencion.tasa_churn}%`"
                        :sub="`${data.retencion.churned} usuarios`"
                        :color="data.retencion.tasa_churn <= 30 ? 'emerald' : data.retencion.tasa_churn <= 60 ? 'amber' : 'red'"
                    />
                </div>
            </section>

            <!-- Frecuencia -->
            <section>
                <h3 class="text-sm font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">
                    Frecuencia de entrenamiento (últimos 30 días)
                </h3>
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <p class="text-3xl font-bold text-gray-900 dark:text-white">
                                {{ data.frecuencia.promedio_dias_por_mes }}
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">días/mes promedio</p>
                        </div>
                    </div>
                    <!-- Barras horizontales de distribución -->
                    <div class="space-y-3">
                        <div v-for="(count, label) in data.frecuencia.distribucion" :key="label">
                            <div class="flex items-center justify-between text-sm mb-1">
                                <span class="text-gray-700 dark:text-gray-300">{{ labelForDist(label) }}</span>
                                <span class="font-semibold text-gray-900 dark:text-white">{{ count }}</span>
                            </div>
                            <div class="h-2 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                                <div
                                    class="h-full transition-all duration-500"
                                    :class="distColor(label)"
                                    :style="{ width: `${barWidth(count)}%` }"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Top alumnos -->
            <section>
                <h3 class="text-sm font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">
                    Top 10 alumnos más activos
                </h3>
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700">
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">#</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Alumno</th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Días</th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Series</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            <tr v-for="(u, idx) in data.top_alumnos" :key="u.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400 font-mono">{{ idx + 1 }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 text-white flex items-center justify-center font-bold text-xs">
                                            {{ (u.name || '?').charAt(0).toUpperCase() }}
                                        </div>
                                        <div>
                                            <p class="font-semibold text-gray-900 dark:text-white">{{ u.name }}</p>
                                            <p class="text-xs text-gray-500">@{{ u.nick }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-right font-bold text-indigo-600 dark:text-indigo-400">
                                    {{ u.dias_entrenados }}
                                </td>
                                <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">
                                    {{ u.series_totales }}
                                </td>
                            </tr>
                            <tr v-if="!data.top_alumnos?.length">
                                <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-500">
                                    Sin actividad en los últimos 30 días
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Churn alert -->
            <section v-if="data.churn.en_riesgo > 0">
                <div class="bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/50 rounded-2xl p-5 flex items-start gap-4">
                    <div class="text-3xl">⚠️</div>
                    <div class="flex-1">
                        <h3 class="font-bold text-amber-900 dark:text-amber-200">
                            {{ data.churn.en_riesgo }} usuarios en riesgo de abandono
                        </h3>
                        <p class="text-sm text-amber-800 dark:text-amber-300 mt-1">
                            Tienen membresía activa pero no entrenan hace 14+ días.
                            Considerá enviarles un recordatorio o contactarlos.
                        </p>
                    </div>
                </div>
            </section>
        </template>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import BaseSkeleton from './BaseSkeleton.vue';
import StatCard from './StatCard.vue';

const loading = ref(false);
const data = ref(null);

const cargar = async (invalidate = false) => {
    loading.value = true;
    try {
        if (invalidate) {
            await axios.post('/api/admin/estadisticas/invalidate');
        }
        const { data: res } = await axios.get('/api/admin/reportes');
        data.value = res;
    } catch (e) {
        console.error('[reports] load error:', e);
    } finally {
        loading.value = false;
    }
};

const cachedAt = computed(() => data.value?.cached_at);

const formatRelative = (iso) => {
    if (!iso) return '';
    const d = new Date(iso);
    const diff = (Date.now() - d.getTime()) / 1000;
    if (diff < 60) return 'hace instantes';
    if (diff < 3600) return `hace ${Math.floor(diff / 60)} min`;
    return d.toLocaleString();
};

const labelForDist = (key) => ({
    diario: '🔥 Diario (20-30 días)',
    frecuente: '💪 Frecuente (12-19 días)',
    regular: '✓ Regular (6-11 días)',
    ocasional: '⏸ Ocasional (1-5 días)',
    inactivo: '💤 Inactivo (0 días)',
}[key] || key);

const distColor = (key) => ({
    diario: 'bg-emerald-500',
    frecuente: 'bg-emerald-400',
    regular: 'bg-amber-400',
    ocasional: 'bg-orange-400',
    inactivo: 'bg-gray-300 dark:bg-gray-600',
}[key] || 'bg-gray-300');

const barWidth = (count) => {
    if (!data.value) return 0;
    const max = Math.max(...Object.values(data.value.frecuencia.distribucion), 1);
    return Math.min(100, (count / max) * 100);
};

onMounted(cargar);
</script>
