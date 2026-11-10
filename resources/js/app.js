import './bootstrap';

import { createApp, defineAsyncComponent } from 'vue';
import { createPinia } from 'pinia';
import DarkModeToggle from './components/DarkModeToggle.vue';
import GlobalSearch from './components/GlobalSearch.vue';
import AppNavigation from './components/AppNavigation.vue';
import MobileBottomNav from './components/MobileBottomNav.vue';
import AppModal from './components/AppModal.vue';
import RutinaPublica from './components/RutinaPublica.vue';
import { setupGlobalToastListeners } from './composables/useToast';
import { setupKeyboardShortcuts } from './composables/useKeyboardShortcuts';
import { useToastStore } from './stores/toast';

// Componentes del shell global (siempre montados, viven fuera de #app).
import ToastContainer from './components/ToastContainer.vue';
import UpdateAvailablePrompt from './components/UpdateAvailablePrompt.vue';
import DashboardRestTimer from './components/dashboard/DashboardRestTimer.vue';
import OfflineStatusBar from './components/training/OfflineStatusBar.vue';

// Pinia se crea una sola vez y se comparte entre todas las apps Vue.
const pinia = createPinia();

/**
 * Registro de componentes por nombre de tag.
 *
 * ANTES: el código leía `app._context.components[tag]`, que es API INTERNA
 * de Vue (no documentada): puede cambiar entre minors y romper sin aviso.
 *
 * AHORA: mantenemos nuestro propio mapa `registro` con la misma clave por la
 * que se registra cada componente, y lo consultamos para resolver el tag
 * del elemento a montar. `app.component(...)` se sigue haciendo (para que
 * los componentes también puedan usarse desde templates compilados), pero
 * NUNCA se lee `_context`.
 */
const registro = new Map();

// === App principal ===
// Todos los componentes se cargan lazy (code splitting). Cada Blade view
// renderiza su propio componente, así que solo se descarga el necesario.
const app = createApp({});
app.use(pinia);

// === Manejo global de errores de Vue ===
// Sin esto, una excepcion en un componente solo queda en la consola: el
// usuario ve la pantalla a medio renderizar y no sabe que paso. Logueamos
// el error completo y avisamos con un toast.
//
// Anti-spam: si el MISMO mensaje se repite dentro de 5s no se vuelve a
// mostrar el toast (un componente que falla en loop no llena la pantalla).
let lastErrorToast = { message: '', at: 0 };
const registerErrorHandler = (vueApp) => {
    vueApp.config.errorHandler = (err, instance, info) => {
        console.error('[GymApp] error en componente:', err, info);
        const message = err?.message || 'Ocurrió un error inesperado';
        const now = Date.now();
        if (message === lastErrorToast.message && now - lastErrorToast.at < 5000) return;
        lastErrorToast = { message, at: now };
        try {
            useToastStore().error(message, { title: 'Error', duration: 8000 });
        } catch (e) {
            console.warn('[GymApp] no se pudo mostrar el toast de error:', e);
        }
    };
};
registerErrorHandler(app);

// Componentes globales (siempre en el bundle inicial, son pequeños y/o globales)
app.component('dark-mode-toggle', DarkModeToggle);
app.component('global-search', GlobalSearch);
app.component('app-navigation', AppNavigation);
app.component('mobile-bottom-nav', MobileBottomNav);
app.component('app-modal', AppModal);
app.component('rutina-publica', RutinaPublica);
app.component('notification-bell', defineAsyncComponent(() => import('./components/NotificationBell.vue')));

registro.set('dark-mode-toggle', DarkModeToggle);
registro.set('global-search', GlobalSearch);
registro.set('app-navigation', AppNavigation);
registro.set('mobile-bottom-nav', MobileBottomNav);
registro.set('app-modal', AppModal);
registro.set('rutina-publica', RutinaPublica);
registro.set(
    'notification-bell',
    defineAsyncComponent(() => import('./components/NotificationBell.vue'))
);

const components = {
    'dashboard-content': () => import('./components/DashboardContent.vue'),
    'rutinas-accordion': () => import('./components/RutinasAccordion.vue'),
    'ejercicios-list': () => import('./components/EjerciciosList.vue'),
    'crear-rutina': () => import('./components/CrearRutina.vue'),
    'configuracion-panel': () => import('./components/ConfiguracionPanel.vue'),
    'trainer-alumnos': () => import('./components/TrainerAlumnos.vue'),
    'historial-content': () => import('./components/HistorialContent.vue'),
    'progreso-content': () => import('./components/ProgresoContent.vue'),
    'diario-nutricion': () => import('./components/DiarioNutricion.vue'),
    'trainer-dashboard': () => import('./components/TrainerDashboard.vue'),
    'trainer-ejercicios': () => import('./components/TrainerEjercicios.vue'),
    'trainer-duplicar': () => import('./components/TrainerDuplicar.vue'),
    'admin-stats': () => import('./components/AdminStats.vue'),
    'admin-membresias': () => import('./components/AdminMembresias.vue'),
    'admin-audit-logs': () => import('./components/AdminAuditLogs.vue'),
    'admin-import-export': () => import('./components/AdminImportExport.vue'),
    'admin-reports': () => import('./components/AdminReports.vue'),
    'sesiones-calendar': () => import('./components/SesionesCalendar.vue'),
    'rutinas-sugeridas': () => import('./components/RutinasSugeridas.vue'),
    'chat-panel': () => import('./components/ChatPanel.vue'),
    'ejercicio-detail-modal': () => import('./components/EjercicioDetailModal.vue'),
    'video-player': () => import('./components/VideoPlayer.vue'),

    // === VisualGym (catálogo exercises-dataset, 1.324 ejercicios) ===
    'visualgym-catalog': () => import('./components/visualgym/VisualGymCatalog.vue'),
    'page-transition': () => import('./components/PageTransition.vue'),
    'onboarding-tour': () => import('./components/OnboardingTour.vue'),
};

for (const [name, loader] of Object.entries(components)) {
    const component = defineAsyncComponent(loader);
    app.component(name, component);
    registro.set(name, component);
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
        if (tag && tag.includes('-') && registro.has(tag)) {
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
        const Component = registro.get(targetName);
        if (Component) {
            mountApp({ el: target, component: Component });
        } else {
            console.warn('[GymApp] target', targetName, 'not registered in app components');
        }
    } else {
        // Fallback: data-component en el propio #app (compatibilidad con
        // páginas standalone como rutina-publica).
        const dataName = mountEl.dataset.component;
        if (dataName && registro.has(dataName)) {
            mountApp({ el: mountEl, component: registro.get(dataName) });
        }
        // Si no hay componente Vue, dejamos el HTML de Blade intacto.
    }
}

// === Montaje de componentes Vue "standalone" (fuera de #app) ===
//
// La app de pagina se monta sobre el primer custom element dentro de #app.
// Los componentes globales que viven FUERA de #app (navbar, bottom nav
// mobile) NO entran en esa busqueda: hay que montarlos aparte.
//
// Antes de la Tanda 3 la navbar era HTML Blade puro y siempre se renderizaba.
// Al pasarla a Vue sin este montaje, la navbar desaparecia en TODAS las
// paginas (no habia ningun error en consola: simplemente nunca se montaba).
//
// NOTA sobre los props: Vue NO lee los atributos del elemento como props
// cuando el componente se registra globalmente y se monta sobre markup del
// DOM (solo funciona en templates compilados). Por eso los componentes
// reciben UN solo atributo `props` con un JSON, y lo parsean adentro.
// Intentar pasar `links='[...]'` o `:links="[...]"` desde Blade no funciona:
// el primero llega como string sin parsear y el segundo se descarta.
function mountStandalone(rootId, name, propsJson = '{}') {
    const root = document.getElementById(rootId);
    if (!root) return false;

    const Component = registro.get(name);
    if (!Component) {
        console.warn(`[GymApp] componente '${name}' no registrado`);
        return false;
    }

    let props = {};
    try {
        props = JSON.parse(propsJson);
    } catch (e) {
        console.warn(`[GymApp] props de '${name}' no parseables:`, e);
    }

    const soloApp = mountApp({ el: root, component: Component, props });

    return Boolean(soloApp);
}

/**
 * Monta un componente Vue en un elemento, con el Pinia compartido y el
 * handler de errores global.
 *
 * ANTES: cada montaje repetía los mismos 4 pasos (`createApp` + `use(pinia)`
 * + `registerErrorHandler` + `mount`). Con 8 montajes eran 8 copias donde
 * forgets el `use(pinia)` era fácil — y un componente sin Pinia rompe los
 * stores en silencio.
 *
 * @param {object} options
 * @param {Element} options.el        Elemento donde montar.
 * @param {object} options.component   Componente Vue.
 * @param {object} [options.props]     Props iniciales.
 * @returns {object|null} La app montada, o null si no se pudo montar.
 */
function mountApp({ el, component, props = {} }) {
    if (!el || !component) return null;

    const vueApp = createApp(component, props);
    vueApp.use(pinia);
    registerErrorHandler(vueApp);
    vueApp.mount(el);

    return vueApp;
}

// La navbar vive en `layouts.navigation` (fuera de #app).
const navRoot = document.getElementById('navigation-root');
if (navRoot) {
    mountStandalone('navigation-root', 'app-navigation', navRoot.dataset.props || '{}');
}

// La bottom nav mobile vive en `components.mobile-bottom-nav` (fuera de #app).
const bottomNavRoot = document.getElementById('mobile-bottom-nav-root');
if (bottomNavRoot) {
    mountStandalone(
        'mobile-bottom-nav-root',
        'mobile-bottom-nav',
        bottomNavRoot.dataset.props || '{}'
    );
}

// === App de toasts (siempre montada, es el shell global) ===
// Como la usamos en TODAS las páginas, se incluye en el bundle inicial.
mountApp({
    el: document.getElementById('toast-root'),
    component: ToastContainer,
});

// === App de "Update Available" (banner persistente) ===
// Similar a toasts: vive fuera del flujo de paginas para no interrumpir UX.
mountApp({
    el: document.getElementById('update-prompt-root'),
    component: UpdateAvailablePrompt,
});

// === App de Temporizador de Descanso Global (Pinia restTimer) ===
mountApp({
    el: document.getElementById('rest-timer-root'),
    component: DashboardRestTimer,
});

// === App de Estado Offline Global ===
mountApp({
    el: document.getElementById('offline-status-root'),
    component: OfflineStatusBar,
});

// Listeners globales: errores HTTP -> toasts automáticos (403, 500, 422, etc.)
setupGlobalToastListeners();

// Keyboard shortcuts (g+r = rutinas, g+d = dashboard, ? = ayuda)
setupKeyboardShortcuts();
