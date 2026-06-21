import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();

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
};

for (const [name, loader] of Object.entries(components)) {
    app.component(name, defineAsyncComponent(loader));
}

const mountEl = document.getElementById('app');
if (mountEl) {
    // Si el contenedor tiene atributo data-component, montamos ese componente específico
    const componentName = mountEl.dataset.component;
    if (componentName && app._context.components[componentName]) {
        const Component = app._context.components[componentName];
        const soloApp = createApp(Component);
        soloApp.use(pinia);
        soloApp.mount(mountEl);
    } else {
        app.mount(mountEl);
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
