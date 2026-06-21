<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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

    public function hasPermission(string $permission): bool
    {
        if (in_array('*', $this->permissions ?? [])) {
            return true;
        }

        return in_array($permission, $this->permissions ?? []);
    }

    public static function seedDefaults(): void
    {
        foreach (self::defaultRoles() as $name => $data) {
            self::updateOrCreate(
                ['name' => $name],
                [
                    'description' => $data['description'],
                    'permissions' => $data['permissions'],
                ]
            );
        }
    }
}