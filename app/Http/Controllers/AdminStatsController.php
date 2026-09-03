<?php

namespace App\Http\Controllers;

use App\Models\Historial;
use App\Models\User;
use App\Services\AdminStatsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Endpoints admin de estadísticas.
 * Toda la lógica de queries vive en AdminStatsService; este controller solo:
 *  - cachea
 *  - serializa a JSON
 *  - invalida el cache cuando corresponde
 */
class AdminStatsController extends Controller
{
    // TTL del cache de stats (5 minutos)
    private const CACHE_TTL = 300;

    public function __construct(private AdminStatsService $stats) {}

    public function estadisticas()
    {
        $payload = Cache::remember('admin.stats', self::CACHE_TTL, function () {
            return [
                'usuarios_por_mes' => $this->stats->getUsuariosPorMes(),
                'horas_pico' => $this->stats->getHorasPicoEntrenamiento(),
                'ejercicios_populares' => $this->stats->getEjerciciosPopulares(),
                'resumen' => $this->stats->getResumenGeneral(),
                'cached_at' => now()->toIso8601String(),
            ];
        });

        return response()->json($payload);
    }

    /**
     * Reportes avanzados: retención, churn, y frecuencia.
     * Cacheados 5 min.
     */
    public function reportes()
    {
        $payload = Cache::remember('admin.reportes', self::CACHE_TTL, function () {
            return [
                'retencion' => $this->stats->getRetencion(),
                'churn' => $this->stats->getChurnRate(),
                'frecuencia' => $this->stats->getFrecuenciaEntrenamiento(),
                'top_alumnos' => $this->stats->getTopAlumnos(),
                'cached_at' => now()->toIso8601String(),
            ];
        });

        return response()->json($payload);
    }

    public function miembrosActivos()
    {
        $haceUnaSemana = Carbon::now()->subWeek();

        $activos = Historial::where('completado', true)
            ->where('fecha', '>=', $haceUnaSemana)
            ->distinct('user_id')
            ->count('user_id');

        $totalAlumnos = User::whereIn('role', ['comun', 'alumno'])->count();

        return response()->json([
            'activos_semana' => $activos,
            'total_alumnos' => $totalAlumnos,
            'porcentaje_activos' => $totalAlumnos > 0 ? round(($activos / $totalAlumnos) * 100, 1) : 0,
        ]);
    }

    /**
     * Endpoint para invalidar el cache (útil después de importar/crear muchos datos)
     */
    public function invalidateCache()
    {
        Cache::forget('admin.stats');
        return response()->json(['success' => true]);
    }
}
