<?php

namespace App\Http\Controllers;

use App\Models\Rutina;
use App\Models\User;
use App\Services\AchievementService;
use App\Services\RutinasSugeridasService;
use Illuminate\Http\Request;

class RutinaController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Rutina::query();

        if ($request->boolean('comunitarias')) {
            $query->where('publica', true);
            if ($user) {
                $query->where('created_by', '!=', $user->id);
            }
        } else {
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

        // Soporte de paginación: si el cliente lo pide, devolver paginado
        if ($request->boolean('paginated') || $request->has('page')) {
            $perPage = min((int) $request->input('per_page', 50), 200);
            return response()->json($query->paginate($perPage));
        }

        // Si no, devolver todo (compatibilidad con clientes actuales)
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

        // Generar token público único para esta rutina (si no existe)
        $shareToken = bin2hex(random_bytes(8)); // 16 caracteres hex

        $affected = Rutina::where('created_by', $user->id)
            ->where('nivel', $data['nivel'])
            ->where('modalidad', $data['modalidad'])
            ->update(['publica' => true, 'share_token' => $shareToken]);

        if ($affected === 0) {
            return response()->json(['error' => 'Rutina no encontrada o no pertenece a este usuario.'], 404);
        }

        // Check if creator achievements are unlocked
        $newMedals = AchievementService::checkRoutineMilestones($user);

        return response()->json([
            'message' => 'Rutina compartida con la comunidad con éxito.',
            'new_medals' => $newMedals,
            'public_url' => route('rutina.publica', ['token' => $shareToken]),
            'share_token' => $shareToken,
        ]);
    }

    /**
     * Vista pública de una rutina compartida (sin auth).
     * Devuelve JSON para que la cargue el componente Vue de la vista rutina-publica.
     */
    public function verPublica($token)
    {
        $rutinas = Rutina::where('share_token', $token)
            ->where('publica', true)
            ->orderBy('dia')
            ->orderBy('orden')
            ->get();

        if ($rutinas->isEmpty()) {
            return response()->json(['error' => 'Rutina no encontrada o no es pública.'], 404);
        }

        $creador = User::find($rutinas->first()->created_by);
        $meta = $rutinas->first();

        // Agrupar por día
        $porDia = $rutinas->groupBy('dia')->map(function ($rows) {
            return $rows->map(function ($r) {
                return [
                    'id' => $r->id,
                    'ejercicio_nombre' => $r->ejercicio_nombre,
                    'series' => $r->series,
                    'reps_min' => $r->reps_min,
                    'reps_max' => $r->reps_max,
                    'descanso_min' => $r->descanso_min,
                    'superserie_grupo' => $r->superserie_grupo,
                ];
            })->values();
        });

        return response()->json([
            'nivel' => $meta->nivel,
            'modalidad' => $meta->modalidad,
            'creador' => $creador ? ['name' => $creador->name, 'nick' => $creador->nick] : null,
            'dias' => $porDia,
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

    /**
     * Sugiere rutinas al user basándose en su historial de entrenamiento.
     * Usa el service de reglas (no ML) que analiza:
     *   - Grupos musculares más trabajados
     *   - Frecuencia de entrenamiento
     *   - Nivel estimado
     *   - Popularidad de la rutina
     */
    public function sugeridas(Request $request, RutinasSugeridasService $service)
    {
        $user = $request->user();
        $topN = (int) $request->input('limit', 5);
        $topN = max(1, min(10, $topN));

        $sugeridas = $service->sugerirPara($user, $topN);

        $payload = $sugeridas->map(function ($item) {
            $rutina = $item['rutina'];
            return [
                'id' => $rutina->id,
                'nivel' => $rutina->nivel,
                'modalidad' => $rutina->modalidad,
                'ejercicios_count' => $rutina->getAttribute('ejercicios_count'),
                'grupos_cubiertos' => $rutina->getAttribute('grupos_cubiertos'),
                'score' => round($item['score'], 1),
                'razones' => $item['razones'],
            ];
        });

        return response()->json([
            'sugeridas' => $payload,
            'perfil' => $service->analizarPerfil($user),
        ]);
    }
}
