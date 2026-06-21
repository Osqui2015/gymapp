<template>
  <button
    @click="toggle"
    type="button"
    :aria-label="`Cambiar a modo ${isDark ? 'claro' : 'oscuro'}`"
    :title="`Modo ${isDark ? 'claro' : 'oscuro'}`"
    class="relative inline-flex items-center justify-center w-10 h-10 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
  >
    <!-- Icono sol (visible en dark) -->
    <svg
      v-show="isDark"
      class="w-5 h-5 transition-transform"
      :class="{ 'rotate-90': animating }"
      fill="none"
      stroke="currentColor"
      stroke-width="2"
      viewBox="0 0 24 24"
    >
      <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
    </svg>

    <!-- Icono luna (visible en light) -->
    <svg
      v-show="!isDark"
      class="w-5 h-5 transition-transform"
      :class="{ '-rotate-90': animating }"
      fill="none"
      stroke="currentColor"
      stroke-width="2"
      viewBox="0 0 24 24"
    >
      <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
    </svg>
  </button>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const isDark = ref(false);
const animating = ref(false);

// Lee preferencia del usuario
const getInitial = () => {
    const stored = localStorage.getItem('theme');
    if (stored === 'dark') return true;
    if (stored === 'light') return false;
    return window.matchMedia?.('(prefers-color-scheme: dark)').matches ?? false;
};

const apply = (dark) => {
    if (dark) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
};

const toggle = () => {
    isDark.value = !isDark.value;
    animating.value = true;
    localStorage.setItem('theme', isDark.value ? 'dark' : 'light');
    apply(isDark.value);
    setTimeout(() => { animating.value = false; }, 300);
};

let mediaQuery = null;
const onSystemChange = (e) => {
    // Solo respetamos el sistema si el usuario nunca eligió manualmente
    if (!localStorage.getItem('theme')) {
        isDark.value = e.matches;
        apply(isDark.value);
    }
};

onMounted(() => {
    isDark.value = getInitial();
    apply(isDark.value);

    mediaQuery = window.matchMedia?.('(prefers-color-scheme: dark)');
    if (mediaQuery) {
        mediaQuery.addEventListener('change', onSystemChange);
    }
});

onUnmounted(() => {
    if (mediaQuery) {
        mediaQuery.removeEventListener('change', onSystemChange);
    }
});
</script>
