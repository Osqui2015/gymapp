<button
    x-data="{ dark: window.darkMode.isDark() }"
    @click="window.darkMode.toggle(); dark = !dark"
    @theme:changed.window="dark = $event.detail.dark"
    type="button"
    :aria-label="`Cambiar a modo ${dark ? 'claro' : 'oscuro'}`"
    :title="`Modo ${dark ? 'oscuro' : 'claro'}`"
    class="inline-flex items-center justify-center w-10 h-10 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
>
    {{-- Sol (visible en dark) --}}
    <svg
        x-show="dark"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 rotate-90 scale-50"
        x-transition:enter-end="opacity-100 rotate-0 scale-100"
        style="display: none;"
        class="w-5 h-5"
        fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
    >
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
    </svg>

    {{-- Luna (visible en light) --}}
    <svg
        x-show="!dark"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -rotate-90 scale-50"
        x-transition:enter-end="opacity-100 rotate-0 scale-100"
        class="w-5 h-5"
        fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
    >
        <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
    </svg>
</button>
