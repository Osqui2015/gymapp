/**
 * Keyboard shortcuts estilo Gmail:
 *   g + d = Dashboard
 *   g + r = Rutinas
 *   g + e = Ejercicios
 *   g + h = Historial
 *   g + p = Progreso
 *   g + a = Alumnos (trainer/admin)
 *   g + s = Configuración
 *   /     = Focus en la búsqueda global
 *   n     = Nueva rutina (si está en /rutinas o /crear-rutina)
 *   ?     = Mostrar ayuda de shortcuts
 *   esc   = Cerrar modales/dropdowns
 *
 * Se activa solo si el usuario NO está escribiendo en un input.
 */

const SHORTCUTS = {
    gd: { route: 'dashboard', label: 'Ir al Dashboard' },
    gr: { route: 'rutinas', label: 'Ir a Rutinas' },
    ge: { route: 'ejercicios', label: 'Ir a Ejercicios' },
    gh: { route: 'historial', label: 'Ir a Historial' },
    gp: { route: 'progreso', label: 'Ir a Progreso' },
    ga: { route: 'trainer.alumnos', label: 'Ir a Alumnos' },
    gs: { route: 'configuracion', label: 'Ir a Configuración' },
};

const SINGLE_KEY_SHORTCUTS = {
    '/': { label: 'Buscar', handler: () => focusSearch() },
};

const isTypingTarget = (el) => {
    if (!el) return false;
    const tag = el.tagName;
    return tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || el.isContentEditable;
};

const focusSearch = () => {
    // Busca el input de GlobalSearch (es el primero con placeholder que contiene "Buscar")
    const input = document.querySelector('input[placeholder*="Buscar"]');
    if (input) {
        input.focus();
        input.select?.();
    } else {
        // Si no hay search visible, navega a una página con search
        window.location.href = '/dashboard';
    }
};

export function setupKeyboardShortcuts() {
    let pendingG = false;
    let pendingTimer = null;

    const handler = (e) => {
        // Permitir shortcuts siempre (no solo cuando no se escribe), pero ignorar si es dentro de un input
        if (isTypingTarget(e.target)) return;
        if (e.metaKey || e.ctrlKey || e.altKey) return;

        const key = e.key.toLowerCase();

        // Mostrar ayuda con ?
        if (key === '?' && !pendingG) {
            e.preventDefault();
            mostrarAyuda();
            return;
        }

        // Shortcuts de una sola tecla (/, esc, etc.)
        if (SINGLE_KEY_SHORTCUTS[key]) {
            e.preventDefault();
            SINGLE_KEY_SHORTCUTS[key].handler();
            return;
        }

        // Inicio de secuencia "g"
        if (key === 'g' && !pendingG) {
            pendingG = true;
            clearTimeout(pendingTimer);
            pendingTimer = setTimeout(() => { pendingG = false; }, 1000);
            return;
        }

        // Segunda tecla de la secuencia
        if (pendingG) {
            const combo = 'g' + key;
            const shortcut = SHORTCUTS[combo];
            if (shortcut) {
                e.preventDefault();
                pendingG = false;
                clearTimeout(pendingTimer);
                navegarA(shortcut.route);
            }
        }
    };

    document.addEventListener('keydown', handler);

    return () => {
        document.removeEventListener('keydown', handler);
        clearTimeout(pendingTimer);
    };
}

const navegarA = (routeName) => {
    // Construir URL desde el nombre de ruta usando la función route() de Ziggy si está disponible,
    // o ir a una URL segura con fallback
    if (typeof window.route === 'function') {
        try {
            const url = window.route(routeName);
            window.location.href = url;
            return;
        } catch (e) {
            console.warn('No se pudo resolver la ruta', routeName, e);
        }
    }
    // Fallback: navegación manual
    const fallbacks = {
        'dashboard': '/dashboard',
        'rutinas': '/rutinas',
        'ejercicios': '/ejercicios',
        'historial': '/historial',
        'progreso': '/progreso',
        'trainer.alumnos': '/trainer/alumnos',
        'configuracion': '/configuracion',
    };
    if (fallbacks[routeName]) {
        window.location.href = fallbacks[routeName];
    }
};

const mostrarAyuda = () => {
    // Construir el modal simple
    const existing = document.getElementById('shortcuts-help-modal');
    if (existing) {
        existing.remove();
        return;
    }

    const modal = document.createElement('div');
    modal.id = 'shortcuts-help-modal';
    modal.className = 'fixed inset-0 z-[200] flex items-center justify-center px-4';
    modal.innerHTML = `
        <div class="fixed inset-0 bg-gray-900/75 backdrop-blur-sm" onclick="document.getElementById('shortcuts-help-modal').remove()"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 p-6 max-w-md w-full" role="dialog" aria-modal="true">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">⌨️ Atajos de teclado</h3>
                <button onclick="document.getElementById('shortcuts-help-modal').remove()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <ul class="space-y-2 text-sm">
                ${Object.entries(SHORTCUTS).map(([key, { label }]) => `
                    <li class="flex items-center justify-between gap-3">
                        <span class="text-gray-700 dark:text-gray-300">${label}</span>
                        <span><kbd class="px-2 py-0.5 font-mono text-xs bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded border border-gray-300 dark:border-gray-600">g</kbd> <kbd class="px-2 py-0.5 font-mono text-xs bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded border border-gray-300 dark:border-gray-600">${key[1]}</kbd></span>
                    </li>
                `).join('')}
                ${Object.entries(SINGLE_KEY_SHORTCUTS).map(([key, { label }]) => `
                    <li class="flex items-center justify-between gap-3 pt-2 border-t border-gray-200 dark:border-gray-700">
                        <span class="text-gray-700 dark:text-gray-300">${label}</span>
                        <kbd class="px-2 py-0.5 font-mono text-xs bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded border border-gray-300 dark:border-gray-600">${key}</kbd>
                    </li>
                `).join('')}
                <li class="flex items-center justify-between gap-3 pt-2 border-t border-gray-200 dark:border-gray-700">
                    <span class="text-gray-700 dark:text-gray-300">Mostrar esta ayuda</span>
                    <kbd class="px-2 py-0.5 font-mono text-xs bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded border border-gray-300 dark:border-gray-600">?</kbd>
                </li>
            </ul>
            <p class="mt-4 text-xs text-gray-500 dark:text-gray-400">Tip: los shortcuts se desactivan mientras escribís en un input.</p>
        </div>
    `;
    document.body.appendChild(modal);
    // Cerrar con esc
    const escHandler = (e) => {
        if (e.key === 'Escape') {
            modal.remove();
            document.removeEventListener('keydown', escHandler);
        }
    };
    document.addEventListener('keydown', escHandler);
};