<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Cache por usuario del historial (stats, body map, 1RM,Musculoskeletal).
 *
 * Esos endpoints se cachean 5-10 minutos para no golpear la DB en cada
 * visita, pero sin invalidación el usuario ve datos viejos justo después de
 * entrenar (el peor momento: acaba de guardar la serie y el KPI no se mueve).
 *
 * Solución: cada usuario tiene un token de versión en cache. Todas sus claves
 * lo incluyen, así que al guardar/editar una serie se incrementa el token y
 * las claves viejas quedan huérfanas (y expiran solas).
 *
 * A diferencia de Cache::tags, esto funciona con cualquier driver de cache
 * (incluido 'database', que no soporta tags).
 */
class CacheHistorial
{
    /**
     * Versión actual del cache de un usuario (1 = inicial).
     */
    public static function version(int $userId): int
    {
        return (int) Cache::get(self::versionKey($userId), 1);
    }

    /**
     * Invalida todo el cache derivado del historial de un usuario.
     * Llamar después de cualquier escritura en `historials`.
     */
    public static function invalidate(int $userId): void
    {
        // `Cache::increment()` hace el read-modify-write de forma ATÓMICA
        // (el driver `database` lo resuelve con un UPDATE ... value = value + 1).
        // Antes era un `Cache::forever($key, self::version($userId) + 1)`: un
        // read-modify-write sin lock, así que dos requests concurrentes
        // podían escribir la misma versión y una invalidación se perdía
        // (el usuario veía datos viejos justo después de entrenar).
        if (! Cache::has(self::versionKey($userId))) {
            Cache::forever(self::versionKey($userId), 1);
        }

        Cache::increment(self::versionKey($userId));
    }

    /**
     * Construye la clave de cache de un usuario, con su token de versión.
     *
     * @param  string  $suffix  prefijo del recurso, p.ej. 'stats:resumen'
     */
    public static function key(int $userId, string $suffix): string
    {
        return "{$suffix}:user:{$userId}:v".self::version($userId);
    }

    private static function versionKey(int $userId): string
    {
        return "cachever:user:{$userId}";
    }
}
