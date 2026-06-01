<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    public const ROLE_COMUN = 'comun';
    public const ROLE_ALUMNO = 'alumno';
    public const ROLE_TRAINER = 'trainer';
    public const ROLE_ADMINISTRADOR = 'administrador';

    protected $fillable = [
        'nick',
        'name',
        'email',
        'password',
        'role',
        'suspended',
        'trainer_id',
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
}