import { ref, computed } from 'vue';
import axios from 'axios';

/**
 * Estado compartido del usuario actual.
 * Se carga una sola vez por sesión y se cachea en `window.__gymAppUser`
 * para evitar requests repetidos en cada componente.
 */

let _user = null;
let _role = null;
let _pendingPromise = null;

const user = ref(_user);
const role = ref(_role);
const isLoading = ref(false);

const isAlumno = computed(() => role.value === 'alumno');
const isTrainer = computed(() => role.value === 'trainer');
const isAdmin = computed(() => role.value === 'administrador');
const isStaff = computed(() => isTrainer.value || isAdmin.value);
const isAuthenticated = computed(() => !!user.value);

/**
 * Carga la info del usuario actual. Cachea el resultado para no hacer
 * requests duplicados.
 */
async function fetchUser(force = false) {
    if (!force && _user) {
        user.value = _user;
        role.value = _role;
        return _user;
    }

    if (_pendingPromise) return _pendingPromise;

    isLoading.value = true;
    _pendingPromise = axios.get('/api/user-info')
        .then((response) => {
            _user = response.data;
            _role = response.data.role;
            user.value = _user;
            role.value = _role;
            return _user;
        })
        .catch((error) => {
            console.error('[useAuth] No se pudo obtener el usuario:', error);
            _user = null;
            _role = null;
            user.value = null;
            role.value = null;
            throw error;
        })
        .finally(() => {
            isLoading.value = false;
            _pendingPromise = null;
        });

    return _pendingPromise;
}

/**
 * Hook para usar en componentes. Devuelve refs reactivos y helpers.
 */
export function useAuth() {
    return {
        // Estado reactivo
        user,
        role,
        isLoading,

        // Helpers booleanos
        isAlumno,
        isTrainer,
        isAdmin,
        isStaff,
        isAuthenticated,

        // Acciones
        fetchUser,
        refresh: () => fetchUser(true),
        logout: async () => {
            await axios.post('/logout');
            _user = null;
            _role = null;
            user.value = null;
            role.value = null;
            window.location.href = '/login';
        },
    };
}
