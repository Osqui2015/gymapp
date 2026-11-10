import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import theme from './theme';

describe('theme utility', () => {
    let matchMediaListeners = [];

    beforeEach(() => {
        localStorage.clear();
        document.documentElement.className = '';
        delete document.documentElement.dataset.theme;
        document.documentElement.style.colorScheme = '';
        matchMediaListeners = [];

        window.matchMedia = vi.fn().mockImplementation((query) => ({
            matches: false,
            media: query,
            onchange: null,
            addListener: vi.fn(),
            removeListener: vi.fn(),
            addEventListener: vi.fn((event, handler) => {
                matchMediaListeners.push(handler);
            }),
            removeEventListener: vi.fn(),
            dispatchEvent: vi.fn(),
        }));
    });

    afterEach(() => {
        vi.restoreAllMocks();
    });

    it('inicia en modo auto por defecto', () => {
        expect(theme.get()).toBe('auto');
        expect(theme.effective()).toBe('light'); // system prefers dark is false
        expect(theme.isDark()).toBe(false);
    });

    it('setea modo dark correctamente', () => {
        theme.set('dark');
        expect(theme.get()).toBe('dark');
        expect(theme.effective()).toBe('dark');
        expect(theme.isDark()).toBe(true);
        expect(localStorage.getItem('theme_mode')).toBe('dark');
        expect(document.documentElement.classList.contains('dark')).toBe(true);
        expect(document.documentElement.dataset.theme).toBe('dark');
        expect(document.documentElement.style.colorScheme).toBe('dark');
    });

    it('setea modo light correctamente', () => {
        theme.set('light');
        expect(theme.get()).toBe('light');
        expect(theme.effective()).toBe('light');
        expect(theme.isDark()).toBe(false);
        expect(localStorage.getItem('theme_mode')).toBe('light');
        expect(document.documentElement.classList.contains('dark')).toBe(false);
        expect(document.documentElement.dataset.theme).toBe('light');
        expect(document.documentElement.style.colorScheme).toBe('light');
    });

    it('cycle() rota en orden auto -> light -> dark -> auto', () => {
        theme.set('auto');
        expect(theme.cycle()).toBe('light');
        expect(theme.get()).toBe('light');

        expect(theme.cycle()).toBe('dark');
        expect(theme.get()).toBe('dark');

        expect(theme.cycle()).toBe('auto');
        expect(theme.get()).toBe('auto');
    });

    it('notifica a los suscriptores con onChange', () => {
        const spy = vi.fn();
        const unsubscribe = theme.onChange(spy);

        theme.set('dark');
        expect(spy).toHaveBeenCalledWith({
            mode: 'dark',
            effective: 'dark',
            isDark: true,
        });

        unsubscribe();
        theme.set('light');
        expect(spy).toHaveBeenCalledTimes(1);
    });

    it('migra la clave legacy "theme" a "theme_mode"', () => {
        localStorage.setItem('theme', 'dark');
        theme.init({ force: true });
        expect(localStorage.getItem('theme_mode')).toBe('dark');
        expect(localStorage.getItem('theme')).toBeNull();
    });

    it('expone window.darkMode para compatibilidad', () => {
        theme.init();
        expect(window.darkMode).toBeDefined();
        expect(typeof window.darkMode.isDark).toBe('function');
        expect(typeof window.darkMode.toggle).toBe('function');
    });

    it('la lógica de anti-FOUC inline produce exactamente la misma decisión que theme.effective()', () => {
        const scenarios = [
            { stored: 'auto', prefersDark: true, expectedDark: true },
            { stored: 'auto', prefersDark: false, expectedDark: false },
            { stored: 'dark', prefersDark: false, expectedDark: true },
            { stored: 'light', prefersDark: true, expectedDark: false },
            { stored: null, prefersDark: true, expectedDark: true },
            { stored: null, prefersDark: false, expectedDark: false },
        ];

        for (const s of scenarios) {
            localStorage.clear();
            if (s.stored) localStorage.setItem('theme_mode', s.stored);

            window.matchMedia = vi.fn().mockReturnValue({ matches: s.prefersDark });

            // 1. Decisión de theme.js
            const themeDecision = theme.effective() === 'dark';

            // 2. Réplica exacta de la fórmula inline de anti-FOUC en app.blade.php
            const m = localStorage.getItem('theme_mode') || localStorage.getItem('theme') || 'auto';
            const p = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            const inlineDecision = m === 'dark' || (m === 'auto' && p);

            expect(themeDecision).toBe(s.expectedDark);
            expect(inlineDecision).toBe(themeDecision);
        }
    });
});
