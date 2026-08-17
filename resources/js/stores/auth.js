import { defineStore } from 'pinia';
import axios from 'axios';

/**
 * Store de autenticación / sesión actual.
 *
 * Antes había un composable `composables/useAuth.js` con state global mutable
 * (refs fuera de Pinia). Migrado a Pinia para tener:
 *   - Una sola fuente de verdad (mismo store en toda la app)
 *   - Devtools visibles (state, mutations, actions)
 *   - State serializable y SSR-safe
 *   - Mejor integración con el resto de stores (p. ej. useRutinaStore puede
 *     leer el user_id del store de auth)
 *
 * La API pública (refs computadas, acciones) se mantiene 100% compatible
 * con el composable viejo: los componentes pueden seguir destructurando
 * `{ user, role, isStaff, fetchUser, ... }` igual que antes.
 */
export const useAuthStore = defineStore('auth', {
    state: () => ({
        id: null,
        name: null,
        nick: null,
        email: null,
        role: null,
        trainerId: null,
        hasTrainer: false,
        loaded: false,
        isLoading: false,
    }),

    getters: {
        isAuthenticated: (state) => !!state.id,
        isAdmin: (state) => state.role === 'administrador',
        isTrainer: (state) => state.role === 'trainer',
        isComun: (state) => state.role === 'comun',
        isAlumno: (state) => state.role === 'alumno',
        isStaff: (state) =>
            ['administrador', 'trainer', 'recepcionista', 'coordinador'].includes(state.role),
        initials: (state) => (state.name ? state.name.charAt(0).toUpperCase() : '?'),
    },

    actions: {
        /**
         * Carga la info del usuario actual desde /api/user-info.
         * Cachea el resultado en `loaded` para no hacer requests duplicados.
         * Pasá `force: true` para forzar un refetch.
         */
        async fetchUser(force = false) {
            if (this.loaded && !force) return;
            this.isLoading = true;
            try {
                const { data } = await axios.get('/api/user-info');
                this.id = data.id || null;
                this.name = data.name || null;
                this.nick = data.nick || null;
                this.email = data.email || null;
                this.role = data.role;
                this.trainerId = data.trainer_id;
                this.hasTrainer = data.has_trainer;
                this.loaded = true;
            } catch (e) {
                console.error('[authStore] No se pudo obtener el usuario:', e);
                // Marcamos como cargado igual para no spamear el endpoint.
                this.loaded = true;
                throw e;
            } finally {
                this.isLoading = false;
            }
        },

        /**
         * Alias semántico para forzar refetch (compat con la API del composable viejo).
         */
        async refresh() {
            return this.fetchUser(true);
        },

        /**
         * Logout: pega /logout, limpia state, redirige a /login.
         */
        async logout() {
            try {
                await axios.post('/logout');
            } catch {
                /* ignore: igual vamos a limpiar y redirigir */
            }
            this.$reset();
            window.location.href = '/login';
        },
    },
});
