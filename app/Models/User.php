<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Support\RolePermissions;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLE_COMUN = 'comun';

    public const ROLE_ALUMNO = 'alumno';

    public const ROLE_TRAINER = 'trainer';

    public const ROLE_ADMINISTRADOR = 'administrador';

    public const ROLE_COORDINADOR = 'coordinador';

    public const ROLE_RECEPCIONISTA = 'recepcionista';

    /**
     * Roles que son staff del gym (tienen acceso a alguna función
     * administrativa). `comun` y `alumno` no están.
     */
    public const ROLES_STAFF = [
        self::ROLE_ADMINISTRADOR,
        self::ROLE_TRAINER,
        self::ROLE_COORDINADOR,
        self::ROLE_RECEPCIONISTA,
    ];

    protected $fillable = [
        'nick',
        'name',
        'email',
        'telefono',
        'password',
        'role',
        'suspended',
        'motivo_suspension',
        'trainer_id',
        'altura',
        'peso_objetivo',
        // === Nutrición (TDEE + macros) ===
        'sexo',
        'edad',
        'nivel_actividad',
        'objetivo_nutricional',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'suspended' => 'boolean',
            'trainer_id' => 'integer',
            'edad' => 'integer',
        ];
    }

    public function hasRole(string|array $roles): bool
    {
        $roles = (array) $roles;
        $actual = $this->normalizedRole();

        foreach ($roles as $role) {
            if ($actual === $this->normalizeRoleName($role)) {
                return true;
            }
        }

        return false;
    }

    public function hasAnyRole(string|array $roles): bool
    {
        return $this->hasRole($roles);
    }

    /**
     * ¿El usuario tiene este permiso?
     *
     * Delega en `RolePermissions`, que es la fuente única de verdad de la
     * matriz de permisos por rol (Fase 0.1 / plan §12.1).
     *
     * @see \App\Support\RolePermissions
     */
    public function tienePermiso(string $permiso): bool
    {
        return RolePermissions::tiene($this, $permiso);
    }

    /**
     * ¿El usuario tiene alguno de estos permisos?
     *
     * @param  array<int, string>  $permisos
     */
    public function tieneAlgunoPermiso(array $permisos): bool
    {
        return RolePermissions::tieneAlguno($this, $permisos);
    }

    /**
     * ¿El usuario es staff del gym (admin, trainer, coordinador o recepcionista)?
     */
    public function isStaff(): bool
    {
        return $this->hasRole(self::ROLES_STAFF);
    }

    public function normalizedRole(): string
    {
        return $this->normalizeRoleName($this->role ?? self::ROLE_COMUN);
    }

    private function normalizeRoleName(string $role): string
    {
        return match ($role) {
            'user' => self::ROLE_COMUN,
            'admin' => self::ROLE_ADMINISTRADOR,
            default => $role,
        };
    }

    public function rutinaSeleccionada(): HasOne
    {
        return $this->hasOne(UserRutina::class);
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(self::class, 'trainer_id');
    }

    public function alumnos(): HasMany
    {
        return $this->hasMany(self::class, 'trainer_id');
    }

    public function rutinasAsignadas(): HasMany
    {
        return $this->hasMany(UserRutina::class, 'assigned_by');
    }

    public function historials(): HasMany
    {
        return $this->hasMany(Historial::class);
    }

    /**
     * Relación polimórfica al sistema de notificaciones in-app.
     * Permite `$user->notifications` y helpers como `notifiedToday()`.
     */
    public function notifications(): MorphMany
    {
        return $this->morphMany(Notification::class, 'notifiable')
            ->latest('created_at');
    }

    public function membresia(): HasOne
    {
        return $this->hasOne(Membresia::class);
    }

    public function membresias(): HasMany
    {
        return $this->hasMany(Membresia::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }

    /**
     * Membresía activa del usuario.
     *
     * Incluye las membresías de CORTESÍA (sin vencimiento): nunca
     * vencen, así que siempre están vigentes.
     *
     * La cortesía se identifica por `metodo_pago = 'cortesia'`, NO por
     * `fecha_vencimiento IS NULL`: esa columna nullable también la
     * tienen los trials mal configurados, y tratarlos como cortesía
     * abriría el acceso sin que nadie haya pagado.
     */
    public function getMembresiaActiva(): ?Membresia
    {
        /** @var Membresia|null $activa */
        $activa = $this->membresias()
            ->whereIn('estado', ['activo', 'por_vencer'])
            ->where(function ($q) {
                $q->where('metodo_pago', Membresia::METODO_PAGO_CORTESIA)
                    ->orWhere('fecha_vencimiento', '>=', now()->toDateString());
            })
            ->first();

        return $activa;
    }

    public function getEstaVencido(): bool
    {
        $membresia = $this->getMembresiaActiva();

        return $membresia === null && $this->membresias()->exists();
    }

    /**
     * ¿El usuario puede acceder al área de entrenamiento?
     *
     * Espejo de la regla del middleware `CheckMembership` (Fase 0.6).
     */
    public function getPuedoAcceder(): bool
    {
        // Administradores siempre pueden acceder
        if ($this->hasRole([self::ROLE_ADMINISTRADOR])) {
            return true;
        }

        // Trainers pueden acceder sin membresía formal
        if ($this->hasRole([self::ROLE_TRAINER])) {
            return true;
        }

        // Verificar membresía activa (incluye cortesía y trials)
        return $this->getMembresiaActiva() !== null;
    }
}
