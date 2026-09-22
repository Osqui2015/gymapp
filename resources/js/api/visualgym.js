/**
 * Cliente HTTP para el catálogo VisualGym.
 *
 * Endpoints:
 *   GET /api/visualgym/facets
 *   GET /api/visualgym/exercises?page=&per_page=&busqueda=&body_part=&target=&equipamiento=
 *   GET /api/visualgym/exercises/{externalId}?lang=es
 */

import axios from 'axios';

const BASE = '/api/visualgym';

/**
 * Devuelve listas únicas para popular dropdowns de filtros:
 *   { body_parts: [...], targets: [...], equipamientos: [...] }
 */
export async function fetchFacets() {
    const { data } = await axios.get(`${BASE}/facets`);
    return data;
}

/**
 * Lista paginada de ejercicios.
 *
 * @param {Object} params
 * @param {number} [params.page=1]
 * @param {number} [params.per_page=24]
 * @param {string} [params.busqueda]
 * @param {string} [params.body_part]
 * @param {string} [params.target]
 * @param {string} [params.equipamiento]
 *
 * Devuelve el payload paginado de Laravel:
 *   { data: [...], current_page, last_page, total, per_page, ... }
 */
export async function fetchExercises(params = {}) {
    const clean = Object.fromEntries(
        Object.entries(params).filter(([, v]) => v !== '' && v !== null && v !== undefined),
    );
    const { data } = await axios.get(`${BASE}/exercises`, { params: clean });
    return data;
}

/**
 * Detalle de un ejercicio por external_id (ej. "0025").
 *
 * @param {string} externalId
 * @param {string} [lang='es']
 *
 * Devuelve el ejercicio con:
 *   - campos del modelo
 *   - image_url (asset URL completo)
 *   - gif_url_full
 *   - instruction (string aplanada al idioma pedido)
 *   - instruction_steps (array de pasos)
 *   - available_languages (lista de idiomas disponibles)
 *   - lang (idioma efectivamente devuelto)
 */
export async function fetchExercise(externalId, lang = 'es') {
    const { data } = await axios.get(`${BASE}/exercises/${externalId}`, {
        params: { lang },
    });
    return data;
}
