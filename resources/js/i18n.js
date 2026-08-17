/**
 * i18n (internacionalización) — setup minimal con vue-i18n.
 *
 * Idiomas soportados: es (Español, default), en (English).
 *
 * Activación:
 *   npm install vue-i18n@11
 *
 * Uso en un componente:
 *   import { useI18n } from 'vue-i18n';
 *   const { t, locale } = useI18n();
 *   <h1>{{ t('dashboard.welcome', { name: user.name }) }}</h1>
 *   <button @click="locale = 'en'">EN</button>
 *
 * Estructura de las claves: `<sección>.<concepto>`:
 *   - common.save, common.cancel, common.delete
 *   - dashboard.welcome, dashboard.no_data
 *   - auth.login, auth.logout
 *   - errors.network, errors.unauthorized
 *
 * Para agregar un nuevo idioma: copiá `locales/en.json` con las claves
 * traducidas y agregalo a `messages` abajo.
 */
import { createI18n } from 'vue-i18n';
import es from './locales/es.json';
import en from './locales/en.json';

const STORAGE_KEY = 'app_locale';

function detectLocale() {
    // 1) LocalStorage (preferencia del usuario, persiste entre sesiones)
    try {
        const stored = localStorage.getItem(STORAGE_KEY);
        if (stored && ['es', 'en'].includes(stored)) return stored;
    } catch { /* ignore */ }

    // 2) Browser preference
    const browser = navigator.language?.split('-')[0];
    if (['es', 'en'].includes(browser)) return browser;

    // 3) Default
    return 'es';
}

export const SUPPORTED_LOCALES = [
    { code: 'es', label: 'Español' },
    { code: 'en', label: 'English' },
];

export const i18n = createI18n({
    legacy: false,           // Composition API mode (recomendado)
    locale: detectLocale(),
    fallbackLocale: 'es',
    messages: { es, en },
});

/**
 * Persiste el locale elegido por el user en localStorage.
 * Útil para llamarlo desde un selector de idioma.
 */
export function setLocale(code) {
    if (!SUPPORTED_LOCALES.some((l) => l.code === code)) return;
    i18n.global.locale.value = code;
    try {
        localStorage.setItem(STORAGE_KEY, code);
    } catch { /* ignore */ }
}
