<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Historial;
use App\Models\User;
use App\Models\Ejercicio;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AdminStatsController extends Controller
{
    // TTL del cache de stats (5 minutos)
    private const CACHE_TTL = 300;

    public function estadisticas()
    {
        $payload = Cache::remember('admin.stats', self::CACHE_TTL, function () {
            return [
                'usuarios_por_mes' => $this->getUsuariosPorMes(),
                'horas_pico' => $this->getHorasPicoEntrenamiento(),
                'ejercicios_populares' => $this->getEjerciciosPopulares(),
                'resumen' => $this->getResumenGeneral(),
                'cached_at' => now()->toIso8601String(),
            ];
        });

        return response()->json($payload);
    }

    private function getUsuariosPorMes()
    {
        $seisMesesAtras = Carbon::now()->subMonths(6)->startOfMonth();

        // Cross-DB: usar strftime en SQLite, DATE_FORMAT en MySQL
        $driver = DB::connection()->getDriverName();
        if ($driver === 'sqlite') {
            $dateExpr = "strftime('%Y-%m', created_at)";
        } else {
            $dateExpr = "DATE_FORMAT(created_at, '%Y-%m')";
        }

        $usuarios = User::select(
            DB::raw("$dateExpr as mes"),
            DB::raw("COUNT(*) as total")
        )
        ->where('created_at', '>=', $seisMesesAtras)
        ->groupBy(DB::raw($dateExpr))
        ->orderBy('mes')
        ->get();

        // Rellenar meses sin usuarios
        $meses = [];
        $fecha = $seisMesesAtras->copy();
        $ahora = Carbon::now();

        while ($fecha <= $ahora) {
            $mesKey = $fecha->format('Y-m');
            $meses[$mesKey] = [
                'mes' => $fecha->format('M Y'),
                'total' => 0,
            ];
            $fecha->addMonth();
        }

        foreach ($usuarios as $u) {
            $meses[$u->mes]['total'] = $u->total;
        }

        return array_values($meses);
    }

    private function getHorasPicoEntrenamiento()
    {
        $ultimoMes = Carbon::now()->subMonth();

        // Cross-DB: usar strftime en SQLite, HOUR en MySQL
        $driver = DB::connection()->getDriverName();
        if ($driver === 'sqlite') {
            $hourExpr = "strftime('%H', fecha)";
        } else {
            $hourExpr = "HOUR(fecha)";
        }

        $horas = Historial::select(
            DB::raw("$hourExpr as hora"),
            DB::raw("COUNT(*) as total")
        )
        ->where('fecha', '>=', $ultimoMes)
        ->whereNotNull('fecha')
        ->groupBy(DB::raw($hourExpr))
        ->orderBy('hora')
        ->get();

        $todasHoras = [];
        for ($i = 5; $i <= 23; $i++) {
            $todasHoras[$i] = ['hora' => $i, 'total' => 0, 'label' => sprintf('%02d:00', $i)];
        }

        foreach ($horas as $h) {
            $hHora = (int) $h->hora;
            if (isset($todasHoras[$hHora])) {
                $todasHoras[$hHora]['total'] = $h->total;
            }
        }

        ksort($todasHoras);

        return array_values($todasHoras);
    }

    private function getEjerciciosPopulares()
    {
        return Historial::select(
            'ejercicio_nombre',
            DB::raw("COUNT(*) as veces_usado"),
            DB::raw("AVG(peso) as peso_promedio"),
            DB::raw("SUM(reps_realizadas) as reps_totales")
        )
        ->whereNotNull('ejercicio_nombre')
        ->where('completado', true)
        ->groupBy('ejercicio_nombre')
        ->orderByDesc('veces_usado')
        ->limit(15)
        ->get()
        ->map(function ($item) {
            return [
                'ejercicio' => $item->ejercicio_nombre,
                'veces_usado' => $item->veces_usado,
                'peso_promedio' => round($item->peso_promedio ?? 0, 1),
                'reps_totales' => $item->reps_totales ?? 0,
            ];
        });
    }

    private function getResumenGeneral()
    {
        return [
            'total_usuarios' => User::count(),
            'usuarios_activos' => User::where('suspended', false)->count(),
            'total_trainers' => User::where('role', 'trainer')->count(),
            'total_entrenamientos' => Historial::where('completado', true)->count(),
            'ejercicios_totales' => Ejercicio::count(),
            'entrenamientos_hoy' => Historial::whereDate('fecha', today())
                ->where('completado', true)
                ->count(),
            'usuarios_nuevos_mes' => User::whereMonth('created_at', now()->month)->count(),
        ];
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