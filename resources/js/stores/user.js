import { defineStore } from 'pinia';

export const useUserStore = defineStore('user', {
    state: () => ({
        id: null,
        name: null,
        nick: null,
        email: null,
        role: null,
        trainerId: null,
        hasTrainer: false,
        loaded: false,
    }),

    getters: {
        isAdmin: (state) => state.role === 'administrador',
        isTrainer: (state) => state.role === 'trainer',
        isComun: (state) => state.role === 'comun',
        isAlumno: (state) => state.role === 'alumno',
        isStaff: (state) => ['administrador', 'trainer', 'recepcionista', 'coordinador'].includes(state.role),
        initials: (state) => state.name ? state.name.charAt(0).toUpperCase() : '?',
    },

    actions: {
        async load() {
            if (this.loaded) return;
            try {
                const { data } = await window.axios.get('/api/user-info');
                this.id = data.id || null;
                this.name = data.name || null;
                this.nick = data.nick || null;
                this.email = data.email || null;
                this.role = data.role;
                this.trainerId = data.trainer_id;
                this.hasTrainer = data.has_trainer;
                this.loaded = true;
            } catch (e) {
                console.error('[userStore] No se pudo cargar info del usuario', e);
                this.loaded = true; // marcar como cargado igual para no spamear
            }
        },

        setUser(userData) {
            Object.assign(this, userData);
            this.loaded = true;
        },

        clear() {
            this.$reset();
        },
    },
});
