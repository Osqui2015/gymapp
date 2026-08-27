<?php

namespace App\Http\Controllers;

use App\Models\Historial;
use App\Models\Rutina;
use App\Models\User;
use App\Services\AchievementService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class HistorialController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $targetUserId = (int) $request->integer('user_id', $user->id);

        if ($targetUserId !== $user->id) {
            if (! $user->hasRole([User::ROLE_TRAINER, User::ROLE_ADMINISTRADOR])) {
                return response()->json(['error' => 'No autorizado'], 403);
            }

            if ($user->hasRole(User::ROLE_TRAINER)) {
                $target = User::findOrFail($targetUserId);
                if ($target->trainer_id !== $user->id) {
                    return response()->json(['error' => 'No autorizado'], 403);
                }
            }
        }

        $query = Historial::where('user_id', $targetUserId)
            ->when($request->filled('rutina_nombre'), function ($query) use ($request) {
                $query->where('rutina_nombre', $request->rutina_nombre);
            })
            ->when($request->filled('ejercicio'), function ($query) use ($request) {
                $query->where('ejercicio_nombre', 'like', '%' . $request->ejercicio . '%');
            })
            ->when($request->filled('from'), function ($query) use ($request) {
                $query->where('fecha', '>=', $request->from);
            })
            ->when($request->filled('to'), function ($query) use ($request) {
                $query->where('fecha', '<=', $request->to);
            })
            ->orderBy('fecha', 'desc')
            ->orderBy('id');

        // Paginación opcional
        if ($request->boolean('paginated') || $request->has('page')) {
            $perPage = min((int) $request->input('per_page', 50), 200);
            return response()->json($query->paginate($perPage));
        }

        return response()->json($query->get());
    }

    public function guardar(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'rutina_nombre' => ['required', 'string', 'max:255'],
            'dia' => ['required', 'string', 'max:255'],
            'ejercicio_nombre' => ['required', 'string', 'max:255'],
            'series_numero' => ['required', 'integer', 'min:1', 'max:50'],
            'series_completadas' => ['nullable', 'integer', 'min:0', 'max:50'],
            'reps_min' => ['required', 'string', 'max:255'],
            'reps_max' => ['required', 'string', 'max:255'],
            'reps_realizadas' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'descanso_min' => ['required', 'numeric', 'min:0', 'max:30'],
            'peso' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'completado' => ['nullable', 'boolean'],
            'superserie_grupo' => ['nullable', 'integer', 'min:0'],
            // Fase 3: esfuerzo por set
            'esfuerzo_tipo' => ['nullable', 'string', 'in:rir,rpe'],
            'esfuerzo_valor' => ['nullable', 'integer', 'min:0', 'max:10'],
        ]);

        $data['user_id'] = $user->id;
        $data['fecha'] = Carbon::now()->toDateString();

        Historial::updateOrCreate(
            [
                'user_id' => $user->id,
                'rutina_nombre' => $data['rutina_nombre'],
                'dia' => $data['dia'],
                'ejercicio_nombre' => $data['ejercicio_nombre'],
                'series_numero' => $data['series_numero'],
            ],
            $data
        );

        $newMedals = AchievementService::checkWorkoutMilestones($user);

        return response()->json([
            'message' => 'Guardado',
            'new_medals' => $newMedals,
        ]);
    }

    public function marcarCompletado(Request $request)
    {
        $user = $request->user();

        $historial = Historial::where('user_id', $user->id)
            ->where('rutina_nombre', $request->rutina_nombre)
            ->where('dia', $request->dia)
            ->where('ejercicio_nombre', $request->ejercicio_nombre)
            ->where('series_numero', $request->series_numero)
            ->first();

        if ($historial) {
            $historial->update(['completado' => true]);
        }

        $newMedals = AchievementService::checkWorkoutMilestones($user);

        return response()->json([
            'message' => 'Completado',
            'new_medals' => $newMedals,
        ]);
    }

    public function obtenerProgreso(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'rutina_nombre' => ['required', 'string', 'max:255'],
        ]);

        $ultimo = Historial::where('user_id', $user->id)
            ->where('rutina_nombre', $data['rutina_nombre'])
            ->where('completado', true)
            ->orderBy('fecha', 'desc')
            ->first();

        if (!$ultimo) {
            return response()->json(['dia_actual' => 'Día 1']);
        }

        $diasCompletados = Historial::where('user_id', $user->id)
            ->where('rutina_nombre', $data['rutina_nombre'])
            ->where('completado', true)
            ->selectRaw('DISTINCT dia')
            ->get()
            ->pluck('dia')
            ->toArray();

        // D1: nivel/modalidad vienen de la relación `rutina`, no de columnas denormalizadas.
        $userRutina = $user->rutinaSeleccionada()->with('rutina')->first();
        $nivel = $userRutina?->rutina?->nivel ?? Rutina::query()->value('nivel');
        $modalidad = $userRutina?->rutina?->modalidad ?? Rutina::query()->value('modalidad');

        if (! $nivel || ! $modalidad) {
            return response()->json(['dia_actual' => 'Día 1']);
        }

        $todosLosDias = Rutina::where('nivel', $nivel)
            ->where('modalidad', $modalidad)
            ->selectRaw('DISTINCT dia')
            ->orderBy('dia')
            ->get()
            ->pluck('dia')
            ->toArray();

        $diaActual = $todosLosDias[0] ?? 'Día 1';
        foreach ($todosLosDias as $index => $dia) {
            if (!in_array($dia, $diasCompletados)) {
                $diaActual = $dia;
                break;
            }
            if ($index === count($todosLosDias) - 1) {
                $diaActual = $todosLosDias[0];
            }
        }

        return response()->json(['dia_actual' => $diaActual]);
    }

    public function finalizarRutina(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        // D1: nivel/modalidad vienen de la relación `rutina`, no de columnas denormalizadas.
        $userRutina = $user->rutinaSeleccionada()->with('rutina')->first();

        if (!$userRutina || !$userRutina->rutina) {
            return response()->json(['error' => 'No hay rutina seleccionada'], 404);
        }

        $rutina = $userRutina->rutina;
        $rutinaNombre = $rutina->nivel.' '.$rutina->modalidad;
        $diaActual = $userRutina->dia_actual ?: 'Día 1';

        $rutinasDelDia = Rutina::where('nivel', $rutina->nivel)
            ->where('modalidad', $rutina->modalidad)
            ->where('dia', $diaActual)
            ->orderBy('orden')
            ->get();

        foreach ($rutinasDelDia as $rutinaDelDia) {
            $totalSeries = max(1, (int) $rutinaDelDia->series);

            for ($serie = 1; $serie <= $totalSeries; $serie++) {
                $historial = Historial::firstOrNew([
                    'user_id' => $user->id,
                    'rutina_nombre' => $rutinaNombre,
                    'dia' => $diaActual,
                    'ejercicio_nombre' => $rutinaDelDia->ejercicio_nombre,
                    'series_numero' => $serie,
                ]);

                $historial->fill([
                    'user_id' => $user->id,
                    'rutina_nombre' => $rutinaNombre,
                    'dia' => $diaActual,
                    'ejercicio_nombre' => $rutinaDelDia->ejercicio_nombre,
                    'series_numero' => $serie,
                    'series_completadas' => $historial->exists ? $historial->series_completadas : 1,
                    'reps_min' => $rutinaDelDia->reps_min,
                    'reps_max' => $rutinaDelDia->reps_max,
                    'reps_realizadas' => $historial->exists ? $historial->reps_realizadas : null,
                    'descanso_min' => $rutinaDelDia->descanso_min,
                    'completado' => true,
                    'fecha' => Carbon::now()->toDateString(),
                    'superserie_grupo' => $rutinaDelDia->superserie_grupo,
                ]);

                $historial->save();
            }
        }

        $diasDisponibles = Rutina::where('nivel', $rutina->nivel)
            ->where('modalidad', $rutina->modalidad)
            ->selectRaw('DISTINCT dia')
            ->orderBy('dia')
            ->pluck('dia')
            ->toArray();

        $diaSiguiente = 'Día 1';
        $indiceActual = array_search($diaActual, $diasDisponibles, true);

        if ($indiceActual !== false && isset($diasDisponibles[$indiceActual + 1])) {
            $diaSiguiente = $diasDisponibles[$indiceActual + 1];
        }

        $userRutina->update([
            'dia_actual' => $diaSiguiente,
        ]);

        $newMedals = AchievementService::checkWorkoutMilestones($user);

        return response()->json([
            'message' => 'Rutina finalizada',
            'dia_actual' => $diaSiguiente,
            'rutina_nombre' => $rutinaNombre,
            'new_medals' => $newMedals,
        ]);
    }

    /**
     * Devuelve las fechas con sesiones del user en un mes/año.
     * Para el componente del calendario.
     *
     * Query params: ?year=2026&month=8&user_id=X (opcional, trainer/admin)
     * Response: { dates: ['2026-08-15', '2026-08-16'], counts: { '2026-08-15': 3, ... } }
     */
    public function calendar(Request $request)
    {
        $user = $request->user();
        $targetUserId = (int) $request->integer('user_id', $user->id);

        if ($targetUserId !== $user->id && ! $user->hasRole([User::ROLE_TRAINER, User::ROLE_ADMINISTRADOR])) {
            return response()->json(['error' => 'No autorizado'], 403);
        }
        if ($user->hasRole(User::ROLE_TRAINER) && $targetUserId !== $user->id) {
            $target = User::findOrFail($targetUserId);
            if ($target->trainer_id !== $user->id) {
                return response()->json(['error' => 'No autorizado'], 403);
            }
        }

        $year = (int) $request->integer('year', now()->year);
        $month = (int) $request->integer('month', now()->month);

        $start = \Carbon\Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $sesiones = \App\Models\Historial::where('user_id', $targetUserId)
            ->whereBetween('fecha', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('fecha, COUNT(*) as total, SUM(series_completadas) as series')
            ->groupBy('fecha')
            ->orderBy('fecha')
            ->get();

        return response()->json([
            'year' => $year,
            'month' => $month,
            'dates' => $sesiones->pluck('fecha')->toArray(),
            'counts' => $sesiones->pluck('total', 'fecha')->toArray(),
            'series' => $sesiones->pluck('series', 'fecha')->toArray(),
        ]);
    }

    /**
     * Fase 7 — Resumen de la semana con dots para WeekCalendar.
     *
     * Por defecto la semana actual (lunes a domingo).
     * Query: ?week_start=YYYY-MM-DD&user_id=X
     *
     * Response:
     *   - week_start, week_end (YYYY-MM-DD)
     *   - days: array de 7 elementos (lun..dom) con:
     *       { date, dia_semana_es, dia_semana_corto, es_hoy, sets, volumen,
     *         ejercicios: ['Press banca', 'Sentadilla', ...], completado: bool }
     *   - totals: { sets, volumen, dias_entrenados, dias_descanso }
     *   - streak: racha actual
     */
    public function weekSummary(Request $request)
    {
        $user = $request->user();
        $targetUserId = (int) $request->integer('user_id', $user->id);

        if ($targetUserId !== $user->id && ! $user->hasRole([User::ROLE_TRAINER, User::ROLE_ADMINISTRADOR])) {
            return response()->json(['error' => 'No autorizado'], 403);
        }
        if ($user->hasRole(User::ROLE_TRAINER) && $targetUserId !== $user->id) {
            $target = User::findOrFail($targetUserId);
            if ($target->trainer_id !== $user->id) {
                return response()->json(['error' => 'No autorizado'], 403);
            }
        }

        $weekStart = $request->filled('week_start')
            ? Carbon::parse($request->week_start)->startOfDay()
            : now()->startOfWeek(Carbon::MONDAY);

        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

        $sesiones = Historial::where('user_id', $targetUserId)
            ->where('completado', true)
            ->whereDate('fecha', '>=', $weekStart->toDateString())
            ->whereDate('fecha', '<=', $weekEnd->toDateString())
            ->selectRaw('DATE(fecha) as fecha_key, COUNT(*) as sets, SUM(peso * COALESCE(reps_realizadas, 0)) as volumen')
            ->groupBy('fecha_key')
            ->orderBy('fecha_key')
            ->get()
            ->keyBy('fecha_key');

        // Traer ejercicios por día en una segunda query (compatible con SQLite y MySQL)
        $ejerciciosPorFecha = Historial::where('user_id', $targetUserId)
            ->where('completado', true)
            ->whereDate('fecha', '>=', $weekStart->toDateString())
            ->whereDate('fecha', '<=', $weekEnd->toDateString())
            ->select('fecha', 'ejercicio_nombre')
            ->distinct()
            ->orderBy('fecha')
            ->get()
            ->groupBy(fn($r) => Carbon::parse($r->fecha)->toDateString())
            ->map(fn($rows) => $rows->pluck('ejercicio_nombre')->unique()->values()->take(5)->all());

        $days = [];
        $totalSets = 0;
        $totalVolumen = 0;
        $diasEntrenados = 0;
        $diasDescanso = 0;

        $diasSemana = [
            1 => ['lunes', 'Lun'],
            2 => ['martes', 'Mar'],
            3 => ['miércoles', 'Mié'],
            4 => ['jueves', 'Jue'],
            5 => ['viernes', 'Vie'],
            6 => ['sábado', 'Sáb'],
            7 => ['domingo', 'Dom'],
        ];

        for ($i = 0; $i < 7; $i++) {
            $currentDay = $weekStart->copy()->addDays($i);
            $date = $currentDay->toDateString();
            $sesion = $sesiones->get($date);
            $esHoy = $date === now()->toDateString();
            $diaNum = (int) $currentDay->dayOfWeek ?: 7;  // 0=Dom → 7

            $ejercicios = [];
            $sets = 0;
            $volumen = 0;
            $completado = false;

            if ($sesion) {
                $completado = true;
                $sets = (int) $sesion->sets;
                $volumen = (float) $sesion->volumen;
                $ejercicios = $ejerciciosPorFecha->get($date, []);
                $totalSets += $sets;
                $totalVolumen += $volumen;
                $diasEntrenados++;
            } else {
                $diasDescanso++;
            }

            $days[] = [
                'date' => $date,
                'dia_semana_es' => ucfirst($diasSemana[$diaNum][0]),
                'dia_semana_corto' => $diasSemana[$diaNum][1],
                'es_hoy' => $esHoy,
                'sets' => $sets,
                'volumen' => round($volumen, 1),
                'ejercicios' => $ejercicios,
                'completado' => $completado,
            ];
        }

        // Streak rápido
        $diasConActividad = Historial::where('user_id', $targetUserId)
            ->where('completado', true)
            ->whereDate('fecha', '>=', now()->subYears(2)->toDateString())
            ->selectRaw('DISTINCT fecha')
            ->orderBy('fecha', 'desc')
            ->pluck('fecha')
            ->map(fn($d) => Carbon::parse($d)->toDateString())
            ->all();

        $streak = $this->calcCurrentStreakInline($diasConActividad);

        return response()->json([
            'week_start' => $weekStart->toDateString(),
            'week_end' => $weekEnd->toDateString(),
            'days' => $days,
            'totals' => [
                'sets' => $totalSets,
                'volumen' => round($totalVolumen, 1),
                'dias_entrenados' => $diasEntrenados,
                'dias_descanso' => $diasDescanso,
            ],
            'streak' => $streak,
        ]);
    }

    private function calcCurrentStreakInline(array $diasOrdenadosDesc): int
    {
        if (empty($diasOrdenadosDesc)) return 0;
        $streak = 0;
        $expected = now()->toDateString();
        $diasSet = array_flip($diasOrdenadosDesc);
        if (!isset($diasSet[$expected])) {
            $expected = now()->subDay()->toDateString();
            if (!isset($diasSet[$expected])) return 0;
        }
        while (isset($diasSet[$expected])) {
            $streak++;
            $expected = Carbon::parse($expected)->subDay()->toDateString();
        }
        return $streak;
    }
}
