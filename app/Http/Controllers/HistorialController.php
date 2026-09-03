<?php

namespace App\Http\Controllers;

use App\Models\Historial;
use App\Models\User;
use App\Services\AchievementService;
use App\Services\HistorialService;
use App\Services\StatsService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class HistorialController extends Controller
{
    public function __construct(
        private HistorialService $historial,
        private StatsService $stats,
    ) {}

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
        $hoy = Carbon::now()->toDateString();

        // Acepta DOS formatos (backward compat):
        //   1) Single set: campos a nivel raíz (usado por el test SuperserieTest)
        //   2) Array de sets: { ..., series: [ {...}, {...} ] } (usado por MobileQuickSeriesInput)
        $series = $request->input('series');
        if (is_array($series) && !empty($series)) {
            $records = $series;
        } else {
            $records = [$request->all()];
        }

        $validatedRecords = [];
        foreach ($records as $rec) {
            $data = validator($rec, [
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
                // Nota libre por set (cómo se sintió, dolor, RPE subjetivo, etc.)
                'nota_user' => ['nullable', 'string', 'max:500'],
                // Fase 3: esfuerzo por set
                'esfuerzo_tipo' => ['nullable', 'string', 'in:rir,rpe'],
                'esfuerzo_valor' => ['nullable', 'integer', 'min:0', 'max:10'],
            ])->validate();

            $data['user_id'] = $user->id;
            $data['fecha'] = $hoy;
            $validatedRecords[] = $data;
        }

        foreach ($validatedRecords as $data) {
            Historial::updateOrCreate(
                [
                    'user_id' => $data['user_id'],
                    'rutina_nombre' => $data['rutina_nombre'],
                    'dia' => $data['dia'],
                    'ejercicio_nombre' => $data['ejercicio_nombre'],
                    'series_numero' => $data['series_numero'],
                ],
                $data
            );
        }

        $newMedals = AchievementService::checkWorkoutMilestones($user);

        return response()->json([
            'message' => 'Guardado',
            'count' => count($validatedRecords),
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

        return response()->json(
            $this->historial->obtenerProgreso($user->id, $data['rutina_nombre'])
        );
    }

    public function finalizarRutina(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $result = $this->historial->finalizarRutinaDia($user);

        if (isset($result['error'])) {
            $code = $result['error'] === 'No hay rutina seleccionada' ? 404 : 400;
            return response()->json($result, $code);
        }

        $newMedals = AchievementService::checkWorkoutMilestones($user);

        return response()->json([
            'message' => 'Rutina finalizada',
            'dia_actual' => $result['dia_actual'],
            'rutina_nombre' => $result['rutina_nombre'],
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

        return response()->json(
            $this->historial->buildCalendar($targetUserId, $year, $month)
        );
    }

    /**
     * Fase 7 — Resumen de la semana con dots para WeekCalendar.
     *
     * Por defecto la semana actual (lunes a domingo).
     * Query: ?week_start=YYYY-MM-DD&user_id=X
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

        return response()->json(
            $this->historial->buildWeekSummary(
                $targetUserId,
                $request->filled('week_start') ? $request->week_start : null,
                $this->stats,
            )
        );
    }

    /**
     * Compara el rendimiento de un ejercicio entre dos fechas.
     * GET /api/historial/comparar?ejercicio=X&desde=YYYY-MM-DD&hasta=YYYY-MM-DD
     */
    public function comparar(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'ejercicio' => 'required|string|max:255',
            'desde' => 'required|date',
            'hasta' => 'required|date|after_or_equal:desde',
        ]);

        $reporte = $this->historial->compararEjercicio(
            $user->id,
            $data['ejercicio'],
            $data['desde'],
            $data['hasta'],
        );

        return response()->json($reporte);
    }
}
