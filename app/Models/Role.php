<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Roles declarados del sistema.
 *
 * ⚠️  ESTA TABLA ES DESCRIPTIVA, NO ES LA FUENTE DE VERDAD (Fase 0.1)
 * --------------------------------------------------------------------
 * La fuente de verdad de los permisos es `users.role` (string), consultada
 * a través de `App\Support\RolePermissions`.
 *
 * Antes de la Fase 0.1 existían DOS sistemas de roles en paralelo:
 *
 *   - `users.role` — el que realmente se aplicaba. `User::hasRole()` leía
 *     este string, y de él dependían TODOS los chequeos de autorización
 *     (middleware `role:`, gates, policies).
 *   - `roles` + `role_user` + este JSON `permissions` — declarado pero
 *     nunca aplicado. La tabla `role_user` tenía 0 filas y `permissions` no
 *     se evaluaba en ninguna ruta, policy ni gate.
 *
 * Esa duplicidad producía Hole de seguridad concreto:
 * `coordinador` tenía permisos declarados de `ver_estadisticas`,
 * `ver_membresias` y `registrar_pagos`, pero solo podía entrar a
 * audit-logs; y `recepcionista` no tenía ni una ruta.
 *
 * La tabla NO se elimina (rompería el seed y joins existentes), pero:
 *
 *   - `hasPermission()` y `permissions` NO deben usarse para decidir
 *     autorización. Usá `RolePermissions::tiene()`.
 *   - `role_user` queda con 0 filas y sin enforcement: la relación
 *     `User::roles()` sigue existiendo por compatibilidad, pero no
 *     participa de ninguna decisión de acceso.
 *
 * @see \App\Support\RolePermissions — matriz real de permisos
 */
class Role extends Model
{
    protected $fillable = [
        'name',
        'description',
        'permissions',
    ];

    protected $casts = [
        'permissions' => 'array',
    ];

    public const COMUN = 'comun';

    public const ADMINISTRADOR = 'administrador';

    public const TRAINER = 'trainer';

    public const RECEPCIONISTA = 'recepcionista';

    public const COORDINADOR = 'coordinador';

    /**
     * @return array<string, array{description: string, permissions: array<int, string>}>
     */
    public static function defaultRoles(): array
    {
        return [
            self::COMUN => [
                'description' => 'Usuario regular con acceso libre',
                'permissions' => [
                    'ver_dashboard',
                    'ver_rutinas',
                    'crear_rutinas',
                    'compartir_rutinas',
                    'importar_rutinas',
                    'ver_ejercicios',
                    'ver_historial',
                    'ver_progreso',
                    'ver_nutricion',
                ],
            ],
            self::ADMINISTRADOR => [
                'description' => 'Administrador del sistema',
                'permissions' => ['*'],
            ],
            self::TRAINER => [
                'description' => 'Entrenador personal',
                'permissions' => [
                    'ver_dashboard',
                    'ver_alumnos',
                    'gestionar_alumnos',
                    'crear_rutinas',
                    'ver_ejercicios',
                    'crear_ejercicios_privados',
                    'ver_progreso_alumnos',
                    'comentar_historial',
                ],
            ],
            self::RECEPCIONISTA => [
                'description' => 'Personal de recepción',
                'permissions' => [
                    'ver_dashboard',
                    'registrar_usuarios',
                    'ver_usuarios',
                    'ver_membresias',
                    'registrar_pagos',
                ],
            ],
            self::COORDINADOR => [
                'description' => 'Coordinador de trainers',
                'permissions' => [
                    'ver_dashboard',
                    'ver_trainers',
                    'asignar_trainers',
                    'ver_alumnos',
                    'ver_estadisticas',
                    'ver_audit_logs',
                ],
            ],
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'role_user');
    }

    /**
     * ⚠️  NO USAR PARA AUTORIZACIÓN.
     *
     * Este método lee el JSON `permissions` de la tabla `roles`, que es
     * DECORATIVO: nunca.decidió el acceso a nada (Fase 0.1).
     *
     * Para decidir autorización usá:
     *
     *     RolePermissions::tiene($user, 'ver_balances')
     *     $user->tienePermiso('ver_balances')
     */
    public function hasPermission(string $permission): bool
    {
        if (in_array('*', $this->permissions ?? [], true)) {
            return true;
        }

        return in_array($permission, $this->permissions ?? [], true);
    }

    /**
     * Sincroniza la tabla `roles` con la matriz de permisos real.
     *
     * La tabla es descriptiva, pero sus textos tienen que contar la
     * historia correcta: si dice que `recepcionista` puede "ver_usuarios"
     * pero la matriz real no lo incluye, el panel miente.
     */
    public static function seedDefaults(): void
    {
        $matriz = \App\Support\RolePermissions::matrizCompleta();

        foreach (self::defaultRoles() as $name => $data) {
            $permissions = $name === self::ADMINISTRADOR
                ? ['*']
                : ($matriz[$name] ?? $data['permissions']);

            self::updateOrCreate(
                ['name' => $name],
                [
                    'description' => $data['description'],
                    'permissions' => $permissions,
                ]
            );
        }
    }
}
