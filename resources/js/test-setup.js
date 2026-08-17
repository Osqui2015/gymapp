/**
 * Setup global para tests de Vitest.
 * Carga jsdom polyfills y limpia el estado entre tests.
 */
import { afterEach, beforeEach } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';

const createStorageMock = () => {
    const store = new Map();

    return {
        getItem(key) {
            const value = store.get(String(key));
            return value === undefined ? null : String(value);
        },
        setItem(key, value) {
            store.set(String(key), String(value));
        },
        removeItem(key) {
            store.delete(String(key));
        },
        clear() {
            store.clear();
        },
        key(index) {
            return Array.from(store.keys())[Number(index)] ?? null;
        },
        get length() {
            return store.size;
        },
    };
};

if (!window.localStorage || typeof window.localStorage.clear !== 'function') {
    Object.defineProperty(window, 'localStorage', {
        value: createStorageMock(),
        configurable: true,
    });
}

if (!window.sessionStorage || typeof window.sessionStorage.clear !== 'function') {
    Object.defineProperty(window, 'sessionStorage', {
        value: createStorageMock(),
        configurable: true,
    });
}

// Limpia Pinia entre tests para que cada test arranque con un store fresco.
beforeEach(() => {
    setActivePinia(createPinia());
    if (typeof localStorage?.clear === 'function') {
        localStorage.clear();
    }
    if (typeof sessionStorage?.clear === 'function') {
        sessionStorage.clear();
    }
});

// Limpia localStorage y sessionStorage entre tests.
afterEach(() => {
    try {
        localStorage.clear();
        sessionStorage.clear();
    } catch {
        // ignore: jsdom puede no tener localStorage en algunos contextos
    }
});
