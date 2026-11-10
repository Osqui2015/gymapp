/**
 * Normalización y validación de teléfonos (espejo de App\Services\TelefonoService).
 *
 * El backend es la fuente de verdad: `TelefonoService::normalizar()` y
 * `TelefonoService::esMovilArgentinoValido()`. Esta copia existe para que
 * la UI pueda marcar "teléfono no válido" SIN round-trip al servidor, y
 * para no generar links de WhatsApp rotos.
 *
 * ⚠️ Si cambiás la regla acá, cambiala también en el backend: los tests
 * de Fase 0.9 comparan ambos.
 */

/** Códigos de área válidos de Argentina (2 y 3 dígitos). */
const AREAS_AR = [
    '11',
    '221', '223', '230', '236', '237', '238', '239', '240', '241',
    '243', '245', '246', '247', '248', '249', '250', '251', '252',
    '253', '254', '255', '256', '257', '258', '260', '261', '262',
    '263', '264', '265', '266', '267', '268', '269', '280', '281',
    '283', '284', '285', '287', '288', '289', '290', '291', '292',
    '293', '294', '295', '296', '297', '298', '299',
    '220', '222', '225', '226', '227', '228', '229', '232', '233',
    '234', '235',
    '331', '332', '333', '334', '336', '337', '338',
    '340', '341', '342', '343', '345', '346', '348', '349',
    '351', '352', '353', '354', '357', '358', '360', '362', '364',
    '370', '376', '379', '380', '381', '383', '385', '386', '387',
    '388',
];

const CODIGO_PAIS_DEFAULT = '54';
const PREFIJO_MOVIL = '9';
const LOCAL_MIN = 8;
const LOCAL_MAX = 12;

/**
 * Normaliza a E.164. Devuelve null si no es normalizable.
 *
 * Para números argentinos el chequeo es ESTRICTO (área real + móvil), no
 * solo "tiene forma de E.164": un local de 9 dígitos como `934482229`
 * tiene forma válida pero su área es indeterminada. Adivinarla haría
 * que un `wa.me` le escriba a otra persona.
 */
export function normalizarTelefono(telefono) {
    if (!telefono) return null;

    const trimmed = String(telefono).trim();
    if (trimmed === '') return null;

    const tieneMas = trimmed.startsWith('+');
    const digitos = trimmed.replace(/\D+/g, '');

    if (digitos === '') return null;

    // Código de país explícito: se respeta tal cual.
    if (tieneMas) {
        return esE164Valido('+' + digitos) ? '+' + digitos : null;
    }

    let local = digitos;

    if (local.startsWith(CODIGO_PAIS_DEFAULT) && local.length > LOCAL_MIN) {
        local = local.slice(CODIGO_PAIS_DEFAULT.length);
    }

    if (local !== '' && !local.startsWith(PREFIJO_MOVIL)) {
        local = PREFIJO_MOVIL + local;
    }

    if (local.length < LOCAL_MIN || local.length > LOCAL_MAX) {
        return null;
    }

    const normalizado = '+' + CODIGO_PAIS_DEFAULT + local;

    if (!esE164Valido(normalizado)) return null;
    if (!esMovilArgentinoValido(normalizado)) return null;

    return normalizado;
}

function esE164Valido(numero) {
    return /^\+[1-9]\d{6,14}$/.test(numero);
}

/**
 * ¿Es un móvil argentino válido (o un internacional con código explícito)?
 */
export function esMovilArgentinoValido(telefono) {
    // ⚠️ NO llamar a `normalizarTelefono()` acá: `normalizarTelefono()`
    // ya llama a este método para validar los números argentinos y eso
    // sería recursión infinita. Se trabaja sobre la entrada cruda.
    if (!telefono) return false;

    const trimmed = String(telefono).trim();
    if (trimmed === '') return false;

    const tieneMas = trimmed.startsWith('+');
    const digitos = trimmed.replace(/\D+/g, '');

    if (digitos === '') return false;

    // Código de país explícito distinto de Argentina: se acepta con la
    // regla E.164 genérica.
    if (tieneMas && !trimmed.startsWith('+54')) {
        return esE164Valido('+' + digitos);
    }

    let local = digitos;

    if (local.startsWith(CODIGO_PAIS_DEFAULT) && local.length > LOCAL_MIN) {
        local = local.slice(CODIGO_PAIS_DEFAULT.length);
    }

    if (local !== '' && !local.startsWith(PREFIJO_MOVIL)) {
        local = PREFIJO_MOVIL + local;
    }

    if (local.length < LOCAL_MIN || local.length > LOCAL_MAX) return false;
    if (!local.startsWith(PREFIJO_MOVIL)) return false;

    const sinMovil = local.slice(1); // área + número

    // El área puede ser de 2 o 3 dígitos. Se prueba la más larga primero
    // (Tucumán = 381, que es de 3).
    if (sinMovil.length >= 3 && AREAS_AR.includes(sinMovil.slice(0, 3))) {
        return sinMovil.length >= 9 && sinMovil.length <= 11;
    }

    if (sinMovil.length >= 2 && AREAS_AR.includes(sinMovil.slice(0, 2))) {
        return sinMovil.length >= 8 && sinMovil.length <= 10;
    }

    // Área desconocida: no se puede afirmar que sea un móvil argentino
    // válido.
    return false;
}

/**
 * ¿El teléfono es utilizable (para WhatsApp y para el backend)?
 *
 * Para números argentinos usa la validación ESTRICTA (área real + móvil),
 * no solo la forma E.164: un local de 9 dígitos ambiguo como `934-482229`
 * tiene forma de E.164 pero no se sabe a qué área pertenece, y adivinar
 * haría que el link de WhatsApp le escriba a otra persona.
 */
export function telefonoValido(telefono) {
    return esMovilArgentinoValido(telefono);
}

/**
 * Formato legible: +54 9 341 123-4567.
 *
 * El área puede ser de 2 (CABA = 11) o 3 dígitos (Tucumán = 381).
 * Sin detectarla, `+54 9 381 1234567` se mostraría como
 * `+54 9 38 112-34567`, que es un número que no existe.
 */
export function formatearTelefono(telefono) {
    const normalizado = normalizarTelefono(telefono);

    if (normalizado === null) return telefono || null;

    // '+54' son 3 caracteres (el signo cuenta): hay que saltear el
    // prefijo COMPLETO, no solo el signo.
    if (!normalizado.startsWith('+54')) return normalizado;

    const digitos = normalizado.slice(3);

    if (digitos.length < 10) return normalizado;

    const movil = digitos.slice(0, 1);
    const local = digitos.slice(1);

    const largoArea = AREAS_AR.includes(local.slice(0, 3)) ? 3 : 2;

    const area = local.slice(0, largoArea);
    let numero = local.slice(largoArea);

    if (numero === '') return normalizado;

    // Separador de miles: los últimos 4 dígitos van juntos.
    if (numero.length > 4) {
        const corte = numero.length - 4;
        numero = `${numero.slice(0, corte)}-${numero.slice(corte)}`;
    }

    return `+54 ${movil} ${area} ${numero}`;
}

/**
 * Link de WhatsApp, o null si el teléfono no sirve.
 */
export function whatsappLink(telefono, mensaje = '') {
    // Validación ESTRICTA: un número ambiguo no genera link, porque un
    // `wa.me` equivocado le escribe a otra persona.
    if (!telefonoValido(telefono)) {
        return null;
    }

    const normalizado = normalizarTelefono(telefono);

    if (normalizado === null) return null;

    let url = `https://wa.me/${normalizado}`;

    if (mensaje !== '') {
        url += '?text=' + encodeURIComponent(mensaje);
    }

    return url;
}
