<?php

namespace App\Services;

use App\Models\GymConfig;
use Illuminate\Support\Facades\Cache;

/**
 * Servicio de configuración del gimnasio (Fase 0.4).
 *
 * POR QUÉ EXISTE
 * --------------
 * El plan (planAdmin.md §12.2) lo marca como BLOQUEANTE de la Fase 1
 * por tres razones concretas:
 *
 *  1. Hay valores hardcodeados que la app necesita cambiar:
 *     `CheckMembership::GRACE_PERIOD_DAYS = 3` (constante de clase), la
 *     duración del trial, los días de aviso de vencimiento, la moneda y
 *     los métodos de pago.
 *
 *  2. El plan exige zona horaria configurable (§8.3): hoy todo usa el
 *     `now()` implícito del servidor, que es UTC. Un gym de Tucumán que
 *     cobra "el día 1" con fecha UTC puede desfasar un día.
 *
 *  3. Sin esto, cada feature nueva mete un `if` más.
 *
 * CACHÉ
 * -----
 * Los valores se cachean con TTL corto y se invalidan por evento
 * (`forget()` explícito) cuando se escribe. El TTL es una red de
 * seguridad: si una escritura falla y deja el cache desactualizado, el
 * valor se recupera solo en 5 minutos.
 */
class GymConfigService
{
    /** TTL del cache de configuración (5 minutos). */
    private const CACHE_TTL = 300;

    private const CACHE_KEY = 'gym.config';

    /**
     * Defaults de la aplicación.
     *
     * Son el contrato: si la clave no está en la tabla `gym_config`, se
     * usa este valor. Los defaults están tipados y centralizados acá
     * (y no en `config/app.php`) para que el panel de configuración y el
     * código de negocio lean siempre el mismo lugar.
     */
    public const DEFAULTS = [
        // Identidad
        'nombre' => 'GymApp',
        'email_contacto' => 'soporte@gymapp.com',
        'telefono_contacto' => '+1234567890',
        'moneda' => 'ARS',
        'simbolo_moneda' => '$',
        'timezone' => 'America/Argentina/Tucuman',

        // Acceso
        'trial_dias' => 14,
        'grace_days' => 3,
        'dias_aviso_vencimiento' => 7,

        // Negocio
        'metodos_pago' => ['efectivo', 'transferencia'],
        'dias_horarios' => ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado'],
        'hora_apertura' => '07:00',
        'hora_cierre' => '22:00',

        // Features
        'whatsapp_habilitado' => true,
        'mostrar_bienestar' => true,
    ];

    /**
     * Categorías, para la UI del panel de configuración.
     */
    public const CATEGORIAS = [
        'general' => 'General',
        'acceso' => 'Acceso y membresías',
        'negocio' => 'Negocio',
        'features' => 'Funcionalidades',
    ];

    // -----------------------------------------------------------------
    // Lectura
    // -----------------------------------------------------------------

    /**
     * Devuelve todos los parámetros con sus defaults aplicados.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            $guardados = GymConfig::pluck('value', 'key')->toArray();

            // Los defaults se aplican primero y la tabla los pisa:
            // así un parámetro nuevo aparece con valor sin tocar la BD.
            return array_merge(self::DEFAULTS, $guardados);
        });
    }

    /**
     * Devuelve un parámetro puntual, o su default si no está configurado.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $todos = $this->all();

        if (array_key_exists($key, $todos)) {
            return $todos[$key];
        }

        return $default;
    }

    public function getInt(string $key, int $default = 0): int
    {
        $valor = $this->get($key, $default);

        return is_numeric($valor) ? (int) $valor : $default;
    }

    public function getBool(string $key, bool $default = false): bool
    {
        $valor = $this->get($key, $default);

        if (is_bool($valor)) {
            return $valor;
        }

        return filter_var($valor, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @return array<int, string>
     */
    public function getArray(string $key, array $default = []): array
    {
        $valor = $this->get($key, $default);

        return is_array($valor) ? $valor : $default;
    }

    // -----------------------------------------------------------------
    // Atajos de uso frecuente
    // -----------------------------------------------------------------

    /** Días de trial de bienvenida para alumnos nuevos. */
    public function trialDias(): int
    {
        return max(0, $this->getInt('trial_dias', self::DEFAULTS['trial_dias']));
    }

    /** Días de gracia después del vencimiento antes de bloquear. */
    public function graceDays(): int
    {
        return max(0, $this->getInt('grace_days', self::DEFAULTS['grace_days']));
    }

    /** Días de anticipación para avisar del vencimiento. */
    public function diasAvisoVencimiento(): int
    {
        return max(0, $this->getInt('dias_aviso_vencimiento', self::DEFAULTS['dias_aviso_vencimiento']));
    }

    public function moneda(): string
    {
        return (string) $this->get('moneda', self::DEFAULTS['moneda']);
    }

    public function simboloMoneda(): string
    {
        return (string) $this->get('simbolo_moneda', self::DEFAULTS['simbolo_moneda']);
    }

    public function nombre(): string
    {
        return (string) $this->get('nombre', self::DEFAULTS['nombre']);
    }

    public function timezone(): string
    {
        $tz = (string) $this->get('timezone', self::DEFAULTS['timezone']);

        return in_array($tz, timezone_identifiers_list(), true) ? $tz : self::DEFAULTS['timezone'];
    }

    /**
     * @return array<int, string>
     */
    public function metodosPago(): array
    {
        return $this->getArray('metodos_pago', self::DEFAULTS['metodos_pago']);
    }

    // -----------------------------------------------------------------
    // Escritura
    // -----------------------------------------------------------------

    /**
     * Escribe un parámetro e invalida el cache.
     */
    public function set(string $key, mixed $value, ?string $categoria = null, ?string $descripcion = null): GymConfig
    {
        $config = GymConfig::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'categoria' => $categoria ?? $this->categoriaDe($key),
                'descripcion' => $descripcion,
            ]
        );

        $this->forget();

        return $config;
    }

    /**
     * Escribe varios parámetros en una transacción.
     *
     * @param  array<string, mixed>  $valores
     */
    public function setMany(array $valores): void
    {
        \DB::transaction(function () use ($valores) {
            foreach ($valores as $key => $value) {
                GymConfig::updateOrCreate(
                    ['key' => $key],
                    [
                        'value' => $value,
                        'categoria' => $this->categoriaDe($key),
                    ]
                );
            }
        });

        $this->forget();
    }

    /**
     * Restaura un parámetro a su default (elimina la fila).
     */
    public function remove(string $key): void
    {
        GymConfig::where('key', $key)->delete();

        $this->forget();
    }

    /**
     * Invalida el cache de configuración.
     *
     * Se llama desde el observer de GymConfig, así que cualquier cambio
     * (no solo los de este service) refresca la config.
     */
    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Invalida el cache cuando cambia la configuración del gym.
     *
     * @param  array<int, string>  $keys
     */
    public function forgetKeys(array $keys): void
    {
        $this->forget();
    }

    /**
     * Categoría por defecto de un parámetro, según su prefijo.
     */
    private function categoriaDe(string $key): string
    {
        return match (true) {
            (bool) preg_match('/^(trial_|grace_|dias_aviso)/', $key) => 'acceso',
            (bool) preg_match('/^(metodos_pago|dias_horarios|hora_|moneda|simbolo_)/', $key) => 'negocio',
            (bool) preg_match('/^(whatsapp_|mostrar_)/', $key) => 'features',
            default => 'general',
        };
    }
}
