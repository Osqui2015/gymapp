/**
 * Pinia store para el catálogo VisualGym.
 *
 * Mantiene:
 *  - exercises: lista paginada actual
 *  - facets: listas únicas de body_part / target / equipamiento (cacheadas)
 *  - pagination: estado de paginación Laravel
 *  - filters: filtros activos
 *  - loading: estado de carga
 *  - current: ejercicio actualmente abierto en el modal de detalle
 */

import { defineStore } from 'pinia';
import axios from 'axios';
import * as api from '../api/visualgym';

const DEFAULT_PER_PAGE = 24;

export const useVisualGymStore = defineStore('visualgym', {
    state: () => ({
        exercises: [],
        pagination: {
            current_page: 1,
            last_page: 1,
            per_page: DEFAULT_PER_PAGE,
            total: 0,
        },
        filters: {
            busqueda: '',
            body_part: '',
            target: '',
            equipamiento: '',
        },
        facets: {
            body_parts: [],
            targets: [],
            equipamientos: [],
        },
        loading: false,
        facetsLoaded: false,
        current: null,
        currentLoading: false,
        perPage: DEFAULT_PER_PAGE,
    }),

    getters: {
        /** Total de páginas (cacheado para evitar recomputaciones). */
        totalPages: (state) => state.pagination.last_page,

        /** Hay resultados. */
        hasResults: (state) => state.exercises.length > 0,

        /** Filtros activos (sin contar vacíos). */
        activeFilters: (state) => {
            return Object.entries(state.filters)
                .filter(([, v]) => v !== '' && v !== null)
                .map(([k, v]) => ({ key: k, value: v }));
        },
    },

    actions: {
        /**
         * Carga facets (solo una vez — son listas estáticas del dataset).
         */
        async loadFacets(force = false) {
            if (this.facetsLoaded && !force) return this.facets;
            try {
                this.facets = await api.fetchFacets();
                this.facetsLoaded = true;
                return this.facets;
            } catch (e) {
                console.error('[visualgym] loadFacets failed', e);
                throw e;
            }
        },

        /**
         * Carga la página actual con los filtros activos.
         * Mantiene la API simple: si le pasás page, va a esa página.
         */
        async fetchList({ page = 1 } = {}) {
            this.loading = true;
            try {
                const data = await api.fetchExercises({
                    page,
                    per_page: this.perPage,
                    ...this.filters,
                });
                this.exercises = data.data;
                this.pagination = {
                    current_page: data.current_page,
                    last_page: data.last_page,
                    per_page: data.per_page,
                    total: data.total,
                };
                return data;
            } catch (e) {
                console.error('[visualgym] fetchList failed', e);
                this.exercises = [];
                this.pagination = { ...this.pagination, total: 0 };
                throw e;
            } finally {
                this.loading = false;
            }
        },

        /**
         * Setea un filtro y recarga la lista (vuelve a página 1).
         */
        async setFilter(key, value) {
            this.filters[key] = value ?? '';
            await this.fetchList({ page: 1 });
        },

        /**
         * Limpia todos los filtros y recarga.
         */
        async clearFilters() {
            this.filters = { busqueda: '', body_part: '', target: '', equipamiento: '' };
            await this.fetchList({ page: 1 });
        },

        /**
         * Cambia de página (mantiene filtros).
         */
        async goToPage(page) {
            if (page < 1 || page > this.totalPages) return;
            await this.fetchList({ page });
        },

        /**
         * Carga el detalle de un ejercicio por external_id.
         */
        async fetchOne(externalId, lang = 'es') {
            this.currentLoading = true;
            try {
                this.current = await api.fetchExercise(externalId, lang);
                return this.current;
            } catch (e) {
                console.error('[visualgym] fetchOne failed', e);
                this.current = null;
                throw e;
            } finally {
                this.currentLoading = false;
            }
        },

        /**
         * Cierra el modal de detalle.
         */
        clearCurrent() {
            this.current = null;
        },

        /**
         * Toggle favorito. Reusa el endpoint existente de EjercicioController.
         * Actualiza localmente el flag para feedback instantáneo.
         *
         * @param {Object} ejercicio { id, external_id, ... }
         */
        async toggleFavorite(ejercicio) {
            if (! ejercicio?.id) return;
            try {
                const { data } = await axios.post(`/api/ejercicios/${ejercicio.id}/favorite`);
                // Actualizar en la lista
                const inList = this.exercises.find((e) => e.id === ejercicio.id);
                if (inList) {
                    inList.is_favorite = data.is_favorite;
                }
                // Actualizar en el detalle abierto
                if (this.current?.id === ejercicio.id) {
                    this.current.is_favorite = data.is_favorite;
                }
                return data.is_favorite;
            } catch (e) {
                console.error('[visualgym] toggleFavorite failed', e);
                throw e;
            }
        },
    },
});
