/**
 * theme.js — Gestor único y centralizado del tema (Light / Dark / Auto).
 *
 * Responsabilidades:
 * - Única fuente de verdad para el modo ('auto' | 'light' | 'dark').
 * - Sincroniza localStorage ('theme_mode'), documentElement.classList ('dark'),
 *   dataset.theme ('light'|'dark') y documentElement.style.colorScheme ('light'|'dark').
 * - Escucha cambios en tiempo real del sistema operativo (prefers-color-scheme).
 * - Notifica a componentes suscritos mediante callbacks y CustomEvent 'theme:changed'.
 */

const STORAGE_KEY = 'theme_mode';
const LEGACY_KEY = 'theme';
const VALID_MODES = ['auto', 'light', 'dark'];

const listeners = new Set();
let mediaQuery = null;
let initialized = false;

function hasWindow() {
    return typeof window !== 'undefined' && typeof document !== 'undefined';
}

function getSystemPrefersDark() {
    if (!hasWindow() || !window.matchMedia) return false;
    return window.matchMedia('(prefers-color-scheme: dark)').matches;
}

function migrateLegacyKey() {
    if (!hasWindow()) return;
    try {
        const current = localStorage.getItem(STORAGE_KEY);
        const legacy = localStorage.getItem(LEGACY_KEY);
        if (!current && legacy) {
            if (legacy === 'dark' || legacy === 'light') {
                localStorage.setItem(STORAGE_KEY, legacy);
            }
        }
        if (legacy) {
            localStorage.removeItem(LEGACY_KEY);
        }
    } catch {
        /* ignore localStorage quota/disabled errors */
    }
}

function readStoredMode() {
    if (!hasWindow()) return 'auto';
    try {
        const stored = localStorage.getItem(STORAGE_KEY);
        if (stored && VALID_MODES.includes(stored)) {
            return stored;
        }
    } catch {
        /* ignore */
    }
    return 'auto';
}

function writeStoredMode(mode) {
    if (!hasWindow()) return;
    try {
        localStorage.setItem(STORAGE_KEY, mode);
    } catch {
        /* ignore */
    }
}

export function get() {
    return readStoredMode();
}

export function effective() {
    const mode = get();
    if (mode === 'auto') {
        return getSystemPrefersDark() ? 'dark' : 'light';
    }
    return mode;
}

export function isDark() {
    return effective() === 'dark';
}

function updateThemeColor(effectiveMode) {
    if (!hasWindow()) return;
    const color = effectiveMode === 'dark' ? '#090D16' : '#F5F6FA';
    // Si hay un meta sin media o el primero existente, actualizamos su contenido
    const meta = document.querySelector('meta[name="theme-color"]:not([media])') ||
                 document.querySelector('meta[name="theme-color"]');
    if (meta) {
        meta.setAttribute('content', color);
    }
}

function applyToDOM(effectiveMode) {
    if (!hasWindow()) return;
    const root = document.documentElement;
    const dark = effectiveMode === 'dark';

    if (dark) {
        root.classList.add('dark');
    } else {
        root.classList.remove('dark');
    }

    root.dataset.theme = effectiveMode;
    root.style.colorScheme = effectiveMode;
    updateThemeColor(effectiveMode);
}

function notify(mode, effectiveMode) {
    const payload = {
        mode,
        effective: effectiveMode,
        isDark: effectiveMode === 'dark',
    };

    listeners.forEach((cb) => {
        try {
            cb(payload);
        } catch (err) {
            console.error('[theme] Error in theme listener:', err);
        }
    });

    if (hasWindow()) {
        window.dispatchEvent(new CustomEvent('theme:changed', { detail: payload }));
    }
}

export function set(mode) {
    if (!VALID_MODES.includes(mode)) {
        console.warn(`[theme] Modo inválido: "${mode}". Se esperaba uno de: ${VALID_MODES.join(', ')}`);
        return;
    }

    writeStoredMode(mode);
    const eff = mode === 'auto' ? (getSystemPrefersDark() ? 'dark' : 'light') : mode;
    applyToDOM(eff);
    notify(mode, eff);
}

export function cycle() {
    const current = get();
    // Ciclo ordenado: auto -> light -> dark -> auto
    const cycleMap = {
        auto: 'light',
        light: 'dark',
        dark: 'auto',
    };
    const next = cycleMap[current] || 'auto';
    set(next);
    return next;
}

export function onChange(callback) {
    if (typeof callback !== 'function') return () => {};
    listeners.add(callback);
    return () => {
        listeners.delete(callback);
    };
}

function onSystemChange() {
    const mode = get();
    if (mode === 'auto') {
        const eff = getSystemPrefersDark() ? 'dark' : 'light';
        applyToDOM(eff);
        notify(mode, eff);
    }
}

export function init({ force = false } = {}) {
    if ((initialized && !force) || !hasWindow()) return;
    initialized = true;

    migrateLegacyKey();

    // Listener para cambios de OS en vivo
    if (window.matchMedia) {
        mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        if (mediaQuery.addEventListener) {
            mediaQuery.addEventListener('change', onSystemChange);
        } else if (mediaQuery.addListener) {
            // Safari antiguo
            mediaQuery.addListener(onSystemChange);
        }
    }

    // Aplicar estado inicial
    const currentMode = get();
    const eff = effective();
    applyToDOM(eff);

    // Compatibilidad retroactiva hacia window.darkMode (usado en tests y scripts existentes)
    window.darkMode = {
        isDark: () => effective() === 'dark',
        toggle: () => cycle(),
    };
}

// Auto-inicializar si se ejecuta en browser
if (hasWindow()) {
    init();
}

export default {
    get,
    set,
    cycle,
    effective,
    isDark,
    onChange,
    init,
};
