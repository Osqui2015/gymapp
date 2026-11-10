<!--
    SuperserieBloqueIzq.vue

    Bloque de superserie de la columna izquierda del ActiveWorkoutModal:
    muestra el par (primero / segundo) con el estado "Ahora" / "Ya hiciste" /
    "Después" y el conector visual entre ambos.

    Props:
      - superseriePartner: partner de la superserie (usa .ejercicio.superserie_grupo)
      - superserieOrder: { first, second, currentRole }
-->
<template>
    <div
        class="obs-card p-3 border-violet-500/30 bg-violet-500/5"
        data-testid="superset-left-block"
    >
        <div class="flex items-center gap-2 mb-2">
            <span class="text-base">⚡</span>
            <p class="text-[11px] font-black uppercase tracking-[0.14em] text-violet-200">
                Superserie {{ superseriePartner.ejercicio.superserie_grupo }}
            </p>
        </div>

        <div class="space-y-1.5">
            <!-- PRIMERO del par -->
            <div
                class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg border"
                :class="
                    superserieOrder.currentRole === 'first'
                        ? 'bg-violet-500/15 border-violet-500/35'
                        : 'bg-emerald-900/15 border-emerald-500/25'
                "
                :data-testid="`superset-left-first-${superserieOrder.first.index}`"
            >
                <span
                    class="w-1.5 h-1.5 rounded-full shrink-0"
                    :class="
                        superserieOrder.currentRole === 'first'
                            ? 'bg-emerald-400'
                            : 'bg-emerald-500/70'
                    "
                ></span>
                <span
                    class="text-[10px] font-black uppercase tracking-wider w-16 shrink-0"
                    :class="
                        superserieOrder.currentRole === 'first'
                            ? 'text-emerald-300'
                            : 'text-emerald-400/70'
                    "
                >
                    {{ superserieOrder.currentRole === 'first' ? 'Ahora' : 'Ya hiciste' }}
                </span>
                <span
                    class="text-xs sm:text-sm font-bold truncate"
                    :class="
                        superserieOrder.currentRole === 'first'
                            ? 'text-white'
                            : 'text-gray-300 line-through decoration-emerald-500/40'
                    "
                >
                    {{ superserieOrder.first.ejercicio.nombre }}
                </span>
            </div>

            <!-- Conector visual entre los dos -->
            <div class="flex items-center gap-2 px-2.5">
                <span class="text-violet-400 text-[10px] font-black">↓</span>
                <span class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">
                    {{ superserieOrder.currentRole === 'first' ? 'Seguí directo con' : 'Ahora' }}
                </span>
            </div>

            <!-- SEGUNDO del par -->
            <div
                class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg border"
                :class="
                    superserieOrder.currentRole === 'second'
                        ? 'bg-violet-500/15 border-violet-500/35'
                        : 'bg-[var(--color-obsidian-surface)] border-[var(--color-obsidian-border)]'
                "
                :data-testid="`superset-left-second-${superserieOrder.second.index}`"
            >
                <span
                    class="w-1.5 h-1.5 rounded-full shrink-0"
                    :class="
                        superserieOrder.currentRole === 'second'
                            ? 'bg-emerald-400'
                            : 'bg-violet-400'
                    "
                ></span>
                <span
                    class="text-[10px] font-black uppercase tracking-wider w-16 shrink-0"
                    :class="
                        superserieOrder.currentRole === 'second'
                            ? 'text-emerald-300'
                            : 'text-violet-300'
                    "
                >
                    {{ superserieOrder.currentRole === 'second' ? 'Ahora' : 'Después' }}
                </span>
                <span
                    class="text-xs sm:text-sm font-bold truncate text-white"
                >
                    {{ superserieOrder.second.ejercicio.nombre }}
                </span>
            </div>
        </div>

        <p class="mt-2 text-[10px] text-gray-400 italic">
            <template v-if="superserieOrder.currentRole === 'first'">
                Sin descanso entre ambos. Registrá una serie en cada uno antes de la próxima pausa.
            </template>
            <template v-else>
                Último del par. Después de esta serie arranca el descanso.
            </template>
        </p>
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
