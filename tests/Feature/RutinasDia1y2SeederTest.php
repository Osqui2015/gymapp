<?php

namespace Tests\Feature;

use App\Models\Rutina;
use Database\Seeders\RutinaDia1TorsoSeeder;
use Database\Seeders\RutinaDia2PiernaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RutinasDia1y2SeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_dia1_crea_7_ejercicios(): void
    {
        $this->seed(RutinaDia1TorsoSeeder::class);
        $this->assertSame(7, Rutina::where('dia', 'Día 1 (Torso)')->count());
    }

    public function test_seeder_dia2_crea_7_ejercicios(): void
    {
        $this->seed(RutinaDia2PiernaSeeder::class);
        $this->assertSame(7, Rutina::where('dia', 'Día 2 (Pierna)')->count());
    }

    public function test_seeder_dia1_documenta_tecnicas_especiales_en_notas(): void
    {
        $this->seed(RutinaDia1TorsoSeeder::class);

        $pressInclinado = Rutina::where('dia', 'Día 1 (Torso)')
            ->where('ejercicio_nombre', 'Press inclinado')
            ->firstOrFail();
        $this->assertStringContainsString('Rest-pause', $pressInclinado->notas);

        $remoSentado = Rutina::where('dia', 'Día 1 (Torso)')
            ->where('ejercicio_nombre', 'Remo sentado')
            ->firstOrFail();
        $this->assertStringContainsString('Rest-pause', $remoSentado->notas);

        $jalonTriceps = Rutina::where('dia', 'Día 1 (Torso)')
            ->where('ejercicio_nombre', 'Extensión de tríceps con barra recta')
            ->firstOrFail();
        $this->assertStringContainsString('Drop-set', $jalonTriceps->notas);
    }

    public function test_seeder_dia2_incluye_bloques_progresivos_y_series_al_fallo(): void
    {
        $this->seed(RutinaDia2PiernaSeeder::class);

        $sentadilla = Rutina::where('dia', 'Día 2 (Pierna)')
            ->where('ejercicio_nombre', 'Sentadilla')
            ->firstOrFail();
        $this->assertStringContainsString('2x6 RIR 1', $sentadilla->notas);
        $this->assertStringContainsString('2x4 RIR 0', $sentadilla->notas);

        $camilla = Rutina::where('dia', 'Día 2 (Pierna)')
            ->where('ejercicio_nombre', 'Curl de isquiotibiales acostado')
            ->firstOrFail();
        $this->assertStringContainsString('fallo', $camilla->notas);
    }

    public function test_seeders_son_publicos_para_catalogo(): void
    {
        $this->seed(RutinaDia1TorsoSeeder::class);
        $this->seed(RutinaDia2PiernaSeeder::class);

        $publicas = Rutina::whereIn('dia', ['Día 1 (Torso)', 'Día 2 (Pierna)'])
            ->where('publica', true)->count();
        $this->assertSame(14, $publicas);
    }

    public function test_seeders_son_idempotentes(): void
    {
        $this->seed(RutinaDia1TorsoSeeder::class);
        $this->seed(RutinaDia1TorsoSeeder::class);
        $this->seed(RutinaDia2PiernaSeeder::class);
        $this->seed(RutinaDia2PiernaSeeder::class);

        $this->assertSame(7, Rutina::where('dia', 'Día 1 (Torso)')->count());
        $this->assertSame(7, Rutina::where('dia', 'Día 2 (Pierna)')->count());
    }
}
