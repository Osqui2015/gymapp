/**
 * Helpers de fecha en la zona horaria del gym.
 *
 * CONTEXTO (bug P0 corregido)
 * ---------------------------
 * El frontend usaba `new Date().toISOString().split('T')[0]` para obtener
 * "hoy". `toISOString()` devuelve la fecha en **UTC**, y el gym opera en
 * `America/Argentina/Tucuman` (UTC-3). Entre las 21:00 y las 00:00 hora
 * local, `toISOString()` devuelve el día SIGUIENTE.
 *
 * Impacto doble:
 *   (a) la serie se guardaba con fecha de mañana -> rompía la idempotencia
 *       y las rachas (un set del lunes aparecía como martes);
 *   (b) los filtros "últimos 30 días" perdían el día de hoy.
 *
 * Estos helpers devuelven siempre la fecha en la zona del gym.
 *
 * Espejo backend: la app corre con `config('app.timezone')` =
 * 'America/Argentina/Tucuman', así que `now()` del servidor ya es local.
 */

const TIMEZONE_GYM = 'America/Argentina/Tucuman';

/**
 * Fecha de hoy en el gym, formato 'YYYY-MM-DD'.
 *
 * @param {Date} [fecha] Fecha a convertir (default: ahora).
 * @returns {string} 'YYYY-MM-DD'
 */
export function hoyLocal(fecha = new Date()) {
    // 'en-CA' devuelve el formato ISO (YYYY-MM-DD) y respeta el timezone.
    return new Intl.DateTimeFormat('en-CA', {
        timeZone: TIMEZONE_GYM,
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
    }).format(fecha);
}

/**
 * Fecha de hoy en el gym, formato 'YYYY-MM-DD', a partir de una fecha en
 * formato ISO completo (como el que devuelve la API: "2026-10-07T00:00:00").
 *
 * Ojo: la API devuelve la fecha del server YA en hora local (el server corre
 * con la tz del gym), así que acá solo se recorta el sufijo de hora.
 *
 * @param {string} iso
 * @returns {string} 'YYYY-MM-DD'
 */
export function fechaIsoLocal(iso) {
    if (!iso) return '';
    return String(iso).slice(0, 10);
}

/**
 * Fecha desplazada N días desde hoy, en el gym, formato 'YYYY-MM-DD'.
 *
 * @param {number} dias Días a restar (positivo = hacia atrás).
 * @returns {string} 'YYYY-MM-DD'
 */
export function fechaLocalHaceDias(dias) {
    const d = new Date();
    d.setDate(d.getDate() - Number(dias || 0));

    return hoyLocal(d);
}

/**
 * Primer día del año en curso, en el gym, formato 'YYYY-MM-DD'.
 *
 * @returns {string}
 */
export function inicioDeAnioLocal() {
    const d = new Date();

    return hoyLocal(new Date(d.getFullYear(), 0, 1));
}
