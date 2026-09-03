<?php

namespace App\Services;

use App\Models\Progreso;
use App\Models\User;

/**
 * Servicio de cálculo de TDEE (Total Daily Energy Expenditure)
 * y distribución de macronutrientes sugeridos.
 *
 * Fórmula: Mifflin-St Jeor (1990), la más recomendada por precisión
 * en personas no-obesas.
 *
 *   Hombres: BMR = 10·peso(kg) + 6.25·altura(cm) - 5·edad + 5
 *   Mujeres: BMR = 10·peso(kg) + 6.25·altura(cm) - 5·edad - 161
 *
 * TDEE = BMR × factor de actividad
 * Calorías target = TDEE + ajuste por objetivo
 *
 * Distribución de macros (g/kg de peso corporal):
 *   Perder grasa:  2.0g/kg proteína, 25% grasas, resto carbs, -20% cal
 *   Mantener:      1.6g/kg proteína, 30% grasas, resto carbs
 *   Ganar masa:    1.8g/kg proteína, 25% grasas, resto carbs, +15% cal
 */
class TdeeService
{
    public const FACTORES_ACTIVIDAD = [
        'sedentario' => 1.2,
        'ligero'     => 1.375,
        'moderado'   => 1.55,
        'activo'     => 1.725,
        'muy_activo' => 1.9,
    ];

    public const AJUSTES_OBJETIVO = [
        // [delta_calorico_pct, proteina_g_kg, grasa_pct]
        'perder_grasa' => [-0.20, 2.0, 0.25],
        'mantener'     => [ 0.00, 1.6, 0.30],
        'ganar_masa'   => [ 0.15, 1.8, 0.25],
    ];

    public const KCAL_POR_GRANO = [
        'proteinas'     => 4,
        'carbohidratos' => 4,
        'grasas'        => 9,
    ];

    /**
     * Datos de entrada para el cálculo. Si falta algo, devuelve null y
     * la UI debe pedirle los datos al usuario.
     */
    public function resolverInputs(User $user): array
    {
        // Sexo y edad: del user (nullable). Actividad y objetivo: del user.
        // Peso y altura: del ÚLTIMO registro de Progreso (más actualizado).
        $ultimoProgreso = Progreso::where('user_id', $user->id)
            ->orderBy('fecha', 'desc')
            ->first();

        return [
            'sexo'    => $user->sexo,
            'edad'    => $user->edad,
            'peso'    => $ultimoProgreso?->peso,
            'altura'  => $ultimoProgreso?->altura ?? $user->altura,
            'nivel_actividad'      => $user->nivel_actividad,
            'objetivo_nutricional' => $user->objetivo_nutricional ?? 'mantener',
            'ultimo_progreso_id'   => $ultimoProgreso?->id,
            'ultimo_progreso_fecha' => $ultimoProgreso?->fecha?->toDateString(),
        ];
    }

    /**
     * ¿Tenemos todos los datos necesarios para calcular TDEE?
     */
    public function inputsCompletos(array $inputs): bool
    {
        return !is_null($inputs['sexo'])
            && !is_null($inputs['edad'])
            && !is_null($inputs['peso'])
            && !is_null($inputs['altura'])
            && !is_null($inputs['nivel_actividad']);
    }

    /**
     * Calcula BMR (Mifflin-St Jeor).
     */
    public function calcBmr(string $sexo, float $peso, float $altura, int $edad): float
    {
        $base = 10 * $peso + 6.25 * $altura - 5 * $edad;
        return $sexo === 'masculino'
            ? $base + 5
            : $base - 161;
    }

    /**
     * Calcula TDEE = BMR × factor de actividad.
     */
    public function calcTdee(float $bmr, string $nivelActividad): float
    {
        $factor = self::FACTORES_ACTIVIDAD[$nivelActividad] ?? 1.2;
        return $bmr * $factor;
    }

    /**
     * Calcula calorías target según objetivo.
     */
    public function calcCaloriasTarget(float $tdee, string $objetivo): int
    {
        $ajuste = self::AJUSTES_OBJETIVO[$objetivo] ?? self::AJUSTES_OBJETIVO['mantener'];
        return (int) round($tdee * (1 + $ajuste[0]));
    }

    /**
     * Calcula la distribución de macros en gramos.
     *
     * @return array{proteinas: int, grasas: int, carbohidratos: int}
     */
    public function calcMacros(int $caloriasTarget, float $peso, string $objetivo): array
    {
        $ajuste = self::AJUSTES_OBJETIVO[$objetivo] ?? self::AJUSTES_OBJETIVO['mantener'];
        [$deltaPct, $proteinaGkg, $grasaPct] = $ajuste;

        // Proteína: gramos por kg de peso corporal
        $proteinas = (int) round($peso * $proteinaGkg);
        $kcalProteinas = $proteinas * self::KCAL_POR_GRANO['proteinas'];

        // Grasa: % de calorías totales
        $kcalGrasas = (int) round($caloriasTarget * $grasaPct);
        $grasas = (int) round($kcalGrasas / self::KCAL_POR_GRANO['grasas']);

        // Carbs: lo que queda
        $kcalCarbs = max(0, $caloriasTarget - $kcalProteinas - $kcalGrasas);
        $carbohidratos = (int) round($kcalCarbs / self::KCAL_POR_GRANO['carbohidratos']);

        return compact('proteinas', 'grasas', 'carbohidratos');
    }

    /**
     * Punto de entrada principal: dado el user, devuelve el reporte completo
     * con los datos disponibles, los targets calculados y la lista de
     * campos faltantes (si los hay).
     *
     * @return array{
     *   inputs: array,
     *   inputs_completos: bool,
     *   faltantes: array<string>,
     *   bmr: ?float,
     *   tdee: ?float,
     *   calorias_target: ?int,
     *   macros: ?array,
     *   explicacion: array,
     * }
     */
    public function calcular(User $user): array
    {
        $inputs = $this->resolverInputs($user);
        $completos = $this->inputsCompletos($inputs);

        if (!$completos) {
            $faltantes = [];
            if (!$inputs['sexo']) $faltantes[] = 'sexo';
            if (!$inputs['edad']) $faltantes[] = 'edad';
            if (!$inputs['peso']) $faltantes[] = 'peso';
            if (!$inputs['altura']) $faltantes[] = 'altura';
            if (!$inputs['nivel_actividad']) $faltantes[] = 'nivel_actividad';

            return [
                'inputs' => $inputs,
                'inputs_completos' => false,
                'faltantes' => $faltantes,
                'bmr' => null,
                'tdee' => null,
                'calorias_target' => null,
                'macros' => null,
                'explicacion' => $this->explicacion(null, null, null, null),
            ];
        }

        $bmr = $this->calcBmr($inputs['sexo'], (float) $inputs['peso'], (float) $inputs['altura'], (int) $inputs['edad']);
        $tdee = $this->calcTdee($bmr, $inputs['nivel_actividad']);
        $caloriasTarget = $this->calcCaloriasTarget($tdee, $inputs['objetivo_nutricional']);
        $macros = $this->calcMacros($caloriasTarget, (float) $inputs['peso'], $inputs['objetivo_nutricional']);

        return [
            'inputs' => $inputs,
            'inputs_completos' => true,
            'faltantes' => [],
            'bmr' => round($bmr, 0),
            'tdee' => round($tdee, 0),
            'calorias_target' => $caloriasTarget,
            'macros' => $macros,
            'explicacion' => $this->explicacion($bmr, $tdee, $caloriasTarget, $inputs['objetivo_nutricional']),
        ];
    }

    /**
     * Texto explicativo de la fórmula, para mostrar en la UI.
     */
    public function explicacion(?float $bmr, ?float $tdee, ?int $caloriasTarget, ?string $objetivo): array
    {
        return [
            'formula' => 'Mifflin-St Jeor',
            'formula_detalle' => 'BMR = 10·peso + 6.25·altura - 5·edad + 5 (hombres) o -161 (mujeres). TDEE = BMR × factor de actividad.',
            'factor_actividad' => self::FACTORES_ACTIVIDAD,
            'ajuste_objetivo' => [
                'perder_grasa' => 'Déficit calórico del 20%, alta proteína (2g/kg) para preservar músculo.',
                'mantener'     => 'Calorías de mantenimiento, proteína moderada (1.6g/kg).',
                'ganar_masa'   => 'Superávit calórico del 15%, proteína alta (1.8g/kg) para construir músculo.',
            ][$objetivo] ?? null,
        ];
    }
}
