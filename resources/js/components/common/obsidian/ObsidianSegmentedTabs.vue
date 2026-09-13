<!--
  ObsidianSegmentedTabs — tabs estilo segmento/pill (mobile-first).

  Reemplaza al tab clásico "border-bottom" con tabs tipo píldora rellena.
  Usado en Progreso (Medidas/Metas/Galería/Medallas) y Historial
  (Sesiones/Matriz Cargas/Esfuerzo RIR).

  Props:
    - modelValue: string (id del tab activo)
    - tabs: Array<{ id, label, icon?, badge? }>
    - size: 'sm' | 'md'

  Emits:
    - update:modelValue
-->
<template>
    <div
        :class="[
            'flex items-center gap-1 p-1 rounded-2xl border overflow-x-auto scrollbar-hide',
            'bg-gray-100 border-gray-200',
            'dark:bg-[var(--color-obsidian-surface)] dark:border-[var(--color-obsidian-border)]',
        ]"
    >
        <button
            v-for="tab in tabs"
            :key="tab.id"
            type="button"
            @click="$emit('update:modelValue', tab.id)"
            :class="[
                'flex items-center justify-center gap-1.5 rounded-xl font-bold transition-all whitespace-nowrap flex-1 min-w-fit',
                size === 'sm' ? 'px-3 py-1.5 text-xs' : 'px-4 py-2 text-sm',
                modelValue === tab.id
                    ? 'bg-white text-violet-700 shadow-sm dark:bg-[var(--color-obsidian-elevated)] dark:text-violet-300 dark:shadow-[0_4px_14px_rgba(0,0,0,0.4)]'
                    : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white',
            ]"
        >
            <span v-if="tab.icon" aria-hidden="true">{{ tab.icon }}</span>
            <span>{{ tab.label }}</span>
            <span
                v-if="tab.badge != null"
                :class="[
                    'ml-1 rounded-full px-1.5 py-0.5 text-[10px] font-bold',
                    modelValue === tab.id
                        ? 'bg-violet-100 text-violet-700 dark:bg-violet-500/30 dark:text-violet-200'
                        : 'bg-gray-200 text-gray-600 dark:bg-white/10 dark:text-gray-300',
                ]"
            >
                {{ tab.badge }}
            </span>
        </button>
    </div>
</template>

<script setup>
defineProps({
    modelValue: { type: String, required: true },
    tabs: { type: Array, required: true },
    size: { type: String, default: 'md' },
});

defineEmits(['update:modelValue']);
</script>
