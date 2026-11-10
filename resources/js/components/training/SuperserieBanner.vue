<!--
    SuperserieBanner.vue

    Banner explicativo de superserie (columna derecha del ActiveWorkoutModal).
    Indica qué ejercicio hacer ahora y cuál ya se hizo.

    Props:
      - superseriePartner: partner de la superserie (usa .ejercicio.superserie_grupo)
      - superserieOrder: { first, second, currentRole }
-->
<template>
    <div
        class="obs-card p-2.5 sm:p-3 flex items-center gap-2 border-violet-500/30 bg-violet-500/5"
        data-testid="superset-banner"
    >
        <span class="text-base">⚡</span>
        <div class="min-w-0 flex-1">
            <p class="text-[11px] font-black uppercase tracking-[0.14em] text-violet-200">
                Superserie {{ superseriePartner.ejercicio.superserie_grupo }}
            </p>
            <p class="text-[11px] text-gray-400">
                <template v-if="superserieOrder.currentRole === 'first'">
                    Hacé
                    <strong class="text-white">{{ superserieOrder.first.ejercicio.nombre }}</strong>
                    y seguí directo con
                    <strong class="text-white">{{ superserieOrder.second.ejercicio.nombre }}</strong>
                    sin descansar entre ellos.
                </template>
                <template v-else>
                    Hacé
                    <strong class="text-white">{{ superserieOrder.second.ejercicio.nombre }}</strong>
                    — ya hiciste
                    <strong class="text-white">{{ superserieOrder.first.ejercicio.nombre }}</strong>
                    antes, seguí sin descansar.
                </template>
            </p>
        </div>
    </div>
</template>

<script setup>
defineProps({
    superseriePartner: {
        type: Object,
        required: true,
    },
    superserieOrder: {
        type: Object,
        required: true,
    },
});
</script>
