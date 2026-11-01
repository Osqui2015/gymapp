/**
 * useHistorialFiltros — filtrado y orden del historial.
 *
 * Lógica pura (sin Vue) para poder testearla: antes vivía dentro de
 * HistorialContent.vue (SFC de 2.400 líneas) y tenía tres bugs silenciosos:
 *   - leía 'h.rutina' cuando la API devuelve 'rutina_nombre';
 *   - los periodos 'year' y 'custom' no filtraban (parseInt → NaN);
 *   - los órdenes 'volumen_desc' y 'ejercicio_asc' no existían en el sort.
 */

/**
 * @param {Array} historial  filas del payload de /api/historial
 * @param {Object} filtros   { search, rutina, dia, periodo, fechaDesde, fechaHasta, soloCompletados, orden }
 * @returns {Array} filas filtradas y ordenadas
 */
export function filtrarHistorial(historial, filtros = {}) {
    let list = Array.isArray(historial) ? [...historial] : [];

    if (filtros.search && filtros.search.trim()) {
        const q = filtros.search.toLowerCase().trim();
        list = list.filter((h) => (h.ejercicio_nombre || '').toLowerCase().includes(q));
    }

    // El payload usa 'rutina_nombre'.
    if (filtros.rutina) {
        list = list.filter((h) => h.rutina_nombre === filtros.rutina);
    }

    if (filtros.dia) {
        list = list.filter((h) => h.dia === filtros.dia);
    }

    if (filtros.soloCompletados) {
        list = list.filter((h) => !!h.completado);
    }

    list = aplicarPeriodo(list, filtros);

    return ordenarHistorial(list, filtros.orden);
}

/**
 * Periodo: 'all' | '7d' | '30d' | '90d' | '365d' | 'year' | 'custom'
 */
export function aplicarPeriodo(list, filtros = {}) {
    const periodo = filtros.periodo || 'all';

    if (periodo === 'custom') {
        const desde = filtros.fechaDesde;
        const hasta = filtros.fechaHasta;
        let out = list;
        if (desde) out = out.filter((h) => (h.fecha || '') >= desde);
        // 'fecha' viene como ISO completo: la fecha sola va como 'YYYY-MM-DD'.
        if (hasta) out = out.filter((h) => (h.fecha || '') <= `${hasta}T23:59:59.999Z`);
        return out;
    }

    if (periodo === 'year') {
        const desde = new Date();
        desde.setMonth(0, 1);
        desde.setHours(0, 0, 0, 0);
        const cutoff = desde.toISOString().slice(0, 10);
        return list.filter((h) => (h.fecha || '') >= cutoff);
    }

    if (periodo === 'all') return list;

    const days = parseInt(periodo, 10);
    if (isNaN(days) || days <= 0) return list;

    const cutoffDate = new Date();
    cutoffDate.setDate(cutoffDate.getDate() - days);
    const cutoff = cutoffDate.toISOString().slice(0, 10);
    return list.filter((h) => (h.fecha || '') >= cutoff);
}

/**
 * Orden: 'fecha_desc' | 'fecha_asc' | 'peso_desc' | 'reps_desc' |
 *        'volumen_desc' | 'ejercicio_asc'
 */
export function ordenarHistorial(list, orden = 'fecha_desc') {
    const out = [...list];

    const volumen = (h) => (Number(h.peso) || 0) * (Number(h.reps_realizadas) || 0);

    out.sort((a, b) => {
        switch (orden) {
            case 'fecha_asc':
                return (a.fecha || '').localeCompare(b.fecha || '');
            case 'peso_desc':
                return (Number(b.peso) || 0) - (Number(a.peso) || 0);
            case 'reps_desc':
                return (Number(b.reps_realizadas) || 0) - (Number(a.reps_realizadas) || 0);
            case 'volumen_desc':
                return volumen(b) - volumen(a);
            case 'ejercicio_asc':
                return (a.ejercicio_nombre || '').localeCompare(b.ejercicio_nombre || '');
            default:
                return (b.fecha || '').localeCompare(a.fecha || '');
        }
    });

    return out;
}

/**
 * Rutinas disponibles para el dropdown de filtros.
 * (Ojo: el payload usa 'rutina_nombre', no 'rutina'.)
 */
export function rutinasDisponibles(historial) {
    const set = new Set();
    (historial || []).forEach((h) => {
        if (h.rutina_nombre) set.add(h.rutina_nombre);
    });
    return Array.from(set).sort();
}

/**
 * Días disponibles para el dropdown de filtros.
 */
export function diasDisponibles(historial) {
    const set = new Set();
    (historial || []).forEach((h) => {
        if (h.dia) set.add(h.dia);
    });
    return Array.from(set).sort();
}

/**
 * Cuenta las series por día (YYYY-MM-DD) para el heatmap del calendario.
 *
 * La API devuelve la fecha como ISO completo ("2026-10-07T00:00:00.000000Z")
 * y el calendario consulta por 'YYYY-MM-DD': hay que normalizar, si no
 * ningún día matchea y el mapa queda en cero.
 *
 * @returns {Object<string, number>} { '2026-10-07': 3, ... }
 */
export function contarSeriesPorFecha(historial) {
    const map = {};
    (historial || []).forEach((reg) => {
        const raw = reg.fecha || reg.created_at;
        if (!raw) return;
        const fecha = String(raw).slice(0, 10);
        map[fecha] = (map[fecha] || 0) + 1;
    });
    return map;
}
