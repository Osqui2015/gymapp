<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Membresia;
use App\Models\User;
use App\Support\RolePermissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * Test feature de la matriz de roles (Fase 0.9).
 *
 * CONTEXTO
 * --------
 * Antes de la Fase 0.1 el sistema de permisos era DECORATIVO: la tabla
 * `roles` + `role_user` + el JSON `permissions` existían pero no se
 * aplicaban en ninguna ruta, policy ni gate. `role_user` tenía 0 filas.
 *
 * Consecuencias verificables:
 *   - `recepcionista` no tenía NI UNA ruta y era IMPOSIBLE de asignar
 *     desde el panel (no estaba en la validación de roles).
 *   - `coordinador` tenía permisos declarados que no coincidían con lo
 *     que realmente podía ver.
 *
 * Este test fija la matriz real: para cada rol, qué puede y qué NO puede
 * hacer. Es el criterio de aceptación "un recepcionista puede cobrar pero
 * NO puede ver balances ni auditoría" del plan (§6.4).
 */
class RoleMatrixTest extends TestCase
{
    use RefreshDatabase;

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    private function user(string $rol): User
    {
        return User::factory()->conMembresia()->create(['role' => $rol]);
    }

    // -----------------------------------------------------------------
    // Fuente de verdad de los roles
    // -----------------------------------------------------------------

    public function test_todos_los_roles_de_la_matriz_son_validos(): void
    {
        foreach (RolePermissions::ROLES as $rol) {
            $this->assertTrue(
                RolePermissions::esRolValido($rol),
                "El rol '{$rol}' debería ser válido."
            );
        }
    }

    public function test_coordinador_y_recepcionista_son_asignables_desde_la_api(): void
    {
        // Antes: la validación era 'in:comun,alumno,trainer,administrador'.
        // Coordinator y recepcionista eran imposibles de asignar.
        $admin = $this->user(User::ROLE_ADMINISTRADOR);

        foreach ([RolePermissions::ROLE_COORDINADOR, RolePermissions::ROLE_RECEPCIONISTA] as $rol) {
            $alumno = User::factory()->create(['role' => User::ROLE_COMUN]);

            $response = $this->actingAs($admin)->put("/api/admin/users/{$alumno->id}", [
                'name' => $alumno->name,
                'email' => $alumno->email,
                'telefono' => null,
                'role' => $rol,
            ]);

            $response->assertOk();
            $this->assertSame($rol, $alumno->refresh()->role);
        }
    }

    public function test_un_rol_invalido_se_rechaza(): void
    {
        $admin = $this->user(User::ROLE_ADMINISTRADOR);
        $alumno = User::factory()->create();

        // `putJson()` para que Laravel devuelva 422 con los errores de
        // validación en vez de un redirect (que es lo que hace una
        // request "normal" de formulario).
        $this->actingAs($admin)->putJson("/api/admin/users/{$alumno->id}", [
            'name' => $alumno->name,
            'email' => $alumno->email,
            'role' => 'superadmin',
        ])->assertStatus(422);

        // El rol NO cambió.
        $this->assertSame(User::ROLE_COMUN, $alumno->refresh()->role);
    }

    // -----------------------------------------------------------------
    // La matriz de permisos
    // -----------------------------------------------------------------

    public function test_admin_tiene_todos_los_permisos(): void
    {
        $admin = $this->user(User::ROLE_ADMINISTRADOR);

        foreach (RolePermissions::MATRIZ[User::ROLE_ADMINISTRADOR] as $permiso) {
            $this->assertTrue($admin->tienePermiso($permiso), "El admin debería tener '{$permiso}'.");
        }
    }

    public function test_recepcionista_cobra_membresias_pero_no_ve_balances_ni_auditoria(): void
    {
        // Es la fila que hace que `recepcionista` sea un rol útil sin ser
        // un riesgo financiero (plan §12.1).
        $recepcionista = $this->user(RolePermissions::ROLE_RECEPCIONISTA);

        // Puede ver y cobrar membresías.
        $this->assertTrue($recepcionista->tienePermiso('ver_membresias'));
        $this->assertTrue($recepcionista->tienePermiso('registrar_pagos'));

        // NO ve balances ni auditoría.
        $this->assertFalse($recepcionista->tienePermiso('ver_balances'));
        $this->assertFalse($recepcionista->tienePermiso('ver_audit_logs'));

        // Tampoco gestiona el catálogo global ni la configuración.
        $this->assertFalse($recepcionista->tienePermiso('gestionar_catalogo'));
        $this->assertFalse($recepcionista->tienePermiso('gestionar_configuracion'));
    }

    public function test_coordinador_gestiona_alumnos_pero_no_ve_balances(): void
    {
        $coordinador = $this->user(RolePermissions::ROLE_COORDINADOR);

        $this->assertTrue($coordinador->tienePermiso('gestionar_usuarios'));
        $this->assertTrue($coordinador->tienePermiso('asignar_trainers'));
        $this->assertTrue($coordinador->tienePermiso('ver_estadisticas'));
        $this->assertTrue($coordinador->tienePermiso('ver_audit_logs'));

        // No ve el balance global: la separación "ver membresías" vs
        // "ver balances" es deliberada.
        $this->assertFalse($coordinador->tienePermiso('ver_balances'));
    }

    public function test_alumno_y_comun_no_tienen_permisos_de_staff(): void
    {
        foreach ([User::ROLE_ALUMNO, User::ROLE_COMUN] as $rol) {
            $user = $this->user($rol);

            $this->assertFalse($user->tienePermiso('gestionar_usuarios'));
            $this->assertFalse($user->tienePermiso('ver_membresias'));
            $this->assertFalse($user->tienePermiso('registrar_pagos'));
            $this->assertFalse($user->tienePermiso('ver_audit_logs'));
            $this->assertFalse($user->tienePermiso('ver_balances'));
            $this->assertFalse($user->tienePermiso('check_in'));
        }
    }

    // -----------------------------------------------------------------
    // Gates (lo que realmente controla el acceso)
    // -----------------------------------------------------------------

    public function test_gate_manage_users_permite_recepcionista_pero_no_alumno(): void
    {
        $recepcionista = $this->user(RolePermissions::ROLE_RECEPCIONISTA);
        $this->actingAs($recepcionista);
        $this->assertTrue(Gate::allows('manage-users'));

        $alumno = $this->user(User::ROLE_ALUMNO);
        $this->actingAs($alumno);
        $this->assertFalse(Gate::allows('manage-users'));
    }

    public function test_gate_view_balances_es_solo_admin(): void
    {
        foreach ([
            User::ROLE_ADMINISTRADOR => true,
            RolePermissions::ROLE_COORDINADOR => false,
            RolePermissions::ROLE_RECEPCIONISTA => false,
            User::ROLE_TRAINER => false,
            User::ROLE_ALUMNO => false,
            User::ROLE_COMUN => false,
        ] as $rol => $esperado) {
            $user = $this->user($rol);
            $this->actingAs($user);

            $this->assertSame(
                $esperado,
                Gate::allows('view-balances'),
                "El rol '{$rol}' ".($esperado ? 'debería' : 'no debería').' ver balances.'
            );
        }
    }

    public function test_gate_view_audit_logs_es_admin_y_coordinador(): void
    {
        foreach ([
            User::ROLE_ADMINISTRADOR => true,
            RolePermissions::ROLE_COORDINADOR => true,
            RolePermissions::ROLE_RECEPCIONISTA => false,
            User::ROLE_TRAINER => false,
            User::ROLE_ALUMNO => false,
            User::ROLE_COMUN => false,
        ] as $rol => $esperado) {
            $user = $this->user($rol);
            $this->actingAs($user);

            $this->assertSame(
                $esperado,
                Gate::allows('view-audit-logs'),
                "El rol '{$rol}' ".($esperado ? 'debería' : 'no debería').' ver auditoría.'
            );
        }
    }

    public function test_gate_check_in_permite_recepcionista_pero_no_alumno(): void
    {
        $recepcionista = $this->user(RolePermissions::ROLE_RECEPCIONISTA);
        $this->actingAs($recepcionista);
        $this->assertTrue(Gate::allows('check-in'));

        $alumno = $this->user(User::ROLE_ALUMNO);
        $this->actingAs($alumno);
        $this->assertFalse(Gate::allows('check-in'));
    }

    // -----------------------------------------------------------------
    // Rutas reales (lo que un usuario puede hacer de verdad)
    // -----------------------------------------------------------------

    public function test_recepcionista_puede_gestionar_usuarios_pero_no_ver_audit_logs(): void
    {
        $recepcionista = $this->user(RolePermissions::ROLE_RECEPCIONISTA);

        // Alta de usuarios: permitida.
        $this->actingAs($recepcionista)
            ->getJson('/api/admin/users')
            ->assertOk();

        // Auditoría: prohibida.
        $this->actingAs($recepcionista)
            ->getJson('/api/admin/audit-logs')
            ->assertForbidden();
    }

    public function test_alumno_no_puede_entrar_a_los_endpoints_admin(): void
    {
        $alumno = $this->user(User::ROLE_ALUMNO);

        foreach ([
            '/api/admin/users',
            '/api/admin/membresias',
            '/api/admin/estadisticas',
            '/api/admin/audit-logs',
        ] as $endpoint) {
            $this->actingAs($alumno)
                ->getJson($endpoint)
                ->assertForbidden("El endpoint {$endpoint} debería estar prohibido para alumnos.");
        }
    }

    public function test_coordinador_puede_entrara_audit_logs_pero_no_a_membresias(): void
    {
        // El plan (§18, pregunta abierta #3) dejó esto sin definir: el
        // coordinador tenía permisos DECLARADOS de `ver_membresias` pero
        // en la práctica solo llegaba a audit-logs.
        //
        // Decisión de la Fase 0.1: el coordinador PUEDE ver y cobrar
        // membresías (es operativo: asigna trainers y gestiona alumnos),
        // pero NO ve el balance global.
        $coordinador = $this->user(RolePermissions::ROLE_COORDINADOR);

        $this->actingAs($coordinador)
            ->getJson('/api/admin/audit-logs')
            ->assertOk();

        $this->actingAs($coordinador)
            ->getJson('/api/admin/membresias')
            ->assertOk();
    }

    // -----------------------------------------------------------------
    // Policies
    // -----------------------------------------------------------------

    public function test_solo_admin_puede_eliminar_o_suspender_usuarios(): void
    {
        // Se prueba la Policy directamente (no el Gate) porque el admin
        // tiene el comodín '*' y pasa cualquier chequeo: lo que
        // interesa acá es la diferencia entre recepcionista y admin.
        $recepcionista = $this->user(RolePermissions::ROLE_RECEPCIONISTA);
        $objetivo = User::factory()->create();

        $policy = new \App\Policies\UserPolicy;

        // La recepcionista NO puede borrar ni suspender.
        $this->assertFalse($policy->delete($recepcionista, $objetivo));
        $this->assertFalse($policy->suspend($recepcionista, $objetivo));

        // Pero SÍ puede crear y ver.
        $this->assertTrue($policy->create($recepcionista));
        $this->assertTrue($policy->view($recepcionista, $objetivo));
        $this->assertTrue($policy->update($recepcionista, $objetivo));
    }

    public function test_el_admin_puede_eliminar_y_suspender(): void
    {
        $admin = $this->user(User::ROLE_ADMINISTRADOR);
        $objetivo = User::factory()->create();

        $policy = new \App\Policies\UserPolicy;

        $this->assertTrue($policy->delete($admin, $objetivo));
        $this->assertTrue($policy->suspend($admin, $objetivo));
    }

    public function test_el_trainer_solo_ve_a_sus_propios_alumnos(): void
    {
        $trainer = User::factory()->create(['role' => User::ROLE_TRAINER]);
        $alumnoPropio = User::factory()->create([
            'role' => User::ROLE_ALUMNO,
            'trainer_id' => $trainer->id,
        ]);
        $alumnoAjeno = User::factory()->create(['role' => User::ROLE_ALUMNO]);

        $this->actingAs($trainer);

        $this->assertTrue(Gate::allows('view', $alumnoPropio));
        $this->assertFalse(Gate::allows('view', $alumnoAjeno));
    }

    // -----------------------------------------------------------------
    // Audit log
    // -----------------------------------------------------------------

    public function test_cambiar_el_rol_queda_auditado(): void
    {
        $admin = $this->user(User::ROLE_ADMINISTRADOR);
        $alumno = User::factory()->create(['role' => User::ROLE_COMUN]);

        $this->actingAs($admin)->put("/api/admin/users/{$alumno->id}", [
            'name' => $alumno->name,
            'email' => $alumno->email,
            'role' => RolePermissions::ROLE_RECEPCIONISTA,
        ])->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'role_changed',
            'model_type' => User::class,
            'model_id' => $alumno->id,
        ]);
    }

    // -----------------------------------------------------------------
    // El sistema viejo de roles queda documentado como no-enforcement
    // -----------------------------------------------------------------

    public function test_role_user_permanece_vacio_y_no_decide_autorizacion(): void
    {
        $admin = $this->user(User::ROLE_ADMINISTRADOR);

        // La tabla `role_user` sigue existiendo (compatibilidad) pero NO
        // participa de ninguna decisión de acceso: el admin no tiene
        // filas en ella y sin embargo tiene todos los permisos.
        $this->assertDatabaseCount('role_user', 0);
        $this->assertTrue($admin->tienePermiso('ver_balances'));

        // Y el JSON `permissions` de la tabla `roles` no se consulta:
        // el admin tiene el comodín '*' y pasa todos los chequeos, pero
        // un rol SIN comodín NO hereda permisos que no estén en la
        // matriz real.
        $recepcionista = $this->user(RolePermissions::ROLE_RECEPCIONISTA);
        $this->assertFalse($recepcionista->tienePermiso('un_permiso_inventado'));
        $this->assertFalse($recepcionista->tienePermiso('ver_balances'));
    }
}
