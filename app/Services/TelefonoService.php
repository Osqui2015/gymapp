<?php

namespace App\Services;

use Illuminate\Support\Str;

/**
 * Normalización y validación de teléfonos.
 *
 * Contexto: la tabla `users.telefono` estaba poblada con basura de seed
 * (8 números con el patrón ficticio `11-XXXX-XXXX`, 3 de España con `+34`,
 * 3 de Tucumán sin prefijo `+54` y 1 duplicado exacto). La feature de
 * WhatsApp (Fase 1) genera links `wa.me/<telefono>`: si el número no está
 * en E.164 el link abre una página de error en vez de WhatsApp.
 *
 * Este servicio deja un único lugar donde se decide qué es un teléfono
 * válido, y lo deja en formato E.164 (`+5493412345678`) para que el link
 * de WhatsApp funcione y para que el índice UNIQUE pueda comparar
 * strings idénticos.
 *
 * FORMATO ARGENTINA
 * ------------------
 * Un móvil argentino en E.164 es `+54 9 <area> <numero>`:
 *   - `+54`      código de país
 *   - `9`        prefijo de móvil (no aparece en el dialing nacional)
 *   - `area`     2 a 4 dígitos (Tucumán = 381, CABA = 11, etc.)
 *   - `numero`   6 a 8 dígitos
 *
 * O sea: entre 10 y 13 dígitos después del `+54`, con el `9` de móvil
 * siempre presente.
 *
 * La normalización es INTENCIONALMENTE CONSERVADORA con los números
 * ambiguos: un número local de 9 dígitos sin código de área NO se puede
 * convertir a un móvil válido sin inventar el área. En ese caso se marca
 * como NO normalizable, para que la UI muestre "teléfono no válido" en
 * vez de fabricar un link de WhatsApp que abre otra persona.
 */
class TelefonoService
{
    /**
     * Prefijo por defecto: Argentina (+54).
     */
    public const DEFAULT_COUNTRY_CODE = '54';

    /**
     * Indicativo de móvil en Argentina. Los móviles llevan un 9 adelante
     * en formato internacional: +54 9 11 1234-5678.
     */
    private const ARG_MOVIL_PREFIJO = '9';

    /**
     * Largo válido del número LOCAL argentino (sin el +54 ni el 9 de móvil):
     * área (2-4) + número (6-8) = entre 8 y 12 dígitos.
     */
    private const LOCAL_MIN = 8;

    private const LOCAL_MAX = 12;

    /**
     * Normaliza un teléfono a formato E.164.
     *
     * - Quita todo lo que no sea dígito (guiones, espacios, paréntesis, puntos).
     * - Si empieza con '+', se respeta el código de país tal cual.
     * - Si no tiene código de país, se asume Argentina (+54) y se le agrega
     *   el prefijo de móvil (9) si no lo trae.
     *
     * Devuelve null si la entrada está vacía, no tiene dígitos, o si el
     * número argentino es AMBIGUO (no se puede determinar el código de
     * área sin inventarlo).
     *
     * Para números argentinos el chequeo es ESTRICTO (área real + móvil),
     * no solo "tiene forma de E.164": un local de 9 dígitos como
     * `934482229` tiene forma válida pero su área es indeterminada.
     * Adivinarla haría que un `wa.me` le escriba a otra persona, así que
     * se devuelve null y la UI muestra "teléfono no válido".
     */
    public function normalizar(?string $telefono): ?string
    {
        if ($telefono === null) {
            return null;
        }

        $trimmed = trim($telefono);
        if ($trimmed === '') {
            return null;
        }

        $tieneMas = Str::startsWith($trimmed, '+');
        $digitos = preg_replace('/\D+/', '', $trimmed);

        if ($digitos === null || $digitos === '') {
            return null;
        }

        // Código de país explícito: se respeta tal cual (no asumimos
        // Argentina). Ej: '+34 906-329365' -> '+34906329365'.
        if ($tieneMas) {
            return $this->esE164Valido('+'.$digitos) ? '+'.$digitos : null;
        }

        // Sin '+': puede venir con el código de país pegado (54911...) o
        // como número local (3412345678 / 11 1234-5678).
        $codigoPais = self::DEFAULT_COUNTRY_CODE;
        $local = $digitos;

        if (Str::startsWith($digitos, $codigoPais) && strlen($digitos) > self::LOCAL_MIN) {
            $local = Str::after($digitos, $codigoPais);
        }

        // Un móvil argentino en formato local NO tiene el 9 adelante: el
        // prefijo de móvil se agrega en formato internacional.
        if ($local !== '' && ! Str::startsWith($local, self::ARG_MOVIL_PREFIJO)) {
            $local = self::ARG_MOVIL_PREFIJO.$local;
        }

        // Largo local (área + número) debe caer en el rango válido.
        $largoLocal = strlen($local);
        if ($largoLocal < self::LOCAL_MIN || $largoLocal > self::LOCAL_MAX) {
            return null;
        }

        $normalizado = '+'.$codigoPais.$local;

        if (! $this->esE164Valido($normalizado)) {
            return null;
        }

        // Chequeo estricto del móvil argentino: sin esto, `934482229`
        // (9 dígitos, área indeterminada) se convertiría en
        // `+54934482229` y quedaría guardado como si fuera real.
        if (! $this->esMovilArgentinoValido($normalizado)) {
            return null;
        }

        return $normalizado;
    }

    /**
     * ¿El teléfono es válido y puede generar un link de WhatsApp?
     *
     * Un número inválido NO debe generar un link roto: la UI tiene que
     * poder mostrar el estado "teléfono no válido" en lugar de mandar al
     * usuario a un 404 de WhatsApp.
     *
     * Para números argentinos usa la validación ESTRICTA (área real +
     * móvil), no solo la forma E.164: un local de 9 dígitos ambiguo
     * como `934-482229` tiene forma de E.164 pero no se sabe a qué
     * área pertenece, y adivinar haría que el link de WhatsApp le
     * escriba a otra persona.
     */
    public function esValido(?string $telefono): bool
    {
        return $this->esMovilArgentinoValido($telefono);
    }

    /**
     * Códigos de área válidos de Argentina (2 y 3 dígitos).
     *
     * Se usan para detectar los números AMBIGUOS del seed: un local de 9
     * dígitos que arranca con 9 (ej. '934-482229') NO se puede convertir
     * con seguridad, porque el 9 inicial puede ser el prefijo de móvil o
     * parte del área.
     *
     * @var array<int, string>
     */
    private const AREAS_AR = [
        // CABA yGBA
        '11',
        // 3 dígitos (interior)
        '221', '223', '230', '236', '237', '238', '239', '240', '241',
        '243', '245', '246', '247', '248', '249', '250', '251', '252',
        '253', '254', '255', '256', '257', '258', '260', '261', '262',
        '263', '264', '265', '266', '267', '268', '269', '280', '281',
        '283', '284', '285', '287', '288', '289', '290', '291', '292',
        '293', '294', '295', '296', '297', '298', '299',
        // 2 dígitos (Buenos Aires interurbano y generalizations)
        '220', '222', '225', '226', '227', '228', '229', '232', '233',
        '234', '235', '239', '262', '264', '266',
        '290', '291', '292', '293', '294', '295', '296', '297', '298',
        '299', '331', '332', '333', '334', '336', '337', '338',
        '340', '341', '342', '343', '345', '346', '348', '349',
        '351', '352', '353', '354', '357', '358', '360', '362', '364',
        '370', '376', '379', '380', '381', '383', '385', '386', '387',
        '388',
    ];

    /**
     * ¿El número es normalizable a un móvil argentino válido?
     *
     * `+54` + `9` + área (2-3 dígitos) + número (6-8 dígitos).
     *
     * El ÁREA se valida contra la lista real de códigos de área de
     * Argentina. Sin ese chequeo, `934482229` (9 dígitos del seed)
     * pasaría el filtro de largo y se convertiría en `+54934482229`
     * — con un área inventada. Un `wa.me` equivocado le escribe a
     * otra persona, así que es mejor dejar el teléfono vacío y que la
     * UI muestre "teléfono no válido".
     *
     * Los números internacionales con otro código de país se aceptan
     * con la regla E.164 genérica.
     */
    public function esMovilArgentinoValido(?string $telefono): bool
    {
        // ⚠️ NO llamar a `normalizar()` acá: `normalizar()` ya llama a este
        // método para validar los números argentinos y eso sería recursión
        // infinita. Se trabaja sobre la entrada cruda.
        if ($telefono === null) {
            return false;
        }

        $trimmed = trim($telefono);
        if ($trimmed === '') {
            return false;
        }

        $tieneMas = Str::startsWith($trimmed, '+');
        $digitos = preg_replace('/\D+/', '', $trimmed);

        if ($digitos === null || $digitos === '') {
            return false;
        }

        // Código de país explícito distinto de Argentina: se acepta con
        // la regla E.164 genérica.
        if ($tieneMas && ! Str::startsWith($trimmed, '+54')) {
            return $this->esE164Valido('+'.$digitos);
        }

        $codigoPais = self::DEFAULT_COUNTRY_CODE;
        $local = $digitos;

        if (Str::startsWith($digitos, $codigoPais) && strlen($digitos) > self::LOCAL_MIN) {
            $local = Str::after($digitos, $codigoPais);
        }

        if ($local !== '' && ! Str::startsWith($local, self::ARG_MOVIL_PREFIJO)) {
            $local = self::ARG_MOVIL_PREFIJO.$local;
        }

        $largoLocal = strlen($local);
        if ($largoLocal < self::LOCAL_MIN || $largoLocal > self::LOCAL_MAX) {
            return false;
        }

        if (! Str::startsWith($local, self::ARG_MOVIL_PREFIJO)) {
            return false;
        }

        $sinMovil = substr($local, 1); // área + número

        // El área puede ser de 2 o 3 dígitos. Se prueba la más larga
        // primero (Tucumán = 381, que es de 3).
        if (strlen($sinMovil) >= 3 && in_array(substr($sinMovil, 0, 3), self::AREAS_AR, true)) {
            // Área de 3: número local = 3 + (6-8) = 9 a 11 dígitos.
            return strlen($sinMovil) >= 9 && strlen($sinMovil) <= 11;
        }

        if (strlen($sinMovil) >= 2 && in_array(substr($sinMovil, 0, 2), self::AREAS_AR, true)) {
            // Área de 2: número local = 2 + (6-8) = 8 a 10 dígitos.
            return strlen($sinMovil) >= 8 && strlen($sinMovil) <= 10;
        }

        // Área desconocida: no se puede afirmar que sea un móvil
        // argentino válido.
        return false;
    }

    /**
     * Valida un string ya en formato E.164.
     *
     * E.164: máx 15 dígitos, código de país de 1 a 3, sin empezar en 0.
     */
    private function esE164Valido(string $numero): bool
    {
        return (bool) preg_match('/^\+[1-9]\d{6,14}$/', $numero);
    }

    /**
     * Genera el link de WhatsApp con un mensaje prellenado.
     *
     * Devuelve null si el teléfono no es válido, para que la UI pueda
     * distinguir "no se puede generar" de "generado".
     */
    public function whatsappLink(?string $telefono, string $mensaje = ''): ?string
    {
        // Validación ESTRICTA: un número ambiguo no genera link, porque
        // un `wa.me` equivocado le escribe a otra persona.
        if (! $this->esValido($telefono)) {
            return null;
        }

        $normalizado = $this->normalizar($telefono);

        $url = 'https://wa.me/'.$normalizado;

        if ($mensaje !== '') {
            $url .= '?text='.rawurlencode($mensaje);
        }

        return $url;
    }

    /**
     * Formato legible para mostrar en la UI: +54 9 341 123-4567.
     *
     * El área puede ser de 2 (CABA = 11) o 3 dígitos (Tucumán = 381).
     * Sin detectarla, `+54 9 381 1234567` se mostraría como
     * `+54 9 38 112-34567`, que es un número que no existe.
     */
    public function formatear(?string $telefono): ?string
    {
        $normalizado = $this->normalizar($telefono);

        if ($normalizado === null) {
            return null;
        }

        // '+54' son 3 caracteres (el signo cuenta): hay que saltear
        // el prefijo COMPLETO, no solo el signo.
        if (! str_starts_with($normalizado, '+54')) {
            return $normalizado;
        }

        $digitos = substr($normalizado, 3);

        if (strlen($digitos) < 10) {
            return $normalizado;
        }

        $movil = substr($digitos, 0, 1);
        $local = substr($digitos, 1);

        // Área de 3 dígitos si el prefijo está en la lista de áreas
        // argentinas de 3 cifras; si no, 2.
        $largoArea = in_array(substr($local, 0, 3), self::AREAS_AR, true) ? 3 : 2;

        $area = substr($local, 0, $largoArea);
        $numero = substr($local, $largoArea);

        if ($numero === '') {
            return $normalizado;
        }

        // Separador de miles: los últimos 4 dígitos van juntos.
        if (strlen($numero) > 4) {
            $corte = strlen($numero) - 4;
            $numero = substr($numero, 0, $corte).'-'.substr($numero, $corte);
        }

        return '+54 '.$movil.' '.$area.' '.$numero;
    }
}
