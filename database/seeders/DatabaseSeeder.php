<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'nick' => 'admin',
            'name' => 'Administrator',
            'email' => 'admin@example.com',
            'telefono' => '11-1234-5678',
            'password' => Hash::make('password'),
            'role' => User::ROLE_ADMINISTRADOR,
        ]);

        $trainer = User::factory()->create([
            'nick' => 'trainer1',
            'name' => 'Trainer Principal',
            'email' => 'trainer@example.com',
            'telefono' => '11-2345-6789',
            'password' => Hash::make('password'),
            'role' => User::ROLE_TRAINER,
        ]);

        User::factory()->create([
            'nick' => 'jdoe',
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'telefono' => '11-3456-7890',
            'password' => Hash::make('password'),
            'role' => User::ROLE_ALUMNO,
            'trainer_id' => $trainer->id,
        ]);

        User::factory()->create([
            'nick' => 'maria',
            'name' => 'María Pérez',
            'email' => 'maria.perez@example.com',
            'telefono' => '11-4567-8901',
            'password' => Hash::make('password'),
            'role' => User::ROLE_ALUMNO,
            'trainer_id' => $trainer->id,
        ]);

        User::factory(4)->create([
            'role' => User::ROLE_COMUN,
        ]);

        User::factory(2)->create([
            'role' => User::ROLE_ALUMNO,
            'trainer_id' => $trainer->id,
        ]);

        // Usuarios adicionales
        User::factory()->create([
            'nick' => 'pablog',
            'name' => 'Pablo Gramajo',
            'email' => 'pablo@example.com',
            'telefono' => '11-5678-9012',
            'password' => Hash::make('pablo123'),
            'role' => User::ROLE_COMUN,
        ]);

        User::factory()->create([
            'nick' => 'carlos.s',
            'name' => 'Carlos Santucho',
            'email' => 'carlos@example.com',
            'telefono' => '11-6789-0123',
            'password' => Hash::make('carlos123'),
            'role' => User::ROLE_COMUN,
        ]);

        User::factory()->create([
            'nick' => 'bruno.g',
            'name' => 'Bruno Gomez',
            'email' => 'bruno@example.com',
            'telefono' => '11-7890-1234',
            'password' => Hash::make('bruno123'),
            'role' => User::ROLE_COMUN,
        ]);
    }
}
