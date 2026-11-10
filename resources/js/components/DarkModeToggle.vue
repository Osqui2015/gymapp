<!--
  DarkModeToggle — 3 modos: ☀️ Light, 🌙 Dark, 💻 Auto (sigue OS).

  - `auto` (default): usa `prefers-color-scheme: dark` y se actualiza si el user
    cambia la preferencia del OS en vivo.
  - `light` / `dark`: forzado, sin importar la preferencia del OS.

  Persiste la elección en localStorage con la clave `theme_mode` (`auto|light|dark`).
  También aplica la clase `.dark` al `<html>` (Tailwind lo lee desde ahí).
-->
<template>
    <div class="relative" ref="rootEl">
        <button
            @click="open = !open"
            type="button"
            :aria-label="`Modo actual: ${modeLabel}. Click para cambiar.`"
            :title="`Modo: ${modeLabel}`"
            class="relative inline-flex items-center justify-center w-10 h-10 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
        >
            <!-- Sol (light) -->
            <svg
                v-if="effectiveMode === 'light'"
                class="w-5 h-5"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"
                />
            </svg>
            <!-- Luna (dark) -->
            <svg
                v-else-if="effectiveMode === 'dark'"
                class="w-5 h-5"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"
                />
            </svg>
            <!-- Monitor (auto) -->
            <svg
                v-else
                class="w-5 h-5"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                />
            </svg>
        </button>

        <Transition name="dropdown">
            <div
                v-if="open"
                class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden z-50"
            >
                <button
                    v-for="opt in options"
                    :key="opt.value"
                    @click="select(opt.value)"
                    :class="[
                        'w-full px-3 py-2 text-left text-sm flex items-center gap-2 transition-colors',
                        mode === opt.value
                            ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 font-semibold'
                            : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700',
                    ]"
                >
                    <span class="text-lg">{{ opt.icon }}</span>
                    <span>{{ opt.label }}</span>
                    <span v-if="mode === opt.value" class="ml-auto">✓</span>
                </button>
            </div>
        </Transition>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import theme from '../utils/theme';

const mode = ref(theme.get()); // preferencia del user: 'auto' | 'light' | 'dark'
const effectiveMode = ref(theme.effective()); // estado efectivo aplicado: 'light' | 'dark'
const open = ref(false);
const rootEl = ref(null);

let unsubscribeTheme = null;

const options = [
    { value: 'light', label: 'Claro', icon: '☀️' },
    { value: 'dark', label: 'Oscuro', icon: '🌙' },
    { value: 'auto', label: 'Automático (sistema)', icon: '💻' },
];

const modeLabel = computed(() => {
    return options.find((o) => o.value === mode.value)?.label || '';
});

const select = (value) => {
    theme.set(value);
    open.value = false;
};

const onClickOutside = (e) => {
    if (!rootEl.value) return;
    if (!rootEl.value.contains(e.target)) open.value = false;
};

onMounted(() => {
    mode.value = theme.get();
    effectiveMode.value = theme.effective();

    unsubscribeTheme = theme.onChange(({ mode: nextMode, effective: nextEffective }) => {
        mode.value = nextMode;
        effectiveMode.value = nextEffective;
    });

    document.addEventListener('click', onClickOutside);
});

onBeforeUnmount(() => {
    if (unsubscribeTheme) {
        unsubscribeTheme();
    }
    document.removeEventListener('click', onClickOutside);
});
</script>

<style scoped>
.dropdown-enter-active,
.dropdown-leave-active {
    transition:
        opacity 0.15s ease,
        transform 0.15s ease;
}
.dropdown-enter-from,
.dropdown-leave-to {
    opacity: 0;
    transform: translateY(-4px);
}
</style>
