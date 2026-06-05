<?php

namespace App\Http\Controllers;

use App\Models\Rutina;
use App\Services\AchievementService;
use Illuminate\Http\Request;

class RutinaController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Rutina::query();

        if ($request->boolean('comunitarias')) {
            // Community routines: published by others
            $query->where('publica', true);
            if ($user) {
                $query->where('created_by', '!=', $user->id);
            }
        } else {
            // Standard user routines: default (null created_by) OR owned by user
            $query->where(function ($q) use ($user) {
                $q->whereNull('created_by');
                if ($user) {
                    $q->orWhere('created_by', $user->id);
                }
            });
        }

        if ($request->has('nivel') && $request->nivel) {
            $query->where('nivel', $request->nivel);
        }
        if ($request->has('modalidad') && $request->modalidad) {
            $query->where('modalidad', $request->modalidad);
        }
        if ($request->has('dia') && $request->dia) {
            $query->where('dia', $request->dia);
        }

        $query->with(['ejercicio', 'creador'])->orderBy('orden');

        $rutinas = $query->get();

        return response()->json($rutinas);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nivel' => 'required|string',
            'modalidad' => 'required|string',
            'dia' => 'required|string',
            'ejercicio_nombre' => 'required|string',
            'series' => 'required|integer',
            'reps_min' => 'required|string',
            'reps_max' => 'required|string',
            'descanso_min' => 'required|numeric',
            'orden' => 'required|integer',
            'superserie_grupo' => 'nullable|integer',
        ]);

        $data['created_by'] = $request->user()->id;

        $rutina = Rutina::create($data);

        return response()->json($rutina, 201);
    }

    public function compartir(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'nivel' => 'required|string|in:Personalizada',
            'modalidad' => 'required|string',
        ]);

        $affected = Rutina::where('created_by', $user->id)
            ->where('nivel', $data['nivel'])
            ->where('modalidad', $data['modalidad'])
            ->update(['publica' => true]);

        if ($affected === 0) {
            return response()->json(['error' => 'Rutina no encontrada o no pertenece a este usuario.'], 404);
        }

        // Check if creator achievements are unlocked
        $newMedals = AchievementService::checkRoutineMilestones($user);

        return response()->json([
            'message' => 'Rutina compartida con la comunidad con éxito.',
            'new_medals' => $newMedals,
        ]);
    }

    public function importar(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'nivel' => 'required|string',
            'modalidad' => 'required|string',
            'created_by' => 'required|integer',
        ]);

        $rutinasToImport = Rutina::where('nivel', $data['nivel'])
            ->where('modalidad', $data['modalidad'])
            ->where('created_by', $data['created_by'])
            ->where('publica', true)
            ->get();

        if ($rutinasToImport->isEmpty()) {
            return response()->json(['error' => 'La rutina seleccionada no está disponible para importar.'], 404);
        }

        // Resolve name collision
        $importedName = $data['modalidad'];
        $exists = Rutina::where('created_by', $user->id)
            ->where('nivel', 'Personalizada')
            ->where('modalidad', $importedName)
            ->exists();

        if ($exists) {
            $importedName = $data['modalidad'] . ' (Importada)';
            $counter = 1;
            while (Rutina::where('created_by', $user->id)->where('nivel', 'Personalizada')->where('modalidad', $importedName)->exists()) {
                $importedName = $data['modalidad'] . ' (Importada) ' . $counter;
                $counter++;
            }
        }

        // Copy each exercise
        foreach ($rutinasToImport as $rutina) {
            Rutina::create([
                'nivel' => 'Personalizada',
                'modalidad' => $importedName,
                'dia' => $rutina->dia,
                'created_by' => $user->id,
                'series' => $rutina->series,
                'reps_min' => $rutina->reps_min,
                'reps_max' => $rutina->reps_max,
                'descanso_min' => $rutina->descanso_min,
                'ejercicio_nombre' => $rutina->ejercicio_nombre,
                'orden' => $rutina->orden,
                'superserie_grupo' => $rutina->superserie_grupo,
                'publica' => false, // imported copy is private
            ]);
        }

        return response()->json([
            'message' => 'Rutina importada con éxito.',
            'modalidad' => $importedName,
        ]);
    }

    public function destroy(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'nivel' => 'required|string',
            'modalidad' => 'required|string',
        ]);

        \DB::transaction(function () use ($data, $user) {
            $query = Rutina::query()
                ->where('nivel', $data['nivel'])
                ->where('modalidad', $data['modalidad']);

            if ($user && !$user->hasRole(\App\Models\User::ROLE_ADMINISTRADOR)) {
                $query->where('created_by', $user->id);
            }

            $routineIds = $query->pluck('id');

            if ($routineIds->isNotEmpty()) {
                \App\Models\UserRutina::whereIn('rutina_id', $routineIds)->delete();
            }

            $query->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Rutina eliminada correctamente.',
        ]);
    }
}
