<template>
  <div class="rounded-2xl bg-gradient-to-r from-slate-950 via-slate-900 to-indigo-950 border border-slate-800 shadow-2xl p-4 md:p-8 mb-5 md:mb-6">
    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
      <div>
        <p class="text-[10px] md:text-sm uppercase tracking-[0.24em] text-indigo-300 mb-2">Rutina activa</p>
        <h2 class="text-2xl md:text-4xl font-bold text-white leading-tight">
          {{ nivel }}
        </h2>
        <div class="flex flex-wrap items-center gap-2 md:gap-3 mt-3 md:mt-4 text-xs md:text-sm text-slate-300">
          <span class="inline-flex items-center rounded-full bg-white/10 px-3 py-1 font-medium">
            {{ dias }}
          </span>
          <span class="inline-flex items-center rounded-full bg-emerald-500/15 px-3 py-1 font-medium text-emerald-200">
            Día actual: {{ diaActual }}
          </span>
        </div>
      </div>

      <button
        @click="$emit('cambiar')"
        class="inline-flex items-center justify-center rounded-xl bg-white/10 px-4 py-2 md:px-5 md:py-3 text-xs md:text-sm font-semibold text-white transition-colors hover:bg-white/15 border border-white/10 self-start"
      >
        Cambiar rutina
      </button>
    </div>
  </div>
</template>

<script setup>
defineProps({
    nivel: { type: String, required: true },
    dias: { type: String, required: true },
    diaActual: { type: String, required: true },
});

defineEmits(['cambiar']);
</script>