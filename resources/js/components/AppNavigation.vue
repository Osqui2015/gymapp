<!--
  AppNavigation — Navegacion principal (reemplaza los x-data de Alpine).

  Antes esta navbar era Blade + Alpine (x-data="{ open: false }",
  x-data="{ trainerOpen: false }", etc.). Alpine ocupaba 44,8 KB del bundle
  INICIAL (16 KB gzip) solo para manejar 4 booleanos de apertura de menu.

  Ahora el estado vive en Vue. Blade sigue armando las URLs y los links
  (se pasan como props), asi que el HTML del servidor no cambia y el SEO
  tampoco.

  Props:
  - links:        array de links del menu principal ({label, href, active})
  - trainerLinks: array de links del dropdown Trainer ({label, href, active, icon})
  - adminLinks:   array de links del dropdown Admin ({label, href, active, icon})
  - userName:     nombre del usuario logueado
  - userEmail:    email del usuario logueado
  - settingsHref: url de Configuración
  - profileHref:  url del Perfil
  - logoutUrl:    url del POST de logout (se manda como form action)
  - csrfToken:    token CSRF para el form de logout
  - dashboardHref, historialHref, rutinasHref, ejerciciosHref, progresoHref
-->
<template>
    <nav class="bg-surface/90 dark:bg-[#0B0F17]/90 backdrop-blur-xl shadow-[0_1px_8px_rgba(0,0,0,0.06)] dark:shadow-[0_1px_8px_rgba(0,0,0,0.3)] border-b border-border dark:border-[#232D42]/80 sticky top-0 z-50 font-body-md transition-colors">
        <div
            class="h-14 md:h-20 max-w-[1280px] mx-auto px-margin md:px-margin-tablet lg:px-margin-desktop flex items-center justify-between gap-space-md md:gap-space-lg"
        >
            <div class="flex items-center gap-3 md:gap-space-xl">
                <a :href="dashboardHref" class="flex items-center gap-2.5 group">
                    <div
                        class="w-9 h-9 md:w-10 md:h-10 rounded-xl bg-gradient-to-tr from-indigo-600 via-indigo-500 to-violet-500 flex items-center justify-center shadow-[0_0_20px_rgba(99,102,241,0.35)] transition-transform group-hover:scale-105"
                    >
                        <span class="material-symbols-outlined text-white text-[20px] md:text-[24px]">fitness_center</span>
                    </div>
                    <div class="flex flex-col">
                        <div class="flex items-center gap-1.5">
                            <span class="font-headline-md text-sm md:text-headline-md tracking-tight text-on-surface font-bold">
                                Gym<span class="text-secondary">App</span>
                            </span>
                            <span
                                class="text-[10px] font-mono uppercase tracking-widest px-1.5 py-0.5 rounded bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 font-semibold"
                                >PRO</span
                            >
                        </div>
                        <span class="hidden sm:block font-label-sm text-label-sm uppercase tracking-wider text-outline">Telemetry Pro</span>
                    </div>
                </a>

                <!-- Acceso rapido en Mobile a Historial -->
                <div class="flex md:hidden items-center gap-1.5">
                    <a
                        :href="historialHref"
                        class="px-2.5 py-1 rounded-lg text-xs font-semibold transition-all flex items-center gap-1"
                        :class="
                            activeRoute === 'historial'
                                ? 'bg-secondary-container text-on-secondary-container shadow-sm'
                                : 'bg-surface-container-high text-on-surface-variant border border-outline-variant hover:text-on-surface dark:bg-[#131924] dark:text-slate-300 dark:border-[#232D42] dark:hover:text-white'
                        "
                    >
                        <span class="material-symbols-outlined text-[15px]">history</span>
                        <span>Historial</span>
                    </a>
                </div>

                <div class="hidden xl:flex xl:items-center">
                    <global-search />
                </div>

                <div
                    class="hidden md:flex items-center gap-space-xs p-space-xs bg-surface-container-low rounded-xl"
                >
                    <a
                        v-for="link in links"
                        :key="link.href"
                        :href="link.href"
                        :aria-current="link.active ? 'page' : 'false'"
                        class="px-space-md py-space-sm transition-colors rounded-lg font-label-lg text-label-lg"
                        :class="
                            link.active
                                ? 'bg-secondary-container text-on-secondary-container font-semibold'
                                : 'text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface'
                        "
                        v-text="link.label"
                    />

                    <!-- Dropdown Trainer -->
                    <div v-if="trainerLinks.length" class="relative">
                        <button
                            ref="trainerBtn"
                            @click="trainerOpen = !trainerOpen"
                            type="button"
                            class="flex items-center gap-1.5 px-space-md py-space-sm rounded-lg font-label-lg text-label-lg transition-colors"
                            :class="
                                trainerActive
                                    ? 'bg-secondary-container text-on-secondary-container font-semibold'
                                    : 'text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface'
                            "
                        >
                            <span>Trainer</span>
                            <svg
                                class="w-4 h-4 transition-transform"
                                :class="{ 'rotate-180': trainerOpen }"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 9l-7 7-7-7"
                                />
                            </svg>
                        </button>
                        <Transition
                            enter-active-class="transition ease-out duration-200"
                            enter-from-class="opacity-0 scale-95"
                            enter-to-class="opacity-100 scale-100"
                            leave-active-class="transition ease-in duration-75"
                            leave-from-class="opacity-100 scale-100"
                            leave-to-class="opacity-0 scale-95"
                        >
                            <div
                                v-show="trainerOpen"
                                class="absolute left-0 mt-2 w-56 bg-surface-container rounded-xl shadow-xl border border-surface-variant overflow-hidden z-50"
                            >
                                <div class="py-2">
                                    <a
                                        v-for="link in trainerLinks"
                                        :key="link.href"
                                        :href="link.href"
                                        class="flex items-center gap-3 px-4 py-3 text-sm transition-colors"
                                        :class="
                                            link.active
                                                ? 'bg-secondary-container/40 text-secondary'
                                                : 'text-on-surface hover:bg-surface-container-high'
                                        "
                                    >
                                        <span class="text-lg" v-text="link.icon" />
                                        <span class="font-medium" v-text="link.label" />
                                    </a>
                                </div>
                            </div>
                        </Transition>
                    </div>

                    <!-- Dropdown Admin -->
                    <div v-if="adminLinks.length" class="relative">
                        <button
                            ref="adminBtn"
                            @click="adminOpen = !adminOpen"
                            type="button"
                            class="flex items-center gap-1.5 px-space-md py-space-sm rounded-lg font-label-lg text-label-lg transition-colors"
                            :class="
                                adminActive
                                    ? 'bg-error-container text-error font-semibold'
                                    : 'text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface'
                            "
                        >
                            <span>Admin</span>
                            <svg
                                class="w-4 h-4 transition-transform"
                                :class="{ 'rotate-180': adminOpen }"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 9l-7 7-7-7"
                                />
                            </svg>
                        </button>
                        <Transition
                            enter-active-class="transition ease-out duration-200"
                            enter-from-class="opacity-0 scale-95"
                            enter-to-class="opacity-100 scale-100"
                            leave-active-class="transition ease-in duration-75"
                            leave-from-class="opacity-100 scale-100"
                            leave-to-class="opacity-0 scale-95"
                        >
                            <div
                                v-show="adminOpen"
                                class="absolute left-0 mt-2 w-60 bg-surface-container rounded-xl shadow-xl border border-surface-variant overflow-hidden z-50"
                            >
                                <div class="py-2">
                                    <a
                                        v-for="link in adminLinks"
                                        :key="link.href"
                                        :href="link.href"
                                        class="flex items-center gap-3 px-4 py-3 text-sm transition-colors"
                                        :class="
                                            link.active
                                                ? 'bg-error-container/40 text-error'
                                                : 'text-on-surface hover:bg-surface-container-high'
                                        "
                                    >
                                        <span class="text-lg" v-text="link.icon" />
                                        <span class="font-medium" v-text="link.label" />
                                    </a>
                                </div>
                            </div>
                        </Transition>
                    </div>
                </div>
            </div>

            <!-- Menu usuario desktop -->
            <div class="flex items-center gap-space-md">
                <button
                    @click="toggleTheme"
                    aria-label="Cambiar tema"
                    type="button"
                    class="w-10 h-10 rounded-xl bg-surface-container flex items-center justify-center text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface transition-colors"
                >
                    <span class="material-symbols-outlined text-[20px]" v-text="isDark ? 'dark_mode' : 'light_mode'" />
                </button>

                <a
                    aria-label="Ajustes rapidos"
                    :href="settingsHref"
                    class="w-10 h-10 rounded-xl bg-surface-container flex items-center justify-center text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface transition-colors"
                >
                    <span class="material-symbols-outlined text-[20px]">settings</span>
                </a>

                <div class="h-8 w-px bg-surface-variant hidden sm:block"></div>

                <div ref="userMenu" class="relative">
                    <button
                        @click="userOpen = !userOpen"
                        type="button"
                        class="flex items-center gap-space-sm pl-space-xs py-1 rounded-xl hover:bg-surface-container/60 transition-colors"
                    >
                        <div class="hidden sm:flex flex-col text-right">
                            <span class="font-label-md text-label-md text-on-surface leading-tight" v-text="userName" />
                            <span class="font-label-sm text-label-sm text-tertiary leading-tight">Atleta Élite</span>
                        </div>
                        <div class="w-8 h-8 rounded-full bg-primary flex items-center justify-center">
                            <span class="material-symbols-outlined text-on-primary text-[18px]">person</span>
                        </div>
                    </button>

                    <Transition
                        enter-active-class="transition ease-out duration-200"
                        enter-from-class="opacity-0 scale-95"
                        enter-to-class="opacity-100 scale-100"
                        leave-active-class="transition ease-in duration-75"
                        leave-from-class="opacity-100 scale-100"
                        leave-to-class="opacity-0 scale-95"
                    >
                        <div
                            v-show="userOpen"
                            class="absolute right-0 mt-2 w-56 bg-surface-container rounded-xl shadow-2xl border border-surface-variant overflow-hidden z-50"
                        >
                            <div
                                class="px-4 py-3 bg-gradient-to-br from-secondary-container to-primary-container relative overflow-hidden"
                            >
                                <div class="relative flex items-center gap-3">
                                    <div
                                        class="w-10 h-10 bg-surface/20 rounded-full flex items-center justify-center backdrop-blur-sm"
                                    >
                                        <span class="text-white font-bold" v-text="initial" />
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-white" v-text="userName" />
                                        <p class="text-xs text-white/75" v-text="userEmail" />
                                    </div>
                                </div>
                            </div>

                            <div class="py-2">
                                <a
                                    :href="settingsHref"
                                    class="flex items-center gap-3 px-4 py-3 text-on-surface hover:bg-surface-container-high transition-colors"
                                >
                                    <span class="material-symbols-outlined text-outline text-[20px]">settings</span>
                                    <span class="font-medium text-sm">Configuración</span>
                                </a>

                                <a
                                    :href="profileHref"
                                    class="flex items-center gap-3 px-4 py-3 text-on-surface hover:bg-surface-container-high transition-colors"
                                >
                                    <span class="material-symbols-outlined text-outline text-[20px]">person</span>
                                    <span class="font-medium text-sm">Perfil</span>
                                </a>

                                <hr class="my-2 border-surface-variant" />

                                <form method="POST" :action="logoutUrl">
                                    <input type="hidden" name="_token" :value="csrfToken" />
                                    <button
                                        type="submit"
                                        class="w-full flex items-center gap-3 px-4 py-3 text-error hover:bg-error-container/20 transition-colors"
                                    >
                                        <span class="material-symbols-outlined text-[20px]">logout</span>
                                        <span class="font-medium text-sm">Cerrar Sesión</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </Transition>
                </div>
            </div>
        </div>
    </nav>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import GlobalSearch from './GlobalSearch.vue';
import { useClickOutside } from '../composables/useClickOutside';
import theme from '../utils/theme';

/**
 * Props del componente.
 *
 * Llegan como el SEGUNDO ARGUMENTO de `createApp(Component, props)` desde
 * `resources/js/app.js`, NO como atributos del elemento.
 *
 * Por que: Vue NO lee los atributos del elemento del DOM como props cuando el
 * componente se registra globalmente y se monta sobre markup de Blade (eso
 * solo funciona en templates compilados). Con `links='[...]'` la prop llegaba
 * como string sin parsear; con `:links="..."` Vue descartaba el atributo por no
 * ser una expresion valida. En ambos casos el menu de links salia VACIO.
 *
 * Blade arma UN objeto JSON y lo pasa por `data-props`; `app.js` lo parsea y
 * lo entrega como props del root.
 */
const props = defineProps({
    links: { type: Array, default: () => [] },
    trainerLinks: { type: Array, default: () => [] },
    adminLinks: { type: Array, default: () => [] },
    userName: { type: String, default: '' },
    userEmail: { type: String, default: '' },
    settingsHref: { type: String, default: '' },
    profileHref: { type: String, default: '' },
    logoutUrl: { type: String, default: '' },
    csrfToken: { type: String, default: '' },
    dashboardHref: { type: String, default: '' },
    historialHref: { type: String, default: '' },
    activeRoute: { type: String, default: '' },
});

// Los links llegan YA parseados (arrays) desde app.js.

const trainerOpen = ref(false);
const adminOpen = ref(false);
const userOpen = ref(false);
const isDark = ref(theme.isDark());
let unsubscribeTheme = null;

// Refs de los contenedores de los dropdowns (para el click-outside).
const trainerBtn = ref(null);
const adminBtn = ref(null);
const userMenu = ref(null);

// Cierra el dropdown correspondiente cuando se hace click fuera.
// (En Alpine esto era `@click.away`, que no existe en Vue 3).
useClickOutside(trainerBtn, () => (trainerOpen.value = false), trainerOpen);
useClickOutside(adminBtn, () => (adminOpen.value = false), adminOpen);
useClickOutside(userMenu, () => (userOpen.value = false), userOpen);

const trainerActive = computed(() => props.trainerLinks.some((l) => l.active));
const adminActive = computed(() => props.adminLinks.some((l) => l.active));
const initial = computed(() => (props.userName || '?').charAt(0).toUpperCase());

const toggleTheme = () => {
    theme.cycle();
};

onMounted(() => {
    isDark.value = theme.isDark();
    unsubscribeTheme = theme.onChange(({ isDark: nextDark }) => {
        isDark.value = nextDark;
    });
});

onBeforeUnmount(() => {
    if (unsubscribeTheme) {
        unsubscribeTheme();
    }
});
</script>
