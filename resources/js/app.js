import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;
// Guard contra doble init: Vite HMR puede re-ejecutar este módulo,
// y las directivas `x-data` del layout (navigation.blade.php) ya consumen Alpine.
// Llamar Alpine.start() 2 veces tira warning en consola y puede romper bindings.
if (!window.__alpineStarted) {
    window.__alpineStarted = true;
    Alpine.start();
}

import { createApp, defineAsyncComponent } from 'vue';
import { createPinia } from 'pinia';
import DarkModeToggle from './components/DarkModeToggle.vue';
import GlobalSearch from './components/GlobalSearch.vue';
import RutinaPublica from './components/RutinaPublica.vue';
import { setupGlobalToastListeners } from './composables/useToast';
import { setupKeyboardShortcuts } from './composables/useKeyboardShortcuts';

// Pinia se crea una sola vez y se共享 entre las dos apps Vue.
const pinia = createPinia();

// === App principal ===
// Todos los componentes se cargan lazy (code splitting). Cada Blade view
// renderiza su propio componente, así que solo se descarga el necesario.
const app = createApp({});
app.use(pinia);

// Componentes globales (siempre en el bundle inicial, son pequeños y/o globales)
app.component('dark-mode-toggle', DarkModeToggle);
app.component('global-search', GlobalSearch);
app.component('rutina-publica', RutinaPublica);
app.component('notification-bell', () => import('./components/NotificationBell.vue'));

const components = {
    'dashboard-content':   () => import('./components/DashboardContent.vue'),
    'rutinas-accordion':   () => import('./components/RutinasAccordion.vue'),
    'ejercicios-list':     () => import('./components/EjerciciosList.vue'),
    'crear-rutina':        () => import('./components/CrearRutina.vue'),
    'configuracion-panel': () => import('./components/ConfiguracionPanel.vue'),
    'trainer-alumnos':     () => import('./components/TrainerAlumnos.vue'),
    'historial-content':   () => import('./components/HistorialContent.vue'),
    'progreso-content':    () => import('./components/ProgresoContent.vue'),
    'diario-nutricion':    () => import('./components/DiarioNutricion.vue'),
    'trainer-dashboard':   () => import('./components/TrainerDashboard.vue'),
    'trainer-ejercicios':  () => import('./components/TrainerEjercicios.vue'),
    'trainer-duplicar':    () => import('./components/TrainerDuplicar.vue'),
    'admin-stats':         () => import('./components/AdminStats.vue'),
    'admin-membresias':    () => import('./components/AdminMembresias.vue'),
    'admin-audit-logs':    () => import('./components/AdminAuditLogs.vue'),
    'admin-import-export': () => import('./components/AdminImportExport.vue'),
    'admin-reports':       () => import('./components/AdminReports.vue'),
    'sesiones-calendar':   () => import('./components/SesionesCalendar.vue'),
    'rutinas-sugeridas':   () => import('./components/RutinasSugeridas.vue'),
    'chat-panel':          () => import('./components/ChatPanel.vue'),
    'ejercicio-detail-modal': () => import('./components/EjercicioDetailModal.vue'),
    'video-player':        () => import('./components/VideoPlayer.vue'),
    'page-transition':     () => import('./components/PageTransition.vue'),
    'onboarding-tour':     () => import('./components/OnboardingTour.vue'),
};

for (const [name, loader] of Object.entries(components)) {
    app.component(name, defineAsyncComponent(loader));
}

const mountEl = document.getElementById('app');
if (mountEl) {
    // Buscamos recursivamente el primer custom element de Vue dentro de #app.
    // Blade puede envolver el slot en <div id="main-content"> u otros wrappers,
    // así que no alcanza con mirar solo `firstElementChild` de #app.
    // Antes: `app.mount(mountEl)` reemplazaba todo el contenido de #app con el
    // template del componente raíz (que era `createApp({})` = vacío), borrando
    // el <dashboard-content> que había renderizado Blade.
    function findCustomVueEl(el) {
        if (!el) return null;
        const tag = el.tagName ? el.tagName.toLowerCase() : null;
        if (tag && tag.includes('-') && app._context.components[tag]) {
            return el;
        }
        for (const child of el.children) {
            const found = findCustomVueEl(child);
            if (found) return found;
        }
        return null;
    }

    const target = findCustomVueEl(mountEl);
    if (target) {
        const targetName = target.tagName.toLowerCase();
        const Component = app._context.components[targetName];
        if (Component) {
            const soloApp = createApp(Component);
            soloApp.use(pinia);
            soloApp.mount(target);
        } else {
            console.warn('[GymApp] target', targetName, 'not registered in app components');
        }
    } else {
        // Fallback: data-component en el propio #app (compatibilidad con
        // páginas standalone como rutina-publica).
        const dataName = mountEl.dataset.component;
        if (dataName && app._context.components[dataName]) {
            const Component = app._context.components[dataName];
            const soloApp = createApp(Component);
            soloApp.use(pinia);
            soloApp.mount(mountEl);
        }
        // Si no hay componente Vue, dejamos el HTML de Blade intacto.
    }
}

// === App de toasts (siempre montada, es el shell global) ===
// Como la usamos en TODAS las páginas, se incluye en el bundle inicial.
import ToastContainer from './components/ToastContainer.vue';
const toastRoot = document.getElementById('toast-root');
if (toastRoot) {
    const toastApp = createApp(ToastContainer);
    toastApp.use(pinia);
    toastApp.mount(toastRoot);
}

// Listeners globales: errores HTTP -> toasts automáticos (403, 500, 422, etc.)
setupGlobalToastListeners();

// Keyboard shortcuts (g+r = rutinas, g+d = dashboard, ? = ayuda)
setupKeyboardShortcuts();
