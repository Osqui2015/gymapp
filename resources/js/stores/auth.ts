import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import axios from 'axios';

export type UserRole = 'administrador' | 'trainer' | 'comun' | 'alumno' | 'coordinador' | 'recepcionista' | null;

export interface AuthState {
    id: number | null;
    name: string | null;
    nick: string | null;
    email: string | null;
    role: UserRole;
    trainerId: number | null;
    hasTrainer: boolean;
    loaded: boolean;
    isLoading: boolean;
}

interface UserInfoResponse {
    id?: number;
    name?: string;
    nick?: string;
    email?: string;
    role?: UserRole;
    trainer_id?: number | null;
    has_trainer?: boolean;
}

const STAFF_ROLES: ReadonlyArray<UserRole> = ['administrador', 'trainer', 'recepcionista', 'coordinador'];

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
export const useAuthStore = defineStore('auth', () => {
    const id = ref<number | null>(null);
    const name = ref<string | null>(null);
    const nick = ref<string | null>(null);
    const email = ref<string | null>(null);
    const role = ref<UserRole>(null);
    const trainerId = ref<number | null>(null);
    const hasTrainer = ref<boolean>(false);
    const loaded = ref<boolean>(false);
    const isLoading = ref<boolean>(false);

    const isAuthenticated = computed<boolean>(() => id.value !== null);
    const isAdmin = computed<boolean>(() => role.value === 'administrador');
    const isTrainer = computed<boolean>(() => role.value === 'trainer');
    const isComun = computed<boolean>(() => role.value === 'comun');
    const isAlumno = computed<boolean>(() => role.value === 'alumno');
    const isStaff = computed<boolean>(() => role.value !== null && STAFF_ROLES.includes(role.value));
    const initials = computed<string>(() => (name.value ? name.value.charAt(0).toUpperCase() : '?'));

    /**
     * Carga la info del usuario actual desde /api/user-info.
     * Cachea el resultado en `loaded` para no hacer requests duplicados.
     * Pasá `force: true` para forzar un refetch.
     */
    async function fetchUser(force = false): Promise<void> {
        if (loaded.value && !force) return;
        isLoading.value = true;
        try {
            const { data } = await axios.get<UserInfoResponse>('/api/user-info');
            id.value = data.id ?? null;
            name.value = data.name ?? null;
            nick.value = data.nick ?? null;
            email.value = data.email ?? null;
            role.value = data.role ?? null;
            trainerId.value = data.trainer_id ?? null;
            hasTrainer.value = data.has_trainer ?? false;
            loaded.value = true;
        } catch (e) {
            console.error('[authStore] No se pudo obtener el usuario:', e);
            // Marcamos como cargado igual para no spamear el endpoint.
            loaded.value = true;
            throw e;
        } finally {
            isLoading.value = false;
        }
    }

    /**
     * Alias semántico para forzar refetch (compat con la API del composable viejo).
     */
    async function refresh(): Promise<void> {
        return fetchUser(true);
    }

    /**
     * Logout: pega /logout, limpia state, redirige a /login.
     */
    async function logout(): Promise<void> {
        try {
            await axios.post('/logout');
        } catch {
            /* ignore: igual vamos a limpiar y redirigir */
        }
        $reset();
        window.location.href = '/login';
    }

    function $reset(): void {
        id.value = null;
        name.value = null;
        nick.value = null;
        email.value = null;
        role.value = null;
        trainerId.value = null;
        hasTrainer.value = false;
        loaded.value = false;
        isLoading.value = false;
    }

    return {
        id,
        name,
        nick,
        email,
        role,
        trainerId,
        hasTrainer,
        loaded,
        isLoading,
        isAuthenticated,
        isAdmin,
        isTrainer,
        isComun,
        isAlumno,
        isStaff,
        initials,
        fetchUser,
        refresh,
        logout,
        $reset,
    };
});
