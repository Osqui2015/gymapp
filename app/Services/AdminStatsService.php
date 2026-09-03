<?php

namespace App\Services;

use App\Models\Ejercicio;
use App\Models\Historial;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Servicio de estadísticas administrativas.
 *
 * Encapsula las queries de reporting que antes vivían en AdminStatsController.
 * Cada método público retorna un array (o Collection) listo para serializar a JSON.
 *
 * Cache: el controller se encarga de Cache::remember() alrededor de estos métodos.
 * Invalidación: AdminStatsController::invalidateCache() limpia las claves usadas.
 */
class AdminStatsService
{
    /**
     * Retención: % de usuarios que entrenaron en el mes actual vs mes anterior.
     * - Nuevos este mes: users con primera sesión en este mes
     * - Retenidos: de los que entrenaron el mes pasado, cuántos también este mes
     * - Churn: de los que entrenaron el mes pasado, cuántos NO entrenaron este mes
     */
    public function getRetencion(): array
    {
        $mesActual = Carbon::now()->startOfMonth();
        $mesAnterior = Carbon::now()->subMonth()->startOfMonth();
        $finMesAnterior = $mesAnterior->copy()->endOfMonth();

        $usersActivosMesPasado = DB::table('historials')
            ->whereBetween('fecha', [$mesAnterior->toDateString(), $finMesAnterior->toDateString()])
            ->distinct()
            ->pluck('user_id');

        $usersActivosMesActual = DB::table('historials')
            ->where('fecha', '>=', $mesActual->toDateString())
            ->distinct()
            ->pluck('user_id');

        $retenidos = $usersActivosMesPasado->intersect($usersActivosMesActual)->count();
        $churned = $usersActivosMesPasado->diff($usersActivosMesActual)->count();
        $nuevos = $usersActivosMesActual->diff($usersActivosMesPasado)->count();
        $totalMesPasado = $usersActivosMesPasado->count();

        return [
            'mes_actual' => $mesActual->format('Y-m'),
            'mes_anterior' => $mesAnterior->format('Y-m'),
            'activos_mes_pasado' => $totalMesPasado,
            'activos_mes_actual' => $usersActivosMesActual->count(),
            'retenidos' => $retenidos,
            'churned' => $churned,
            'nuevos' => $nuevos,
            'tasa_retencion' => $totalMesPasado > 0 ? round(($retenidos / $totalMesPasado) * 100, 1) : 0,
            'tasa_churn' => $totalMesPasado > 0 ? round(($churned / $totalMesPasado) * 100, 1) : 0,
        ];
    }

    /**
     * Churn rate: % de usuarios que abandonaron en los últimos 30 días.
     * "Abandonaron" = membresía activa pero sin entrenar hace 14+ días.
     */
    public function getChurnRate(): array
    {
        $hace14Dias = Carbon::now()->subDays(14)->toDateString();
        $hace30Dias = Carbon::now()->subDays(30)->toDateString();

        $enRiesgo = User::whereHas('membresias', function ($q) {
                $q->whereIn('estado', ['activo', 'por_vencer']);
            })
            ->whereDoesntHave('historials', function ($q) use ($hace14Dias) {
                $q->where('fecha', '>=', $hace14Dias);
            })
            ->whereHas('historials', function ($q) use ($hace30Dias, $hace14Dias) {
                // tuvo actividad reciente en el pasado (entre 30 y 14 días atrás)
                $q->whereBetween('fecha', [$hace30Dias, $hace14Dias]);
            })
            ->count();

        $totalActivos = User::whereHas('membresias', function ($q) {
            $q->whereIn('estado', ['activo', 'por_vencer']);
        })->count();

        return [
            'en_riesgo' => $enRiesgo,
            'total_activos' => $totalActivos,
            'tasa' => $totalActivos > 0 ? round(($enRiesgo / $totalActivos) * 100, 1) : 0,
        ];
    }

    /**
     * Frecuencia promedio de entrenamiento por usuario activo.
     */
    public function getFrecuenciaEntrenamiento(): array
    {
        $hace30 = Carbon::now()->subDays(30)->toDateString();

        $sesionesPorUser = DB::table('historials')
            ->where('fecha', '>=', $hace30)
            ->selectRaw('user_id, COUNT(DISTINCT fecha) as dias')
            ->groupBy('user_id')
            ->get();

        $distribucion = [
            'diario' => 0,        // 20-30 días
            'frecuente' => 0,     // 12-19 días
            'regular' => 0,       // 6-11 días
            'ocasional' => 0,     // 1-5 días
            'inactivo' => 0,      // 0 días
        ];

        $suma = 0;
        $count = 0;
        foreach ($sesionesPorUser as $row) {
            $suma += $row->dias;
            $count++;
            if ($row->dias >= 20) $distribucion['diario']++;
            elseif ($row->dias >= 12) $distribucion['frecuente']++;
            elseif ($row->dias >= 6) $distribucion['regular']++;
            else $distribucion['ocasional']++;
        }

        // Sumar los que no entrenaron al grupo "inactivo"
        $totalUsers = User::whereHas('membresias', function ($q) {
            $q->whereIn('estado', ['activo', 'por_vencer']);
        })->count();
        $distribucion['inactivo'] = max(0, $totalUsers - $count);

        return [
            'promedio_dias_por_mes' => $count > 0 ? round($suma / $count, 1) : 0,
            'distribucion' => $distribucion,
        ];
    }

    /**
     * Top 10 usuarios con más sesiones en los últimos 30 días.
     */
    public function getTopAlumnos(): array
    {
        $hace30 = Carbon::now()->subDays(30)->toDateString();

        return DB::table('historials')
            ->join('users', 'users.id', '=', 'historials.user_id')
            ->where('historials.fecha', '>=', $hace30)
            ->select('users.id', 'users.name', 'users.nick',
                     DB::raw('COUNT(DISTINCT historials.fecha) as dias_entrenados'),
                     DB::raw('COUNT(*) as series_totales'))
            ->groupBy('users.id', 'users.name', 'users.nick')
            ->orderByDesc('dias_entrenados')
            ->limit(10)
            ->get()
            ->toArray();
    }

    public function getUsuariosPorMes()
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

    public function getHorasPicoEntrenamiento()
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

    public function getEjerciciosPopulares()
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

    public function getResumenGeneral()
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
}
