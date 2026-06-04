<?php

namespace App\Http\Controllers;

use App\Models\Meta;
use App\Services\AchievementService;
use Illuminate\Http\Request;

class MetaController extends Controller
{
    public function index(Request $request)
    {
        $metas = Meta::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($metas);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'tipo' => 'required|string|in:entrenamiento_semanal,peso_corporal,cintura_corporal,brazos_corporal,pecho_corporal,otro',
            'descripcion' => 'required|string|max:255',
            'valor_objetivo' => 'required|numeric|min:0',
        ]);

        $data['user_id'] = $request->user()->id;
        $data['completada'] = false;

        $meta = Meta::create($data);

        return response()->json([
            'message' => 'Meta creada correctamente',
            'meta' => $meta,
        ], 201);
    }

    public function toggleCompletada(Request $request, $id)
    {
        $meta = Meta::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail();

        $meta->update([
            'completada' => !$meta->completada,
        ]);

        $newMedals = [];
        if ($meta->completada) {
            $newMedals = AchievementService::checkGoalMilestones($request->user());
        }

        return response()->json([
            'message' => 'Estado de la meta actualizado',
            'meta' => $meta,
            'new_medals' => $newMedals,
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $meta = Meta::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail();

        $meta->delete();

        return response()->json([
            'message' => 'Meta eliminada correctamente',
        ]);
    }
}
