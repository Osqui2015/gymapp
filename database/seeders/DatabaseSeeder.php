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
            'password' => Hash::make('password'),
            'role' => User::ROLE_ADMINISTRADOR,
        ]);

        $trainer = User::factory()->create([
            'nick' => 'trainer1',
            'name' => 'Trainer Principal',
            'email' => 'trainer@example.com',
            'password' => Hash::make('password'),
            'role' => User::ROLE_TRAINER,
        ]);

        User::factory()->create([
            'nick' => 'jdoe',
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'password' => Hash::make('password'),
            'role' => User::ROLE_ALUMNO,
            'trainer_id' => $trainer->id,
        ]);

        User::factory()->create([
            'nick' => 'maria',
            'name' => 'María Pérez',
            'email' => 'maria.perez@example.com',
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
    }
}
