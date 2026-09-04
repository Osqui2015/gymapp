<template>
  <div v-if="totalSeries > 0" class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm">
    <div class="flex items-center justify-between mb-3">
      <div>
        <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">📅 Actividad de los últimos 7 días</p>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Más oscuro = más series</p>
      </div>
      <span class="text-xs font-mono text-gray-500 dark:text-gray-400">{{ totalSeries }} series</span>
    </div>
    <div class="grid grid-cols-7 gap-1.5">
      <div
        v-for="(dia, i) in dias"
        :key="i"
        class="flex flex-col items-center gap-1"
      >
        <div
          class="w-full aspect-square rounded-md flex items-center justify-center text-xs font-bold transition-all"
          :class="dia.color"
          :title="`${dia.label}: ${dia.series} series`"
        >
          <span v-if="dia.series > 0" class="text-white">{{ dia.series }}</span>
        </div>
        <span class="text-[10px] font-medium text-gray-500 dark:text-gray-400">{{ dia.diaCorto }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useFormatters } from '@/composables/useFormatters';

const { formatDateShort } = useFormatters();

const props = defineProps({
    historial: { type: Array, default: () => [] },
});

const diasCortos = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];

const dias = computed(() => {
    const hoy = new Date();
    hoy.setHours(0, 0, 0, 0);

    const resultado = [];
    for (let i = 6; i >= 0; i--) {
        const fecha = new Date(hoy);
        fecha.setDate(hoy.getDate() - i);
        resultado.push({
            fecha: fecha.toISOString().split('T')[0],
            label: fechaformatDateShort(1716),
            diaCorto: diasCortos[fecha.getDay()],
            series: 0,
        });
    }

    (props.historial || []).forEach((reg) => {
        const fecha = reg.fecha || (reg.created_at ? reg.created_at.split('T')[0] : null);
        if (!fecha) return;
        const dia = resultado.find((d) => d.fecha === fecha);
        if (dia) dia.series += 1;
    });

    const maxSeries = Math.max(...resultado.map((d) => d.series), 1);
    resultado.forEach((dia) => {
        const ratio = dia.series / maxSeries;
        if (dia.series === 0) {
            dia.color = 'bg-gray-100 dark:bg-gray-700';
        } else if (ratio < 0.34) {
            dia.color = 'bg-indigo-300 dark:bg-indigo-700';
        } else if (ratio < 0.67) {
            dia.color = 'bg-indigo-500 dark:bg-indigo-500';
        } else {
            dia.color = 'bg-indigo-700 dark:bg-indigo-600';
        }
    });

    return resultado;
});

const totalSeries = computed(() => dias.value.reduce((sum, d) => sum + d.series, 0));
</script>