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
        // Sembrar roles por defecto (idempotente)
        \App\Models\Role::seedDefaults();

        // Sembrar la biblioteca de ejercicios y las rutinas ANTES de los
        // usuarios. El historial que se inserta más abajo referencia
        // ejercicios por nombre; las migraciones de backfill (rutinas y
        // historials) matchean por nombre y requieren que los ejercicios
        // ya existan.
        $this->call([
            EjercicioSeeder::class,
            RutinaSeeder::class,
            RutinaDia1TorsoSeeder::class,    // catalogo comunitario: DIA 1 TORSO
            RutinaDia2PiernaSeeder::class,   // catalogo comunitario: DIA 2 PIERNA
            RutinaDia3FullBodySeeder::class, // catalogo comunitario: DIA 3 FULL BODY
        ]);

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

        $maria = User::factory()->create([
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

        User::factory()->create([
            'nick' => 'osqui',
            'name' => 'Oscar Guerrero',
            'email' => 'osqui@example.com',
            'telefono' => '11-7890-1234',
            'password' => Hash::make('oscar123'),
            'role' => User::ROLE_COMUN,
        ]);

        // Seed historical workout entries for maria
        // IMPORTANTE: los nombres de ejercicio deben matchear EXACTAMENTE con
        // los del EjercicioSeeder, sino el backfill del FK falla. Si la
        // biblioteca cambia, actualizar acá.
        $mariaWorkouts = [
            [
                'fecha' => '2026-05-15',
                'ejercicio' => 'Sentadilla',
                'sets' => [
                    ['peso' => 60, 'reps' => 8],
                    ['peso' => 60, 'reps' => 8],
                    ['peso' => 60, 'reps' => 8],
                ]
            ],
            [
                'fecha' => '2026-05-22',
                'ejercicio' => 'Sentadilla',
                'sets' => [
                    ['peso' => 65, 'reps' => 8],
                    ['peso' => 65, 'reps' => 8],
                    ['peso' => 65, 'reps' => 8],
                ]
            ],
            [
                'fecha' => '2026-05-29',
                'ejercicio' => 'Sentadilla',
                'sets' => [
                    ['peso' => 70, 'reps' => 6],
                    ['peso' => 70, 'reps' => 6],
                    ['peso' => 70, 'reps' => 6],
                ]
            ],
            [
                'fecha' => '2026-06-03',
                'ejercicio' => 'Sentadilla',
                'sets' => [
                    ['peso' => 75, 'reps' => 5],
                    ['peso' => 75, 'reps' => 5],
                    ['peso' => 75, 'reps' => 5],
                ]
            ],
            // Prensa
            [
                'fecha' => '2026-05-15',
                'ejercicio' => 'Prensa de piernas',
                'sets' => [
                    ['peso' => 120, 'reps' => 10],
                    ['peso' => 120, 'reps' => 10],
                ]
            ],
            [
                'fecha' => '2026-05-22',
                'ejercicio' => 'Prensa de piernas',
                'sets' => [
                    ['peso' => 130, 'reps' => 10],
                    ['peso' => 130, 'reps' => 10],
                ]
            ],
            [
                'fecha' => '2026-05-29',
                'ejercicio' => 'Prensa de piernas',
                'sets' => [
                    ['peso' => 140, 'reps' => 8],
                    ['peso' => 140, 'reps' => 8],
                ]
            ],
            [
                'fecha' => '2026-06-03',
                'ejercicio' => 'Prensa de piernas',
                'sets' => [
                    ['peso' => 150, 'reps' => 8],
                    ['peso' => 150, 'reps' => 8],
                ]
            ],
        ];

        foreach ($mariaWorkouts as $wGroup) {
            foreach ($wGroup['sets'] as $index => $set) {
                \App\Models\Historial::create([
                    'user_id' => $maria->id,
                    'rutina_nombre' => 'Fuerza Piernas',
                    'dia' => 'Día 1',
                    'ejercicio_nombre' => $wGroup['ejercicio'],
                    'series_numero' => $index + 1,
                    'series_completadas' => 1,
                    'reps_min' => '5',
                    'reps_max' => '10',
                    'reps_realizadas' => $set['reps'],
                    'descanso_min' => 2.5,
                    'peso' => $set['peso'],
                    'completado' => true,
                    'fecha' => $wGroup['fecha'],
                ]);
            }
        }

        // Seed some Ejercicios Clave
        \App\Models\EjercicioClave::create([
            'user_id' => $maria->id,
            'trainer_id' => $trainer->id,
            'ejercicio_nombre' => 'Sentadilla',
            'notas_trainer' => "Foco en romper el paralelo y mantener la espalda neutra.\nProgresar peso solo si mantienes buena técnica.",
        ]);

        \App\Models\EjercicioClave::create([
            'user_id' => $maria->id,
            'trainer_id' => $trainer->id,
            'ejercicio_nombre' => 'Prensa de piernas',
            'notas_trainer' => 'Evitar bloquear las rodillas en la extensión máxima. Empujar con los talones.',
        ]);

        // === Backfill de FKs (ejercicio_id) ===
        // Las migraciones originales hacían el backfill en su up(), pero las
        // migraciones corren ANTES que los seeders, así que la tabla
        // `ejercicios` estaba vacía y nada matcheaba. Por eso movemos el
        // backfill al final del seeder, donde los ejercicios ya existen.
        // Es idempotente: solo actualiza filas con FK null.
        $this->backfillEjercicioId();
    }

    /**
     * Backfill de rutinas.ejercicio_id y historials.ejercicio_id por match
     * de nombre. Idempotente: solo actualiza filas con ejercicio_id NULL.
     */
    private function backfillEjercicioId(): void
    {
        $ejercicios = \App\Models\Ejercicio::pluck('id', 'nombre');

        $totalRutinas = 0;
        foreach ($ejercicios as $nombre => $id) {
            $totalRutinas += \App\Models\Rutina::where('ejercicio_nombre', $nombre)
                ->whereNull('ejercicio_id')
                ->update(['ejercicio_id' => $id]);
        }

        $totalHist = 0;
        foreach ($ejercicios as $nombre => $id) {
            $totalHist += \App\Models\Historial::where('ejercicio_nombre', $nombre)
                ->whereNull('ejercicio_id')
                ->update(['ejercicio_id' => $id]);
        }

        $unmatchedRutinas = \App\Models\Rutina::whereNull('ejercicio_id')->count();
        $unmatchedHist = \App\Models\Historial::whereNull('ejercicio_id')->count();

        if ($unmatchedRutinas > 0 || $unmatchedHist > 0) {
            $this->command->warn(
                "Backfill parcial: $unmatchedRutinas rutinas y $unmatchedHist historiales sin match. " .
                'Probable causa: nombre de ejercicio no existe en la biblioteca.'
            );
        } else {
            $this->command->info(
                "Backfill OK: $totalRutinas rutinas y $totalHist historiales con ejercicio_id seteado."
            );
        }
    }
}
