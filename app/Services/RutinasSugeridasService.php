<?php

namespace App\Services;

use App\Models\Ejercicio;
use App\Models\Historial;
use App\Models\Rutina;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Servicio de sugerencia de rutinas (basado en reglas, NO ML).
 *
 * Estrategia:
 *   1. Analiza el historial del user (últimos 60 días) para sacar:
 *      - Top grupos musculares (con % de cobertura)
 *      - Frecuencia de entrenamiento (días/mes)
 *      - Nivel (basado en pesos promedio, ajustables)
 *   2. Busca rutinas existentes que matcheen ese perfil.
 *   3. Rankea por score combinado:
 *      - Cobertura de grupos musculares: +10 por grupo cubierto
 *      - Frecuencia del user: bonus si la rutina tiene 3-5 días/semana
 *      - Popularidad (cuántos users la usan): +1 por cada 5 users, max +20
 *      - Frescura: si fue creada/modificada hace <30 días, +5
 *   4. Devuelve top N con explicación de por qué se sugiere cada una.
 *
 * Si el user no tiene historial (nuevo), devuelve las rutinas más populares
 * con nivel "Principiante".
 */
class RutinasSugeridasService
{
    private const LOOKBACK_DAYS = 60;
    private const TOP_N = 5;

    /**
     * Genera sugerencias para un user.
     */
    public function sugerirPara(User $user, int $topN = self::TOP_N): Collection
    {
        $perfil = $this->analizarPerfil($user);
        $todasLasRutinas = $this->cargarRutinas();

        $sugeridas = $todasLasRutinas->map(function (Rutina $rutina) use ($perfil) {
            return [
                'rutina' => $rutina,
                'score' => $this->calcularScore($rutina, $perfil),
                'razones' => $this->explicarRazones($rutina, $perfil),
            ];
        })->sortByDesc('score')->take($topN)->values();

        return $sugeridas;
    }

    /**
     * Analiza el perfil de entrenamiento del user.
     * Devuelve un array con:
     *   - top_grupos: ['Pecho' => 0.45, 'Espalda' => 0.30, ...]
     *   - dias_por_mes: 12 (frecuencia)
     *   - nivel_estimado: 'principiante' | 'intermedio' | 'avanzado'
     *   - tiene_historial: bool
     */
    public function analizarPerfil(User $user): array
    {
        $limite = Carbon::now()->subDays(self::LOOKBACK_DAYS);
        $historiales = Historial::where('user_id', $user->id)
            ->where('fecha', '>=', $limite->toDateString())
            ->get();

        if ($historiales->isEmpty()) {
            return [
                'top_grupos' => [],
                'dias_por_mes' => 0,
                'nivel_estimado' => 'principiante',
                'tiene_historial' => false,
            ];
        }

        // Top grupos musculares (join con ejercicios para sacar grupo_muscular)
        $ejercicios = Ejercicio::whereIn('nombre', $historiales->pluck('ejercicio_nombre')->unique())
            ->pluck('grupo_muscular', 'nombre');

        $gruposCounter = [];
        $total = $historiales->count();
        foreach ($historiales as $h) {
            $grupo = $ejercicios[$h->ejercicio_nombre] ?? null;
            if ($grupo) {
                $gruposCounter[$grupo] = ($gruposCounter[$grupo] ?? 0) + 1;
            }
        }
        arsort($gruposCounter);
        $topGrupos = [];
        foreach ($gruposCounter as $grupo => $count) {
            $topGrupos[$grupo] = round($count / $total, 2);
        }

        // Días únicos de entrenamiento en el período
        $diasUnicos = $historiales->pluck('fecha')->unique()->count();
        $diasPorMes = round($diasUnicos / (self::LOOKBACK_DAYS / 30));

        // Nivel estimado (basado en peso promedio, simple)
        $pesoPromedio = $historiales->where('peso', '>', 0)->avg('peso') ?? 0;
        $nivel = match (true) {
            $pesoPromedio >= 60 => 'avanzado',
            $pesoPromedio >= 30 => 'intermedio',
            default => 'principiante',
        };

        return [
            'top_grupos' => $topGrupos,
            'dias_por_mes' => $diasPorMes,
            'nivel_estimado' => $nivel,
            'tiene_historial' => true,
        ];
    }

    /**
     * Carga todas las rutinas (con sus metadatos básicos).
     * Una rutina = muchas filas en la tabla (cada fila es un ejercicio).
     * Agrupamos por (nivel, modalidad) que es la "identidad" de la rutina.
     */
    protected function cargarRutinas(): Collection
    {
        return Rutina::select('id', 'nivel', 'modalidad', 'ejercicio_nombre', 'ejercicio_id', 'created_by', 'updated_at', 'series', 'reps_min', 'reps_max')
            ->with('ejercicioRef:id,nombre,grupo_muscular')
            ->get()
            ->groupBy(fn ($r) => $r->nivel . '|' . $r->modalidad)
            ->map(function ($items) {
                $first = $items->first();
                return tap($first, function ($rutina) use ($items) {
                    $rutina->setAttribute('ejercicios_count', $items->count());
                    $rutina->setAttribute('grupos_cubiertos', $items->pluck('ejercicioRef.grupo_muscular')->filter()->unique()->values()->toArray());
                });
            })
            ->values();
    }

    /**
     * Calcula el score de afinidad entre una rutina y el perfil del user.
     */
    protected function calcularScore(Rutina $rutina, array $perfil): float
    {
        $score = 0.0;

        // Si no tiene historial, bonus por popularidad + nivel principiante
        if (! $perfil['tiene_historial']) {
            $score += $rutina->nivel === 'Principiante' ? 30 : 5;
            $score += min(20, $this->popularidad($rutina) * 0.5);
            return $score;
        }

        // Cobertura de grupos musculares del user
        $gruposCubiertos = collect($rutina->getAttribute('grupos_cubiertos'));
        $topGrupos = array_keys($perfil['top_grupos']);
        $overlap = $gruposCubiertos->intersect($topGrupos)->count();
        $score += $overlap * 15;

        // Match por nivel
        $nivelMatch = match (true) {
            str_contains($rutina->nivel, ucfirst($perfil['nivel_estimado'])) => 25,
            default => 5,
        };
        $score += $nivelMatch;

        // Frecuencia
        if ($perfil['dias_por_mes'] >= 12 && in_array($rutina->modalidad, ['Push', 'Pull', 'Legs'])) {
            $score += 15; // user muy activo, prefiere splits
        } elseif ($perfil['dias_por_mes'] >= 8 && in_array($rutina->modalidad, ['Upper', 'Lower'])) {
            $score += 10;
        } elseif ($perfil['dias_por_mes'] < 8 && in_array($rutina->modalidad, ['Full body'])) {
            $score += 10;
        }

        // Popularidad
        $score += $this->popularidad($rutina);

        // Frescura (rutina actualizada hace <30 días)
        if ($rutina->updated_at && $rutina->updated_at->gt(Carbon::now()->subDays(30))) {
            $score += 5;
        }

        return $score;
    }

    /**
     * Score de popularidad basado en cuántos users la tienen asignada.
     * Devuelve 0-20 puntos.
     */
    protected function popularidad(Rutina $rutina): float
    {
        $count = \App\Models\UserRutina::where('rutina_id', '!=', null)
            ->whereHas('rutina', function ($q) use ($rutina) {
                $q->where('nivel', $rutina->nivel)->where('modalidad', $rutina->modalidad);
            })
            ->distinct('user_id')
            ->count('user_id');
        return min(20, floor($count / 5) * 1);
    }

    /**
     * Genera una lista de razones legibles para mostrar en el front.
     */
    protected function explicarRazones(Rutina $rutina, array $perfil): array
    {
        $razones = [];

        if (! $perfil['tiene_historial']) {
            $razones[] = 'Recomendada para empezar';
            if ($rutina->nivel === 'Principiante') {
                $razones[] = 'Nivel principiante (ideal para nuevos usuarios)';
            }
            return $razones;
        }

        $gruposCubiertos = collect($rutina->getAttribute('grupos_cubiertos'));
        $topGrupos = array_keys($perfil['top_grupos']);
        $overlap = $gruposCubiertos->intersect($topGrupos);
        if ($overlap->isNotEmpty()) {
            $razones[] = 'Entrena ' . $overlap->implode(', ') . ' que son tus grupos principales';
        }

        if (str_contains($rutina->nivel, ucfirst($perfil['nivel_estimado']))) {
            $razones[] = 'Nivel ' . $rutina->nivel . ' (adecuado para tu nivel)';
        }

        if ($perfil['dias_por_mes'] >= 12) {
            $razones[] = 'Buen fit para usuarios activos (más de 12 días/mes)';
        } elseif ($perfil['dias_por_mes'] < 8) {
            $razones[] = 'Ideal si entrenás menos de 3 veces por semana';
        }

        return $razones ?: ['Recomendación general'];
    }
}
