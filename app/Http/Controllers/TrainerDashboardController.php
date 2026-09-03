<?php

namespace App\Http\Controllers;

use App\Models\EjercicioTrainer;
use App\Models\Historial;
use App\Models\Rutina;
use App\Models\User;
use App\Models\UserRutina;
use App\Services\TrainerDashboardService;
use Illuminate\Http\Request;

class TrainerDashboardController extends Controller
{
    public function __construct(private TrainerDashboardService $trainer) {}

    public function index(Request $request)
    {
        $trainerId = $request->user()->id;

        return response()->json($this->trainer->buildDashboardIndex($trainerId));
    }

    public function verAlumno(Request $request, User $alumno)
    {
        $trainer = $request->user();

        // Verificar que el alumno pertence al trainer
        if ($alumno->trainer_id !== $trainer->id) {
            return response()->json(['error' => 'No tienes acceso a este alumno'], 403);
        }

        return response()->json($this->trainer->buildAlumnoDetalle($alumno));
    }

    public function agregarComentario(Request $request, User $alumno)
    {
        $trainer = $request->user();

        if ($alumno->trainer_id !== $trainer->id && !$trainer->hasRole('administrador')) {
            return response()->json(['error' => 'No tienes acceso a este alumno'], 403);
        }

        $validated = $request->validate([
            'historial_id' => 'required|exists:historials,id',
            'comentario' => 'required|string|max:500',
        ]);

        $historial = Historial::where('id', $validated['historial_id'])
            ->where('user_id', $alumno->id)
            ->first();

        if (!$historial) {
            return response()->json(['error' => 'Entrada de historial no encontrada'], 404);
        }

        $historial->update([
            'comentario_trainer' => $validated['comentario'],
            'trainer_id' => $trainer->id,
        ]);

        return response()->json(['success' => true, 'comentario' => $historial->comentario_trainer]);
    }

    public function duplicarRutina(Request $request)
    {
        $trainer = $request->user();
        $validated = $request->validate([
            'rutina_id' => 'required|exists:rutinas,id',
            'nombre_nuevo' => 'required|string|max:255',
        ]);

        $nombre = $this->trainer->duplicarRutina(
            $trainer->id,
            (int) $validated['rutina_id'],
            $validated['nombre_nuevo']
        );

        return response()->json([
            'success' => true,
            'nombre' => $nombre,
        ]);
    }

    public function ejerciciosPrivados(Request $request)
    {
        $trainer = $request->user();

        $ejercicios = EjercicioTrainer::where('trainer_id', $trainer->id)
            ->orderBy('grupo_muscular')
            ->orderBy('nombre')
            ->get();

        return response()->json($ejercicios);
    }

    public function crearEjercicioPrivado(Request $request)
    {
        $trainer = $request->user();

        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'grupo_muscular' => 'required|string|max:100',
            'equipamiento' => 'nullable|string|max:100',
            'descripcion' => 'nullable|string|max:500',
        ]);

        $ejercicio = EjercicioTrainer::create([
            'trainer_id' => $trainer->id,
            'nombre' => $validated['nombre'],
            'grupo_muscular' => $validated['grupo_muscular'],
            'equipamiento' => $validated['equipamiento'] ?? 'Ninguno',
            'descripcion' => $validated['descripcion'] ?? null,
        ]);

        return response()->json($ejercicio, 201);
    }

    public function eliminarEjercicioPrivado(Request $request, $id)
    {
        $trainer = $request->user();

        $ejercicio = EjercicioTrainer::where('id', $id)
            ->where('trainer_id', $trainer->id)
            ->first();

        if (!$ejercicio) {
            return response()->json(['error' => 'Ejercicio no encontrado'], 404);
        }

        $ejercicio->delete();

        return response()->json(['success' => true]);
    }

    public function obtenerTodasRutinas(Request $request)
    {
        $trainer = $request->user();

        $rutinas = Rutina::where('created_by', $trainer->id)
            ->get()
            ->groupBy('nivel')
            ->map(function ($items, $nombre) {
                return [
                    'id' => $items->first()->id,
                    'nombre' => $nombre,
                    'dias' => $items->pluck('dia')->unique()->count(),
                    'modalidad' => $items->first()->modalidad,
                ];
            })
            ->values();

        return response()->json($rutinas);
    }
}
