<?php

namespace App\Providers;

use App\Http\Requests\Auth\LoginRequest;
use App\Models\AuditLog;
use App\Models\DiarioNutricion;
use App\Models\Ejercicio;
use App\Models\EjercicioClave;
use App\Models\GymConfig;
use App\Models\Historial;
use App\Models\MedallaUsuario;
use App\Models\Membresia;
use App\Models\Message;
use App\Models\Meta;
use App\Models\Progreso;
use App\Models\Rutina;
use App\Models\User;
use App\Observers\GymConfigObserver;
use App\Observers\RutinaObserver;
use App\Observers\UserObserver;
use App\Policies\AuditLogPolicy;
use App\Policies\DiarioNutricionPolicy;
use App\Policies\EjercicioClavePolicy;
use App\Policies\EjercicioPolicy;
use App\Policies\HistorialPolicy;
use App\Policies\MedallaPolicy;
use App\Policies\MembresiaPolicy;
use App\Policies\MessagePolicy;
use App\Policies\MetaPolicy;
use App\Policies\ProgresoPolicy;
use App\Policies\RutinaPolicy;
use App\Policies\UserPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // === Dev safety: ver notas en commit previo ===
        Model::shouldBeStrict(! app()->isProduction());

        // === Observers para audit log automático ===
        Rutina::observe(RutinaObserver::class);
        User::observe(UserObserver::class);

        // Config del gym: invalida el cache y audita cada cambio (Fase 0.4).
        GymConfig::observe(GymConfigObserver::class);

        Vite::usePreloadTagAttributes(function ($src, string $url): array|false {
            return str_ends_with($url, '.css') ? [] : false;
        });

        $this->registerGates();
        $this->registerPolicies();
        $this->registerRateLimiters();
    }

    /**
     * Limiters de tasa (rate limiting).
     *
     * La app NO tenia ningun limite. Las 148 rutas de routes/api.php tienen
     * el grupo de middleware `api`, pero Laravel 12 solo agrega
     * `throttle:api` a ese grupo si existe un limiter llamado `api`
     * (ver bootstrap/app.php). Sin el, no habia nada que frenara:
     * - fuerza bruta de nick+password contra el login
     * - saturacion de los endpoints caros (catalogo VisualGym, export, stats)
     *
     * Limites:
     * - `api`      300/min por usuario autenticado, 120/min por IP anonima.
     * - `login`    10/min por nick+IP.
     * - `register` 5/min por IP.
     *
     * El "olvide mi contrasena" NO va aca: PasswordResetLinkController ya
     * tiene su propio limite (3 por email+IP cada 10 min) que ademas limpia
     * el contador al enviar el link con exito. Un middleware lo preemptaria.
     */
    protected function registerRateLimiters(): void
    {
        $tooMany = fn (string $message) => fn () => response()->json([
            'message' => $message,
            'retry_after' => 60,
        ], 429);

        RateLimiter::for('api', function (Request $request) use ($tooMany) {
            $user = $request->user();

            if ($user) {
                return Limit::perMinute(300)
                    ->by('u'.$user->id)
                    ->response($tooMany('Demasiadas peticiones. Probá en un momento.'));
            }

            return Limit::perMinute(120)
                ->by('ip'.$request->ip())
                ->response($tooMany('Demasiadas peticiones. Probá en un momento.'));
        });

        RateLimiter::for('login', function (Request $request) use ($tooMany) {
            // Misma clave y mismo límite que `LoginRequest::throttleKey()` /
            // `LoginRequest::MAX_INTENTOS`: los dos limitadores cuentan
            // sobre el mismo contador y no se contradicen.
            $nick = strtolower(trim((string) $request->input('nick')));

            return Limit::perMinute(LoginRequest::MAX_INTENTOS)
                ->by($nick.'|'.$request->ip())
                ->response($tooMany('Demasiados intentos de login. Esperá un minuto.'));
        });

        RateLimiter::for('register', function (Request $request) use ($tooMany) {
            return Limit::perMinute(5)
                ->by($request->ip())
                ->response($tooMany('Demasiados registros desde esta IP. Esperá un minuto.'));
        });
    }

    /**
     * Gates de autorización reutilizables.
     *
     * Hoy la autorización se hace principalmente vía el middleware `role:...`
     * (registrado en bootstrap/app.php → EnsureUserHasRole). Los Gates son
     * una capa complementaria: mejores para reglas condicionales
     * ("¿puede ESTE user ver ESTE recurso?") y para usar con `@can` en Blade
     * y `$user->can(...)` en código.
     *
     * Para USARLOS en una ruta:
     *   Route::get('/admin/users', ...)->middleware('can:manage-users');
     *
     * Para USARLOS en un controller:
     *   $this->authorize('manage-users');
     *
     * Para USARLOS en Blade:
     *   `@can('manage-users')` ... `@endcan`
     *
     * Los Gates específicos de cada modelo (RutinaPolicy, HistorialPolicy, ...)
     * se deberían definir en archivos `app/Policies/<Model>Policy.php` y
     * registrar con `Gate::policy(Rutina::class, RutinaPolicy::class)`.
     */
    protected function registerGates(): void
    {
        // === Gates por capacidad (derivados de RolePermissions) ===
        //
        // La matriz de permisos vive en `App\Support\RolePermissions`
        // (Fase 0.1). Estos gates son la forma idiomática de Laravel de
        // exponerla: `@can('ver_balances')`, `->can('...')`, etc.
        //
        // Los gates por ROL que existían antes (admin, staff,
        // trainer-or-admin) se mantienen porque las policies y el
        // middleware los referencian por nombre.

        Gate::define('admin', function (User $user) {
            return $user->hasRole(User::ROLE_ADMINISTRADOR);
        });

        // Staff = admin, trainer, recepcionista o coordinador
        // (mismo set que `isStaff` en el store de Pinia)
        Gate::define('staff', function (User $user) {
            return $user->hasRole(User::ROLES_STAFF);
        });

        // Admin o trainer (los dos roles que pueden asignar rutinas a alumnos)
        Gate::define('trainer-or-admin', function (User $user) {
            return $user->hasRole([
                User::ROLE_ADMINISTRADOR,
                User::ROLE_TRAINER,
            ]);
        });

        // === Gates de capacidad ===
        //
        // Cada uno consulta la matriz de RolePermissions, que es la fuente
        // única de verdad. Antes estos gates tenían el set de roles
        // hardcodeado acá, y el set declared en `Role.permissions` (que
        // nunca se aplicaba) podía contradecirlo sin que nadie lo notara.

        // Puede gestionar usuarios: admin, coordinador, recepcionista
        Gate::define('manage-users', function (User $user) {
            return $user->tienePermiso('gestionar_usuarios');
        });

        // Puede ver audit logs: admin y coordinador
        Gate::define('view-audit-logs', function (User $user) {
            return $user->tienePermiso('ver_audit_logs');
        });

        // Puede ver estadísticas globales: admin y coordinador
        Gate::define('view-global-stats', function (User $user) {
            return $user->tienePermiso('ver_estadisticas');
        });

        // Puede ver reportes (retención, churn): admin y coordinador
        Gate::define('view-reportes', function (User $user) {
            return $user->tienePermiso('ver_reportes');
        });

        // Puede ver el BALANCE global (MRR, ingresos): solo admin.
        // Esta separación es deliberada: `recepcionista` cobra membresías
        // pero no ve el balance global del gym.
        Gate::define('view-balances', function (User $user) {
            return $user->tienePermiso('ver_balances');
        });

        // Puede ver/gestionar membresías: admin, coordinador, recepcionista
        Gate::define('view-membresias', function (User $user) {
            return $user->tienePermiso('ver_membresias');
        });

        // Puede registrar pagos: admin, coordinador, recepcionista
        Gate::define('register-pagos', function (User $user) {
            return $user->tienePermiso('registrar_pagos');
        });

        // Puede importar/exportar data: solo admin
        Gate::define('manage-import-export', function (User $user) {
            return $user->tienePermiso('importar_exportar');
        });

        // Puede asignar rutinas a alumnos: admin, trainer
        // (los trainers solo a sus alumnos — eso requiere lógica adicional
        // del modelo, ver UserRutinaController::asignarRutina).
        Gate::define('assign-routines', function (User $user) {
            return $user->tienePermiso('crear_rutinas') && $user->tienePermiso('gestionar_alumnos');
        });

        // Puede operar el check-in de recepción
        Gate::define('check-in', function (User $user) {
            return $user->tienePermiso('check_in');
        });

        // Puede editar la configuración del gym
        Gate::define('manage-configuracion', function (User $user) {
            return $user->tienePermiso('gestionar_configuracion');
        });

        // Puede ver info de un alumno específico: admin, trainer (cualquiera)
        // o el mismo alumno. La validación "el trainer solo ve SUS alumnos"
        // se hace en el controller con una query específica.
        Gate::define('view-alumno', function (User $user, $alumnoId) {
            if ($user->tienePermiso('ver_alumnos')) {
                return true;
            }
            if ((int) $alumnoId === (int) $user->id) {
                return true;
            }

            return false;
        });
    }

    /**
     * Registro de policies (autorización específica por instancia).
     *
     * Cada vez que el código llama `$user->can('view', $model)` o
     * `$this->authorize('update', $model)`, Laravel busca aquí la policy
     * correspondiente al modelo y le pasa el modelo como argumento.
     *
     * Las policies se usan en lugar de (o además de) los Gates genéricos
     * definidos en registerGates() cuando la regla depende del recurso
     * concreto (por ejemplo: "puede ver ESTE historial", no solo "es trainer").
     */
    protected function registerPolicies(): void
    {
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Ejercicio::class, EjercicioPolicy::class);
        Gate::policy(EjercicioClave::class, EjercicioClavePolicy::class);
        Gate::policy(Meta::class, MetaPolicy::class);
        Gate::policy(MedallaUsuario::class, MedallaPolicy::class);
        Gate::policy(Membresia::class, MembresiaPolicy::class);
        Gate::policy(Progreso::class, ProgresoPolicy::class);
        Gate::policy(DiarioNutricion::class, DiarioNutricionPolicy::class);
        Gate::policy(AuditLog::class, AuditLogPolicy::class);

        // Estas tres FUNCIONABAN por autodescubrimiento de Laravel, pero
        // depender del autodescubrimiento es frágil: si alguien renombra
        // el modelo o la policy, la autorización deja de aplicarse EN
        // SILENCIO (no da error, simplemente deja de proteger).
        // Registrarlas explícitamente las hace visibles y falla ruidoso.
        Gate::policy(Rutina::class, RutinaPolicy::class);
        Gate::policy(Historial::class, HistorialPolicy::class);
        Gate::policy(Message::class, MessagePolicy::class);
    }
}
