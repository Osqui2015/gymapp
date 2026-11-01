<?php

namespace Tests\Feature;

use App\Models\Progreso;
use App\Models\User;
use App\Services\TdeeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tanda 3 / T3-C — tests unitarios de lógica pura: TdeeService.
 *
 * El cálculo de TDEE (Mifflin-St Jeor) y la distribución de macros alimentan
 * la pantalla de DiarioNutricion. Si la fórmula o los redondeos se rompen,
 * el usuario recibe una recomendación de calorías/gramos equivocada.
 *
 * Estos tests son UNIDAD: no tocan HTTP, solo el servicio.
 *
 * Fórmula (Mifflin-St Jeor 1990):
 *   Hombres: BMR = 10·peso(kg) + 6.25·altura(cm) - 5·edad + 5
 *   Mujeres: BMR = 10·peso(kg) + 6.25·altura(cm) - 5·edad - 161
 *   TDEE = BMR × factor_actividad
 */
class TdeeServiceTest extends TestCase
{
    use RefreshDatabase;

    private TdeeService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TdeeService;
    }

    // ---------------------------------------------------------------- BMR

    public function test_bmr_masculino_usa_la_formula_de_mifflin_st_jeor(): void
    {
        // 10×80 + 6.25×180 − 5×30 + 5 = 800 + 1125 − 150 + 5 = 1780
        $bmr = $this->service->calcBmr('masculino', 80.0, 180.0, 30);

        $this->assertEqualsWithDelta(1780.0, $bmr, 0.001);
    }

    public function test_bmr_femenino_usa_la_constante_menos_161(): void
    {
        // 10×60 + 6.25×165 − 5×28 − 161 = 600 + 1031.25 − 140 − 161 = 1330.25
        $bmr = $this->service->calcBmr('femenino', 60.0, 165.0, 28);

        $this->assertEqualsWithDelta(1330.25, $bmr, 0.001);
    }

    public function test_cualquier_sexo_distinto_de_masculino_usa_la_constante_de_mujeres(): void
    {
        $masculino = $this->service->calcBmr('masculino', 70.0, 175.0, 30);
        $otro = $this->service->calcBmr('otro', 70.0, 175.0, 30);

        $this->assertEqualsWithDelta($masculino - 166, $otro, 0.001);
    }

    // --------------------------------------------------------------- TDEE

    public function test_tdee_multiplica_el_bmr_por_el_factor_de_actividad(): void
    {
        // 1780 × 1.55 (moderado) = 2759
        $tdee = $this->service->calcTdee(1780.0, 'moderado');

        $this->assertEqualsWithDelta(2759.0, $tdee, 0.001);
    }

    public function test_tdee_usa_todos_los_factores_de_actividad_declarados(): void
    {
        foreach (TdeeService::FACTORES_ACTIVIDAD as $nivel => $factor) {
            $this->assertEqualsWithDelta(
                2000.0 * $factor,
                $this->service->calcTdee(2000.0, $nivel),
                0.001,
                "El factor de actividad '{$nivel}' no se aplica bien"
            );
        }
    }

    public function test_tdee_con_actividad_desconocida_cae_a_sedentario(): void
    {
        $this->assertEqualsWithDelta(
            2000.0 * TdeeService::FACTORES_ACTIVIDAD['sedentario'],
            $this->service->calcTdee(2000.0, 'nivel_inventado'),
            0.001
        );
    }

    public function test_los_factores_de_actividad_estan_ordenados_de_menor_a_mayor(): void
    {
        $factores = array_values(TdeeService::FACTORES_ACTIVIDAD);
        $ordenados = $factores;
        sort($ordenados);

        $this->assertSame($ordenados, $factores, 'Los factores de actividad deben ir de menor a mayor');
    }

    // ------------------------------------------------- Calorías por objetivo

    public function test_mantener_no_ajusta_las_calorias(): void
    {
        $this->assertSame(2759, $this->service->calcCaloriasTarget(2759.0, 'mantener'));
    }

    public function test_perder_grasa_aplica_un_deficit_del_20_por_ciento(): void
    {
        // 2759 × 0.8 = 2207.2 → 2207
        $this->assertSame(2207, $this->service->calcCaloriasTarget(2759.0, 'perder_grasa'));
    }

    public function test_ganar_masa_aplica_un_superavit_del_15_por_ciento(): void
    {
        // 2759 × 1.15 = 3172.85 → 3173
        $this->assertSame(3173, $this->service->calcCaloriasTarget(2759.0, 'ganar_masa'));
    }

    public function test_un_objetivo_desconocido_cae_a_mantener(): void
    {
        $this->assertSame(
            $this->service->calcCaloriasTarget(2759.0, 'mantener'),
            $this->service->calcCaloriasTarget(2759.0, 'objetivo_inventado')
        );
    }

    // ------------------------------------------------------------- Macros

    public function test_las_calorias_de_los_macros_suman_las_calorias_target(): void
    {
        $calorias = 2759;
        $macros = $this->service->calcMacros($calorias, 80.0, 'mantener');

        $kcal = $macros['proteinas'] * TdeeService::KCAL_POR_GRANO['proteinas']
            + $macros['grasas'] * TdeeService::KCAL_POR_GRANO['grasas']
            + $macros['carbohidratos'] * TdeeService::KCAL_POR_GRANO['carbohidratos'];

        // Los macros se redondean a gramos enteros: la diferencia con las
        // calorías target puede ser de unos pocos kcal, no de cientos.
        $this->assertEqualsWithDelta($calorias, $kcal, 20);
    }

    public function test_la_proteina_se_calcula_en_gramos_por_kilo_de_peso(): void
    {
        // Mantener: 1.6 g/kg → 80 kg = 128 g
        $macros = $this->service->calcMacros(2759, 80.0, 'mantener');
        $this->assertSame(128, $macros['proteinas']);

        // Perder grasa: 2.0 g/kg → 80 kg = 160 g
        $macros = $this->service->calcMacros(2207, 80.0, 'perder_grasa');
        $this->assertSame(160, $macros['proteinas']);

        // Ganar masa: 1.8 g/kg → 80 kg = 144 g
        $macros = $this->service->calcMacros(3173, 80.0, 'ganar_masa');
        $this->assertSame(144, $macros['proteinas']);
    }

    public function test_la_grasa_es_un_porcentaje_de_las_calorias_target(): void
    {
        // Mantener: 30% de 2759 = 827.7 kcal → /9 = 92 g
        $macros = $this->service->calcMacros(2759, 80.0, 'mantener');
        $this->assertSame(92, $macros['grasas']);

        // Perder grasa: 25% de 2207 = 551.75 → /9 = 61 g
        $macros = $this->service->calcMacros(2207, 80.0, 'perder_grasa');
        $this->assertSame(61, $macros['grasas']);
    }

    public function test_los_carbohidratos_absorben_lo_que_queda(): void
    {
        $macros = $this->service->calcMacros(2759, 80.0, 'mantener');

        $esperado = (int) round((2759 - 128 * 4 - 92 * 9) / 4);
        $this->assertSame($esperado, $macros['carbohidratos']);
    }

    public function test_los_carbohidratos_nunca_son_negativos(): void
    {
        // Un objetivo imposible (muy pocas calorías, mucho peso) no debe
        // producir carbs negativos.
        $macros = $this->service->calcMacros(500, 200.0, 'ganar_masa');

        $this->assertGreaterThanOrEqual(0, $macros['carbohidratos']);
    }

    // --------------------------------------------------- Inputs y reporte

    public function test_inputs_completos_requiere_todos_los_campos(): void
    {
        $completo = [
            'sexo' => 'masculino',
            'edad' => 30,
            'peso' => 80.0,
            'altura' => 180.0,
            'nivel_actividad' => 'moderado',
        ];

        $this->assertTrue($this->service->inputsCompletos($completo));

        foreach (array_keys($completo) as $campo) {
            $incompleto = $completo;
            $incompleto[$campo] = null;

            $this->assertFalse(
                $this->service->inputsCompletos($incompleto),
                "Sin '{$campo}' los inputs NO deberían estar completos"
            );
        }
    }

    public function test_calcular_devuelve_el_reporte_completo_con_inputs_validos(): void
    {
        $user = User::factory()->create([
            'sexo' => 'masculino',
            'edad' => 30,
            'altura' => 180,
            'nivel_actividad' => 'moderado',
            'objetivo_nutricional' => 'mantener',
        ]);

        Progreso::create([
            'user_id' => $user->id,
            'peso' => 80.0,
            'altura' => 180.0,
            'fecha' => now()->toDateString(),
        ]);

        $reporte = $this->service->calcular($user);

        $this->assertTrue($reporte['inputs_completos']);
        $this->assertSame([], $reporte['faltantes']);
        $this->assertEqualsWithDelta(1780.0, $reporte['bmr'], 1.0);
        $this->assertEqualsWithDelta(2759.0, $reporte['tdee'], 1.0);
        $this->assertSame(2759, $reporte['calorias_target']);
        $this->assertIsArray($reporte['macros']);
        $this->assertArrayHasKey('proteinas', $reporte['macros']);
        $this->assertArrayHasKey('grasas', $reporte['macros']);
        $this->assertArrayHasKey('carbohidratos', $reporte['macros']);
        $this->assertSame('Mifflin-St Jeor', $reporte['explicacion']['formula']);
    }

    public function test_calcular_usa_el_ultimo_progreso_registrado(): void
    {
        $user = User::factory()->create([
            'sexo' => 'masculino',
            'edad' => 30,
            'altura' => 180,
            'nivel_actividad' => 'moderado',
            'objetivo_nutricional' => 'mantener',
        ]);

        // Progreso viejo: 90 kg. Progreso nuevo: 80 kg.
        Progreso::create([
            'user_id' => $user->id,
            'peso' => 90.0,
            'altura' => 180.0,
            'fecha' => now()->subMonths(2)->toDateString(),
        ]);
        Progreso::create([
            'user_id' => $user->id,
            'peso' => 80.0,
            'altura' => 180.0,
            'fecha' => now()->toDateString(),
        ]);

        $reporte = $this->service->calcular($user);

        $this->assertEquals(80.0, $reporte['inputs']['peso']);
        // 10×80 + 6.25×180 − 5×30 + 5 = 1780 (no 1950, que sería con 90 kg).
        $this->assertEqualsWithDelta(1780.0, $reporte['bmr'], 1.0);
    }

    public function test_calcular_avisa_los_campos_que_faltan_y_no_calcula(): void
    {
        $user = User::factory()->create([
            'sexo' => null,
            'edad' => null,
            'altura' => null,
            'nivel_actividad' => null,
            'objetivo_nutricional' => 'mantener',
        ]);

        $reporte = $this->service->calcular($user);

        $this->assertFalse($reporte['inputs_completos']);
        $this->assertContains('sexo', $reporte['faltantes']);
        $this->assertContains('edad', $reporte['faltantes']);
        $this->assertContains('peso', $reporte['faltantes']);
        $this->assertContains('altura', $reporte['faltantes']);
        $this->assertContains('nivel_actividad', $reporte['faltantes']);

        $this->assertNull($reporte['bmr']);
        $this->assertNull($reporte['tdee']);
        $this->assertNull($reporte['calorias_target']);
        $this->assertNull($reporte['macros']);
    }

    public function test_el_objetivo_por_defecto_de_la_base_es_mantener(): void
    {
        // La columna `users.objetivo_nutricional` es NOT NULL con
        // default 'mantener'. El `?? 'mantener'` del servicio es la red de
        // seguridad para datos viejos, no el camino normal.
        $user = User::factory()->create([
            'sexo' => 'masculino',
            'edad' => 30,
            'altura' => 180,
            'nivel_actividad' => 'moderado',
        ]);

        $this->assertSame('mantener', $user->fresh()->objetivo_nutricional);

        Progreso::create([
            'user_id' => $user->id,
            'peso' => 80.0,
            'altura' => 180.0,
            'fecha' => now()->toDateString(),
        ]);

        $reporte = $this->service->calcular($user);

        $this->assertSame('mantener', $reporte['inputs']['objetivo_nutricional']);
        $this->assertSame(2759, $reporte['calorias_target']);
    }
}
