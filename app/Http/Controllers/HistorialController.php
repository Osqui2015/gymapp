<?php

namespace App\Http\Controllers;

use App\Models\Historial;
use App\Models\Rutina;
use Illuminate\Http\Request;
use Carbon\Carbon;

class HistorialController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $historial = Historial::where('user_id', $user->id)
            ->when($request->filled('rutina_nombre'), function ($query) use ($request) {
                $query->where('rutina_nombre', $request->rutina_nombre);
            })
            ->orderBy('fecha', 'desc')
            ->orderBy('id')
            ->get();

        return response()->json($historial);
    }

    public function guardar(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'rutina_nombre' => ['required', 'string', 'max:255'],
            'dia' => ['required', 'string', 'max:255'],
            'ejercicio_nombre' => ['required', 'string', 'max:255'],
            'series_numero' => ['required', 'integer', 'min:1'],
            'series_completadas' => ['nullable', 'integer', 'min:0'],
            'reps_min' => ['required', 'string', 'max:255'],
            'reps_max' => ['required', 'string', 'max:255'],
            'reps_realizadas' => ['nullable', 'integer', 'min:0'],
            'descanso_min' => ['required', 'numeric'],
            'peso' => ['nullable', 'numeric', 'min:0'],
            'completado' => ['nullable', 'boolean'],
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

        return response()->json(['message' => 'Guardado']);
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

        return response()->json(['message' => 'Completado']);
    }

    public function obtenerProgreso(Request $request)
    {
        $user = $request->user();

        $ultimo = Historial::where('user_id', $user->id)
            ->where('rutina_nombre', $request->rutina_nombre)
            ->where('completado', true)
            ->orderBy('fecha', 'desc')
            ->first();

        if (!$ultimo) {
            return response()->json(['dia_actual' => 'Día 1']);
        }

        $diasCompletados = Historial::where('user_id', $user->id)
            ->where('rutina_nombre', $request->rutina_nombre)
            ->where('completado', true)
            ->selectRaw('DISTINCT dia')
            ->get()
            ->pluck('dia')
            ->toArray();

        $todosLosDias = Rutina::where('nivel', explode(' ', $request->rutina_nombre)[0])
            ->where('modalidad', substr($request->rutina_nombre, strlen(explode(' ', $request->rutina_nombre)[0]) + 1))
            ->selectRaw('DISTINCT dia')
            ->orderBy('dia')
            ->get()
            ->pluck('dia')
            ->toArray();

        $diaActual = 'Día 1';
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

        return response()->json([
            'message' => 'Rutina finalizada',
            'dia_actual' => $diaSiguiente,
            'rutina_nombre' => $rutinaNombre,
        ]);
    }
}
