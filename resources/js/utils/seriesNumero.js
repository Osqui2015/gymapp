/**
 * Numeración de series: espacio separado para calentamiento y efectivas.
 *
 * CONTEXTO (bug P0 corregido)
 * ---------------------------
 * El índice UNIQUE de `historials` es:
 *   (user_id, rutina_nombre, dia, ejercicio_nombre, series_numero, fecha)
 * y NO incluye `tipo_serie`. Como el warmup y la serie efectiva N PARTÍAN
 * ambos de 1, la colisión era inevitable: el warmup 1 y la serie efectiva 1
 * caían en la MISMA clave, y `Historial::guardarIdempotente()` hacía
 * `fill()->save()` sobre la fila existente — pisando el calentamiento con la
 * serie efectiva. Se perdía el registro del warmup y el volumen subcuenta.
 *
 * AGENTS.md declara ese índice como invariante que no hay que romper, así
 * que la solución NO es agregar `tipo_serie` al índice (eso requiere
 * migración y repara las filas ya colisionadas), sino separar los rangos
 * DENTRO de la misma columna `series_numero`.
 *
 * RANGOS
 *   - Series efectivas:  1 .. 50  (como siempre)
 *   - Calentamientos:   51 .. 99  (offset de 50)
 *
 * El rango 51..99 cabe en la validación del backend (`max:99`), que antes
 * era `max:50` y rechazaba los calentamientos con offset.
 *
 * OJO: los calentamientos NO son "series" en el sentido del volumen/1RM, así
 * que ningún KPI debe sumar las que caen en el rango de calentamiento. El
 * backend ya filtra por `tipo_serie` en los agregados (ver
 * HistorialController::pesoMaxMin y StatsService), pero el RANGO es la
 * garantía de que nunca colisionan entre sí.
 */

/** Primer número reservado para calentamiento. */
export const OFFSET_CALENTAMIENTO = 50;

/** Último número reservado para calentamiento (inclusive). */
export const MAX_CALENTAMIENTO = OFFSET_CALENTAMIENTO + 49; // 99

/** Máximo de series efectivas por ejercicio. */
export const MAX_EFECTIVA = OFFSET_CALENTAMIENTO; // 50

/**
 * ¿Este número de serie corresponde a un calentamiento?
 *
 * @param {number|null|undefined} numero
 * @returns {boolean}
 */
export function esCalentamiento(numero) {
    const n = Number(numero);
    return Number.isFinite(n) && n > OFFSET_CALENTAMIENTO;
}

/**
 * Convierte el contador interno de calentamientos (1, 2, 3...) al número
 * persistido en `series_numero` (51, 52, 53...).
 *
 * @param {number} numeroCalentamiento Contador interno (base 1).
 * @returns {number} Número persistido.
 */
export function numeroCalentamientoPersisted(numeroCalentamiento) {
    const n = Number(numeroCalentamiento);
    if (!Number.isFinite(n) || n <= 0) {
        return OFFSET_CALENTAMIENTO + 1;
    }

    return Math.min(OFFSET_CALENTAMIENTO + n, MAX_CALENTAMIENTO);
}

/**
 * Convierte un número persistido al número de display para el usuario.
 *
 * Un warmup persistido como 51 se muestra como "Calentamiento 1".
 * Una efectiva persistida como 3 se muestra como "#3".
 *
 * @param {number} numeroPersistido
 * @returns {number} Número a mostrar.
 */
export function numeroSerieParaDisplay(numeroPersistido) {
    const n = Number(numeroPersistido);
    if (!Number.isFinite(n)) {
        return 0;
    }

    if (n > OFFSET_CALENTAMIENTO) {
        return n - OFFSET_CALENTAMIENTO;
    }

    return n;
}
