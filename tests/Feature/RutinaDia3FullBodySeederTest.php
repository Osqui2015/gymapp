<?php

namespace Tests\Feature;

use App\Models\Ejercicio;
use App\Models\Rutina;
use Database\Seeders\RutinaDia3FullBodySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RutinaDia3FullBodySeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_crea_8_ejercicios_en_dia_3_full_body(): void
    {
        // Sin la biblioteca de ejercicios poblada, el seeder corre igual
        // (las rutinas se crean con ejercicio_nombre y ejercicio_id=null).
        $this->seed(RutinaDia3FullBodySeeder::class);

        $this->assertSame(8, Rutina::where('dia', 'Día 3 (Full Body)')->count());
    }

    public function test_seeder_incluye_las_dos_superseries_correctas(): void
    {
        $this->seed(RutinaDia3FullBodySeeder::class);

        // Superserie 1: press banca + apertura polea (orden 1 y 2)
        $ss1 = Rutina::where('superserie_grupo', 1)->orderBy('orden')->get();
        $this->assertCount(2, $ss1);
        $this->assertSame(1, $ss1[0]->orden);
        $this->assertSame(2, $ss1[1]->orden);

        // Superserie 2: biceps + triceps polea (orden 5 y 6)
        $ss2 = Rutina::where('superserie_grupo', 2)->orderBy('orden')->get();
        $this->assertCount(2, $ss2);
        $this->assertSame(5, $ss2[0]->orden);
        $this->assertSame(6, $ss2[1]->orden);
    }

    public function test_seeder_es_idempotente(): void
    {
        $this->seed(RutinaDia3FullBodySeeder::class);
        $this->seed(RutinaDia3FullBodySeeder::class);
        $this->assertSame(8, Rutina::where('dia', 'Día 3 (Full Body)')->count());
    }

    public function test_seeder_incluye_bloques_progresivos_en_notas(): void
    {
        $this->seed(RutinaDia3FullBodySeeder::class);

        // El press banca debe tener el bloque progresivo en notas
        $press = Rutina::where('dia', 'Día 3 (Full Body)')
            ->where('ejercicio_nombre', 'Press de banca')
            ->firstOrFail();
        $this->assertStringContainsString('2x12 RIR 2', $press->notas);
        $this->assertStringContainsString('2x10 RIR 1', $press->notas);
        $this->assertStringContainsString('Superserie', $press->notas);
    }

    public function test_rutinas_son_publicas_para_catalogo_comunitario(): void
    {
        $this->seed(RutinaDia3FullBodySeeder::class);

        $publicas = Rutina::where('dia', 'Día 3 (Full Body)')->where('publica', true)->count();
        $this->assertSame(8, $publicas);
    }

    public function test_seeder_resetea_si_se_corre_de_nuevo_con_datos_existentes(): void
    {
        // Insertar una rutina "ruido" con el mismo dia
        Rutina::create([
            'nivel' => 'Intermedio', 'modalidad' => '3 Días', 'dia' => 'Día 3 (Full Body)',
            'series' => 1, 'reps_min' => '1', 'reps_max' => '1', 'descanso_min' => 1,
            'ejercicio_nombre' => 'Ruido', 'orden' => 99, 'publica' => false,
        ]);
        $this->assertSame(1, Rutina::where('dia', 'Día 3 (Full Body)')->count());

        $this->seed(RutinaDia3FullBodySeeder::class);

        // El ruido se borro, quedaron solo los 8 del seeder
        $this->assertSame(8, Rutina::where('dia', 'Día 3 (Full Body)')->count());
        $this->assertNull(Rutina::where('ejercicio_nombre', 'Ruido')->first());
    }
}
