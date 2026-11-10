<?php

namespace App\Support;

use App\Models\User;

/**
 * Fuente única de verdad de los permisos por rol (Fase 0.1 / plan §12.1).
 *
 * POR QUÉ EXISTE
 * --------------
 * Antes había DOS sistemas de roles en paralelo:
 *
 *   - `users.role` (string) — el que realmente se usaba. `User::hasRole()`
 *     leía este string, y TODOS los checks de autorización (middleware
 *     `role:`, gates, policies) dependían de él.
 *
 *   - `roles` + `role_user` + `Role.permissions` (JSON) — el que estaba
 *     DECLARADO pero nunca se aplicaba. La tabla `role_user` tenía 0 filas
 *     y `permissions` no se evaluaba en ninguna ruta, policy ni gate.
 *
 * Eso producía consecuencias concretas y verificables:
 *
 *   - `coordinador` tenía permisos declarados de `ver_estadisticas`,
 *     `ver_membresias` y `registrar_pagos`, pero en la práctica solo podía
 *     entrar a audit-logs.
 *   - `recepcionista` no tenía NI UNA ruta, y tampoco era asignable desde
 *     el panel (la validación de roles no lo incluía).
 *
 * DECISIÓN (Fase 0.1)
 * -------------------
 * `users.role` es la fuente de verdad. Esta clase es el ÚNICO lugar donde
 * se define qué puede hacer cada rol. Los gates, las policies y el
 * middleware `role:` consultan acá.
 *
 * La tabla `roles` y su JSON `permissions` quedan como dato descriptivo
 * (no se eliminan para no romper el seed ni los joins existentes), pero
 * NINGÚN chequeo de autorización los consulta.
 */
class RolePermissions
{
    /**
     * Roles válidos del sistema.
     *
     * `coordinador` y `recepcionista` NO estaban en la validación de los
     * controllers (`in:comun,alumno,trainer,administrador`), así que era
     * IMPOSIBLE asignarlos desde el panel aunque las policies los
     contemplaran. Ahora están acá y se propagan a los controllers.
     */
    public const ROLES = [
        User::ROLE_COMUN,
        User::ROLE_ALUMNO,
        User::ROLE_TRAINER,
        User::ROLE_COORDINADOR,
        User::ROLE_RECEPCIONISTA,
        User::ROLE_ADMINISTRADOR,
    ];

    // --- Roles que representan al dueño/staff del gym ---
    public const ROLE_COORDINADOR = 'coordinador';

    public const ROLE_RECEPCIONISTA = 'recepcionista';

    /**
     * Permisos por rol.
     *
     * La matriz proviene de la propuesta del plan (§12.1), con dos
     * correcciones:
     *
     *  - "Ver balances / MRR" está SEPARADO de "Ver membresías": esa
     *    separación es la que hace que `recepcionista` sea un rol útil
     *    sin ser un riesgo (ve membresías y cobra, pero no ve el balance
     *    global ni la auditoría).
     *
     *  - `coordinador` NO ve balances: sus permisos declarados en el
     *    `Role` original incluían `registrar_pagos`, pero el plan (§18,
     *    pregunta abierta #3) lo había dejado sin definir. Acá se le
     *    permite registrar pagos — que es operativo — pero no ver el
     *    balance global.
     */
    public const MATRIZ = [
        User::ROLE_ADMINISTRADOR => [
            'ver_dashboard',
            'ver_rutinas',
            'crear_rutinas',
            'compartir_rutinas',
            'importar_rutinas',
            'ver_ejercicios',
            'ver_historial',
            'ver_progreso',
            'ver_nutricion',
            'crear_ejercicios_privados',
            'ver_alumnos',
            'gestionar_alumnos',
            'ver_progreso_alumnos',
            'comentar_historial',
            'gestionar_usuarios',
            'suspender_usuarios',
            'eliminar_usuarios',
            'asignar_trainers',
            'ver_membresias',
            'registrar_pagos',
            'gestionar_planes',
            'ver_balances',
            'ver_estadisticas',
            'ver_reportes',
            'ver_audit_logs',
            'importar_exportar',
            'gestionar_catalogo',
            'check_in',
            'gestionar_configuracion',
        ],
        User::ROLE_COORDINADOR => [
            'ver_dashboard',
            'ver_rutinas',
            'crear_rutinas',
            'compartir_rutinas',
            'ver_ejercicios',
            'ver_historial',
            'ver_progreso',
            'ver_alumnos',
            'gestionar_alumnos',
            'ver_progreso_alumnos',
            'comentar_historial',
            'gestionar_usuarios',
            'asignar_trainers',
            'ver_membresias',
            'registrar_pagos',
            'ver_estadisticas',
            'ver_audit_logs',
            'check_in',
        ],
        User::ROLE_RECEPCIONISTA => [
            'ver_dashboard',
            'ver_rutinas',
            'ver_ejercicios',
            'ver_historial',
            'ver_progreso',
            'gestionar_usuarios',
            'ver_membresias',
            'registrar_pagos',
            'check_in',
        ],
        User::ROLE_TRAINER => [
            'ver_dashboard',
            'ver_rutinas',
            'crear_rutinas',
            'compartir_rutinas',
            'importar_rutinas',
            'ver_ejercicios',
            'ver_historial',
            'ver_progreso',
            'ver_nutricion',
            'ver_alumnos',
            'gestionar_alumnos',
            'crear_ejercicios_privados',
            'ver_progreso_alumnos',
            'comentar_historial',
            'gestionar_catalogo',
        ],
        User::ROLE_ALUMNO => [
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
        User::ROLE_COMUN => [
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
    ];

    /**
     * Permisos que el administrador tiene implícitamente.
     * Se agrega 'ver_balances' y 'gestionar_configuracion' al rol admin
     * para que un permiso nuevo no requiera tocar el admin a mano.
     */
    public const PERMISOS_ADMIN = ['*'];

    // -----------------------------------------------------------------
    // Consultas
    // -----------------------------------------------------------------

    /**
     * ¿El usuario tiene este permiso?
     */
    public static function tiene(User $user, string $permiso): bool
    {
        if ($user->hasRole(User::ROLE_ADMINISTRADOR)) {
            return true;
        }

        return in_array($permiso, self::permisosDe($user->normalizedRole()), true);
    }

    /**
     * ¿El usuario tiene alguno de estos permisos?
     *
     * @param  array<int, string>  $permisos
     */
    public static function tieneAlguno(User $user, array $permisos): bool
    {
        foreach ($permisos as $permiso) {
            if (self::tiene($user, $permiso)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Permisos efectivos de un rol.
     *
     * @return array<int, string>
     */
    public static function permisosDe(string $rol): array
    {
        return self::MATRIZ[$rol] ?? [];
    }

    /**
     * ¿El string es un rol válido del sistema?
     */
    public static function esRolValido(?string $rol): bool
    {
        return $rol !== null && in_array($rol, self::ROLES, true);
    }

    /**
     * Roles que pueden administrar usuarios (alta, edición).
     *
     * @return array<int, string>
     */
    public static function rolesQueGestionanUsuarios(): array
    {
        return [
            User::ROLE_ADMINISTRADOR,
            self::ROLE_COORDINADOR,
            self::ROLE_RECEPCIONISTA,
        ];
    }

    /**
     * Roles que pueden ver el balance global (MRR, ingresos).
     *
     * Deliberadamente NO incluye a `recepcionista` ni a `coordinador`:
     * esa separación es la que hace que `recepcionista` sea útil sin
     * ser un riesgo financiero.
     *
     * @return array<int, string>
     */
    public static function rolesQueVenBalances(): array
    {
        return [
            User::ROLE_ADMINISTRADOR,
        ];
    }

    /**
     * Roles con acceso al check-in de recepción.
     *
     * @return array<int, string>
     */
    public static function rolesQueHacenCheckIn(): array
    {
        return [
            User::ROLE_ADMINISTRADOR,
            self::ROLE_COORDINADOR,
            self::ROLE_RECEPCIONISTA,
        ];
    }

    /**
     * La matriz completa, para la UI de configuración y los tests.
     *
     * @return array<string, array<int, string>>
     */
    public static function matrizCompleta(): array
    {
        return self::MATRIZ;
    }

    /**
     * Lista de roles para un `<select>` en el frontend.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function opciones(): array
    {
        return [
            ['value' => User::ROLE_COMUN, 'label' => 'Común'],
            ['value' => User::ROLE_ALUMNO, 'label' => 'Alumno'],
            ['value' => User::ROLE_TRAINER, 'label' => 'Trainer'],
            ['value' => self::ROLE_COORDINADOR, 'label' => 'Coordinador'],
            ['value' => self::ROLE_RECEPCIONISTA, 'label' => 'Recepcionista'],
            ['value' => User::ROLE_ADMINISTRADOR, 'label' => 'Administrador'],
        ];
    }
}
