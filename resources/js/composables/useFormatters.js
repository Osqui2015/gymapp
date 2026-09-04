/**
 * Helpers de formateo centralizados.
 *
 * Regla del proyecto: las fechas se muestran SIEMPRE en formato
 * día/mes/año (dd/mm/yyyy) o variantes más largas (con mes en texto
 * o con hora). NUNCA en formato ISO (yyyy-mm-ddThh:mm:ss.sssZ).
 *
 * Acepta varios inputs:
 *   - String en formato 'yyyy-mm-dd' (lo más común desde el backend)
 *   - String ISO con timestamp ('2026-09-03T00:00:00.000000Z')
 *   - Objeto Date
 *   - null/undefined → devuelve string vacío
 */
const MESES_CORTOS = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
const MESES_LARGOS = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
const DIAS_SEMANA = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];

/**
 * Normaliza cualquier input de fecha a un Date o null.
 * NO formatea: solo normaliza.
 */
function toDate(input) {
    if (input == null) return null;
    if (input instanceof Date) return isNaN(input.getTime()) ? null : input;
    if (typeof input !== 'string') return null;

    const s = input.trim();
    if (!s) return null;

    // Si viene como 'yyyy-mm-dd' sin hora, parseamos como local para evitar
    // que el timezone reste un día. Ej: '2026-09-03' → Date local sept-3.
    if (/^\d{4}-\d{2}-\d{2}$/.test(s)) {
        const [y, m, d] = s.split('-').map(Number);
        return new Date(y, m - 1, d);
    }

    // ISO con timestamp o cualquier string parseable
    const d = new Date(s);
    return isNaN(d.getTime()) ? null : d;
}

/**
 * Pad con 0 a la izquierda hasta 2 dígitos.
 */
function pad2(n) {
    return String(n).padStart(2, '0');
}

/**
 * Formato corto: dd/mm/yyyy (default del proyecto).
 * Ej: '2026-09-03' → '03/09/2026'
 */
export function formatDate(input) {
    const d = toDate(input);
    if (!d) return '';
    return `${pad2(d.getDate())}/${pad2(d.getMonth() + 1)}/${d.getFullYear()}`;
}

/**
 * Formato dd MMM (mes corto). Ej: '03 sep'
 * Útil para ejes de chart donde el espacio es limitado.
 */
export function formatDateShort(input) {
    const d = toDate(input);
    if (!d) return '';
    return `${pad2(d.getDate())} ${MESES_CORTOS[d.getMonth()]}`;
}

/**
 * Formato dd MMM yy. Ej: '03 sep 26'
 */
export function formatDateMedium(input) {
    const d = toDate(input);
    if (!d) return '';
    return `${pad2(d.getDate())} ${MESES_CORTOS[d.getMonth()]} ${String(d.getFullYear()).slice(-2)}`;
}

/**
 * Formato largo: "3 de septiembre de 2026"
 * Para cards de detalle, headers, etc.
 */
export function formatDateLong(input) {
    const d = toDate(input);
    if (!d) return '';
    return `${d.getDate()} de ${MESES_LARGOS[d.getMonth()]} de ${d.getFullYear()}`;
}

/**
 * Formato con día de semana: "lunes, 3 de septiembre de 2026"
 */
export function formatDateWeekday(input) {
    const d = toDate(input);
    if (!d) return '';
    return `${DIAS_SEMANA[d.getDay()]}, ${formatDateLong(d)}`;
}

/**
 * Formato con hora: dd/mm/yyyy HH:MM
 */
export function formatDateTime(input) {
    const d = toDate(input);
    if (!d) return '';
    return `${formatDate(d)} ${pad2(d.getHours())}:${pad2(d.getMinutes())}`;
}

/**
 * Solo la hora: HH:MM
 */
export function formatTime(input) {
    const d = toDate(input);
    if (!d) return '';
    return `${pad2(d.getHours())}:${pad2(d.getMinutes())}`;
}

/**
 * Solo el mes corto: 'sep', 'oct', etc.
 */
export function formatMonthShort(input) {
    const d = toDate(input);
    if (!d) return '';
    return MESES_CORTOS[d.getMonth()];
}

/**
 * Solo el mes largo: 'septiembre', 'octubre', etc.
 */
export function formatMonthLong(input) {
    const d = toDate(input);
    if (!d) return '';
    return MESES_LARGOS[d.getMonth()];
}

/**
 * Composable Vue: expone los helpers como un objeto para usar en setup.
 *   const { formatDate, formatDateLong } = useFormatters();
 */
export function useFormatters() {
    return {
        formatDate,
        formatDateShort,
        formatDateMedium,
        formatDateLong,
        formatDateWeekday,
        formatDateTime,
        formatTime,
        formatMonthShort,
        formatMonthLong,
    };
}
