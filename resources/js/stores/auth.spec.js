import { describe, it, expect, vi, beforeEach } from 'vitest';
import { useAuthStore } from './auth';
import axios from 'axios';

// Mock del cliente axios para no hacer requests reales en tests.
vi.mock('axios', () => {
    const mockAxios = {
        get: vi.fn(),
        post: vi.fn(),
        defaults: { headers: { common: {} } },
    };
    return {
        default: mockAxios,
        ...mockAxios,
    };
});

describe('useAuthStore', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('inicia con state vacío (sin usuario cargado)', () => {
        const auth = useAuthStore();
        expect(auth.id).toBeNull();
        expect(auth.name).toBeNull();
        expect(auth.role).toBeNull();
        expect(auth.loaded).toBe(false);
        expect(auth.isAuthenticated).toBe(false);
    });

    it('los getters reflejan correctamente el rol', () => {
        const auth = useAuthStore();
        expect(auth.isAdmin).toBe(false);
        expect(auth.isTrainer).toBe(false);
        expect(auth.isAlumno).toBe(false);
        expect(auth.isComun).toBe(false);
        expect(auth.isStaff).toBe(false);

        auth.role = 'trainer';
        expect(auth.isTrainer).toBe(true);
        expect(auth.isStaff).toBe(true);

        auth.role = 'administrador';
        expect(auth.isAdmin).toBe(true);
        expect(auth.isStaff).toBe(true);

        auth.role = 'alumno';
        expect(auth.isAlumno).toBe(true);
        expect(auth.isStaff).toBe(false);

        auth.role = 'comun';
        expect(auth.isComun).toBe(true);
    });

    it('fetchUser cachea el resultado (no vuelve a pegarle al endpoint)', async () => {
        axios.get.mockResolvedValueOnce({
            data: { id: 1, name: 'Test', nick: 'test', email: 't@e.com', role: 'trainer', trainer_id: null, has_trainer: false },
        });

        const auth = useAuthStore();
        await auth.fetchUser();
        expect(auth.id).toBe(1);
        expect(auth.role).toBe('trainer');
        expect(auth.loaded).toBe(true);
        expect(axios.get).toHaveBeenCalledTimes(1);

        // Segunda llamada no debe pegar al endpoint (cache)
        await auth.fetchUser();
        expect(axios.get).toHaveBeenCalledTimes(1);
    });

    it('fetchUser({ force: true }) sí re-pega al endpoint', async () => {
        axios.get.mockResolvedValue({
            data: { id: 1, name: 'Test', nick: 'test', email: 't@e.com', role: 'trainer', trainer_id: null, has_trainer: false },
        });

        const auth = useAuthStore();
        await auth.fetchUser();
        await auth.fetchUser(true);
        expect(axios.get).toHaveBeenCalledTimes(2);
    });

    it('fetchUser marca loaded=true incluso cuando falla (no spamea el endpoint)', async () => {
        axios.get.mockRejectedValue(new Error('Network error'));

        const auth = useAuthStore();
        await expect(auth.fetchUser()).rejects.toThrow();
        expect(auth.loaded).toBe(true);
        expect(auth.id).toBeNull();
    });

    it('logout limpia el state y llama a /logout', async () => {
        axios.get.mockResolvedValueOnce({
            data: { id: 1, name: 'Test', role: 'trainer' },
        });
        axios.post.mockResolvedValueOnce({ data: {} });

        // Mock window.location.href setter
        const originalLocation = window.location;
        delete window.location;
        window.location = { ...originalLocation, href: '' };

        const auth = useAuthStore();
        await auth.fetchUser();
        expect(auth.id).toBe(1);

        await auth.logout();
        expect(axios.post).toHaveBeenCalledWith('/logout');
        expect(auth.id).toBeNull();
        expect(auth.role).toBeNull();
        expect(window.location.href).toBe('/login');

        window.location = originalLocation;
    });
});
