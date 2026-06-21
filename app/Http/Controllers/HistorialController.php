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

        // Resolver nivel y modalidad desde la relación del usuario (mucho más robusto que parsear strings)
        $userRutina = $user->rutinaSeleccionada;
        $nivel = $userRutina?->nivel ?? Rutina::query()->value('nivel');
        $modalidad = $userRutina?->modalidad ?? Rutina::query()->value('modalidad');

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

        $userRutina = $user->rutinaSeleccionada;

        if (!$userRutina) {
            return response()->json(['error' => 'No hay rutina seleccionada'], 404);
        }

        $rutinaNombre = $userRutina->nivel.' '.$userRutina->modalidad;
        $diaActual = $userRutina->dia_actual ?: 'Día 1';

        $rutinasDelDia = Rutina::where('nivel', $userRutina->nivel)
            ->where('modalidad', $userRutina->modalidad)
            ->where('dia', $diaActual)
            ->orderBy('orden')
            ->get();

        foreach ($rutinasDelDia as $rutina) {
            $totalSeries = max(1, (int) $rutina->series);

            for ($serie = 1; $serie <= $totalSeries; $serie++) {
                $historial = Historial::firstOrNew([
                    'user_id' => $user->id,
                    'rutina_nombre' => $rutinaNombre,
                    'dia' => $diaActual,
                    'ejercicio_nombre' => $rutina->ejercicio_nombre,
                    'series_numero' => $serie,
                ]);

                $historial->fill([
                    'user_id' => $user->id,
                    'rutina_nombre' => $rutinaNombre,
                    'dia' => $diaActual,
                    'ejercicio_nombre' => $rutina->ejercicio_nombre,
                    'series_numero' => $serie,
                    'series_completadas' => $historial->exists ? $historial->series_completadas : 1,
                    'reps_min' => $rutina->reps_min,
                    'reps_max' => $rutina->reps_max,
                    'reps_realizadas' => $historial->exists ? $historial->reps_realizadas : null,
                    'descanso_min' => $rutina->descanso_min,
                    'completado' => true,
                    'fecha' => Carbon::now()->toDateString(),
                    'superserie_grupo' => $rutina->superserie_grupo,
                ]);

                $historial->save();
            }
        }

        $diasDisponibles = Rutina::where('nivel', $userRutina->nivel)
            ->where('modalidad', $userRutina->modalidad)
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
}
