<!--
  MobileBottomNav — Barra de navegacion inferior + sheet de menu (mobile).

  Reemplaza el partial Blade `components/mobile-bottom-nav.blade.php`, que
  usaba Alpine (`x-data="{ menuOpen: false }"`) para un solo booleano.

  Alpine ocupaba 44,8 KB del bundle INICIAL (16 KB gzip) solo para esto y
  para los dropdowns de la navbar.

  Blade sigue armando las URLs y decide que items ve cada rol.
-->
<template>
    <div class="md:hidden fixed bottom-0 left-0 right-0 z-40 font-sans">
        <!-- Backdrop del sheet "Menu" -->
        <Transition
            enter-active-class="transition-opacity ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-show="menuOpen"
                @click="menuOpen = false"
                class="fixed inset-0 bg-black/70 backdrop-blur-sm"
            ></div>
        </Transition>

        <!-- Sheet deslizable (GymApp Telemetry Pro) -->
        <Transition
            enter-active-class="transition ease-out duration-300 transform"
            enter-from-class="translate-y-full"
            enter-to-class="translate-y-0"
            leave-active-class="transition ease-in duration-200 transform"
            leave-from-class="translate-y-0"
            leave-to-class="translate-y-full"
        >
            <div
                v-show="menuOpen"
                class="fixed bottom-0 left-0 right-0 bg-[#131924] rounded-t-3xl shadow-2xl border-t border-[#232D42] max-h-[85vh] overflow-y-auto text-slate-200"
            >
                <!-- Handle visual -->
                <div class="flex justify-center pt-3 pb-1">
                    <div class="w-10 h-1.5 bg-[#232D42] rounded-full"></div>
                </div>

                <div class="px-4 pt-2 pb-24">
                    <!-- Header del usuario (Telemetry Pro gradient) -->
                    <div
                        class="flex items-center gap-3 p-3.5 mb-4 bg-gradient-to-r from-indigo-600/25 via-violet-600/20 to-[#18202F] border border-indigo-500/30 rounded-2xl relative overflow-hidden"
                    >
                        <div
                            class="relative w-11 h-11 bg-gradient-to-tr from-indigo-600 to-violet-500 rounded-xl flex items-center justify-center shadow-lg shadow-indigo-600/30"
                        >
                            <span class="text-white font-bold text-lg" v-text="initial" />
                        </div>
                        <div class="relative flex-1 min-w-0">
                            <p class="text-sm font-bold text-white truncate" v-text="userName" />
                            <p class="text-xs text-indigo-300 font-mono uppercase tracking-wider" v-text="roleLabel" />
                        </div>
                        <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/30">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                            <span class="text-[10px] font-mono text-emerald-300 uppercase">Online</span>
                        </div>
                    </div>

                    <!-- Items de navegacion primaria -->
                    <p class="px-2 mb-2 text-[10px] font-bold uppercase tracking-widest text-slate-400">Navegación</p>
                    <nav class="space-y-1 mb-4">
                        <a
                            v-for="item in primary"
                            :key="item.href"
                            :href="item.href"
                            @click="menuOpen = false"
                            class="flex items-center gap-3 px-3.5 py-3 rounded-xl text-sm font-semibold transition-colors"
                            :class="
                                item.active
                                    ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30'
                                    : 'text-slate-200 hover:bg-[#18202F]'
                            "
                        >
                            <span
                                class="material-symbols-outlined text-[20px]"
                                :class="item.active ? 'text-indigo-400' : 'text-slate-400'"
                                v-text="item.icon"
                            />
                            <span v-text="item.label" />
                            <span v-if="item.active" class="ml-auto w-2 h-2 bg-indigo-400 rounded-full"></span>
                        </a>

                        <a
                            :href="ejerciciosHref"
                            @click="menuOpen = false"
                            class="flex items-center gap-3 px-3.5 py-3 rounded-xl text-sm font-semibold transition-colors"
                            :class="
                                ejerciciosActive
                                    ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30'
                                    : 'text-slate-200 hover:bg-[#18202F]'
                            "
                        >
                            <span
                                class="material-symbols-outlined text-[20px]"
                                :class="ejerciciosActive ? 'text-indigo-400' : 'text-slate-400'"
                                >fitness_center</span
                            >
                            <span>Biblioteca de Ejercicios</span>
                            <span v-if="ejerciciosActive" class="ml-auto w-2 h-2 bg-indigo-400 rounded-full"></span>
                        </a>
                    </nav>

                    <!-- Trainer -->
                    <template v-if="trainerLinks.length">
                        <p class="px-2 mb-2 text-[10px] font-bold uppercase tracking-widest text-indigo-400">Panel Trainer</p>
                        <nav class="space-y-1 mb-4">
                            <a
                                v-for="item in trainerLinks"
                                :key="item.href"
                                :href="item.href"
                                @click="menuOpen = false"
                                class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-colors text-slate-200 hover:bg-[#18202F]"
                            >
                                <span class="material-symbols-outlined text-[18px] text-indigo-400" v-text="item.icon" />
                                <span v-text="item.label" />
                            </a>
                        </nav>
                    </template>

                    <!-- Admin -->
                    <template v-if="adminLinks.length">
                        <p class="px-2 mb-2 text-[10px] font-bold uppercase tracking-widest text-rose-400">Panel Admin</p>
                        <nav class="space-y-1 mb-4">
                            <a
                                v-for="item in adminLinks"
                                :key="item.href"
                                :href="item.href"
                                @click="menuOpen = false"
                                class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-colors text-slate-200 hover:bg-[#18202F]"
                            >
                                <span class="material-symbols-outlined text-[18px] text-rose-400" v-text="item.icon" />
                                <span v-text="item.label" />
                            </a>
                        </nav>
                    </template>

                    <!-- Cuenta -->
                    <p class="px-2 mb-2 text-[10px] font-bold uppercase tracking-widest text-slate-400">Cuenta</p>
                    <nav class="space-y-1">
                        <a
                            :href="settingsHref"
                            @click="menuOpen = false"
                            class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-colors text-slate-200 hover:bg-[#18202F]"
                        >
                            <span class="material-symbols-outlined text-[18px] text-slate-400">settings</span>
                            <span>Configuración</span>
                        </a>
                        <a
                            :href="profileHref"
                            @click="menuOpen = false"
                            class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-colors text-slate-200 hover:bg-[#18202F]"
                        >
                            <span class="material-symbols-outlined text-[18px] text-slate-400">person</span>
                            <span>Perfil</span>
                        </a>
                        <form method="POST" :action="logoutUrl">
                            <input type="hidden" name="_token" :value="csrfToken" />
                            <button
                                type="submit"
                                class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-colors text-rose-400 hover:bg-rose-500/10 cursor-pointer"
                            >
                                <span class="material-symbols-outlined text-[18px]">logout</span>
                                <span>Cerrar Sesión</span>
                            </button>
                        </form>
                    </nav>
                </div>
            </div>
        </Transition>

        <!-- Barra inferior persistente (GymApp Telemetry Pro) -->
        <nav
            class="bg-[#0B0F17]/95 backdrop-blur-xl border-t border-[#232D42] pb-safe shadow-[0_-8px_30px_rgba(0,0,0,0.6)]"
            data-purpose="bottom-persistent-navigation"
        >
            <div class="max-w-md mx-auto px-3 py-1.5">
                <div class="grid grid-cols-5 gap-1 items-center justify-items-center">
                    <template v-for="item in primary" :key="item.href">
                        <a
                            v-if="item.active"
                            :href="item.href"
                            class="flex flex-col items-center justify-center py-1 px-2.5 rounded-xl text-white relative group w-full"
                        >
                            <div
                                class="absolute -top-1.5 w-8 h-1 rounded-full bg-indigo-500 shadow-[0_0_12px_rgba(99,102,241,0.8)]"
                            ></div>
                            <div
                                class="w-9 h-7 rounded-lg bg-indigo-600/25 border border-indigo-500/40 flex items-center justify-center text-indigo-300 mb-0.5"
                            >
                                <span class="material-symbols-outlined text-[19px]" v-text="item.icon" />
                            </div>
                            <span class="text-[10px] font-bold tracking-tight text-white" v-text="item.label" />
                        </a>
                        <a
                            v-else
                            :href="item.href"
                            class="flex flex-col items-center justify-center py-1 px-2 rounded-xl text-slate-400 hover:text-slate-200 transition-colors group w-full"
                        >
                            <div class="w-9 h-7 flex items-center justify-center">
                                <span
                                    class="material-symbols-outlined text-[20px] group-hover:scale-110 transition-transform"
                                    v-text="item.icon"
                                />
                            </div>
                            <span class="text-[10px] font-medium tracking-tight" v-text="item.label" />
                        </a>
                    </template>

                    <!-- Boton "Menu" (abre el sheet) -->
                    <button
                        type="button"
                        @click="menuOpen = !menuOpen"
                        class="flex flex-col items-center justify-center py-1 px-2 rounded-xl transition-colors group relative w-full cursor-pointer"
                        :class="menuOpen ? 'text-indigo-400' : 'text-slate-400 hover:text-slate-200'"
                    >
                        <span
                            v-if="hasBadge"
                            class="absolute top-1 right-3 w-2 h-2 rounded-full ring-2 ring-[#0B0F17]"
                            :class="badgeColor"
                        ></span>
                        <div class="w-9 h-7 flex items-center justify-center">
                            <span
                                class="material-symbols-outlined text-[20px] group-hover:scale-110 transition-transform"
                                >menu</span
                            >
                        </div>
                        <span class="text-[10px] font-medium tracking-tight">Menú</span>
                    </button>
                </div>
            </div>
        </nav>
    </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
    primary: { type: Array, default: () => [] },
    trainerLinks: { type: Array, default: () => [] },
    adminLinks: { type: Array, default: () => [] },
    userName: { type: String, default: '' },
    roleLabel: { type: String, default: '' },
    settingsHref: { type: String, required: true },
    profileHref: { type: String, required: true },
    logoutUrl: { type: String, required: true },
    csrfToken: { type: String, default: '' },
    ejerciciosHref: { type: String, required: true },
    ejerciciosActive: { type: Boolean, default: false },
    isStaff: { type: Boolean, default: false },
    isAdmin: { type: Boolean, default: false },
});

const menuOpen = ref(false);

const initial = computed(() => (props.userName || '?').charAt(0).toUpperCase());
const hasBadge = computed(() => props.isAdmin || props.isStaff);
const badgeColor = computed(() => (props.isAdmin ? 'bg-rose-500' : 'bg-violet-500'));

// Escape cierra el sheet.
// En Vue 3 no existe `@keydown.escape.window` (era de Alpine/Vue 2): el
// listener se registra sobre `window` para que funcione aunque el foco esté
// en otro elemento de la pagina.
const onKeydown = (e) => {
    if (e.key === 'Escape' && menuOpen.value) {
        menuOpen.value = false;
    }
};

onMounted(() => window.addEventListener('keydown', onKeydown));
onBeforeUnmount(() => window.removeEventListener('keydown', onKeydown));
</script>
