<?php

namespace App\Http\Controllers;

use App\Models\MedallaUsuario;
use App\Services\AchievementService;
use Illuminate\Http\Request;

class MedallaController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Check workouts milestones to ensure medals are up-to-date
        AchievementService::checkWorkoutMilestones($user);

        $unlocked = MedallaUsuario::where('user_id', $user->id)
            ->get()
            ->keyBy('slug');

        $allMedallas = AchievementService::listAllMedallas();
        $stats = AchievementService::getAchievementsStats($user);

        $result = [];
        foreach ($allMedallas as $slug => $data) {
            $isUnlocked = isset($unlocked[$slug]);
            $ganadoAt = $isUnlocked ? $unlocked[$slug]->ganado_at : null;

            // Calculate progress for each medal
            $progressValue = 0;
            $targetValue = 0;

            switch ($slug) {
                case 'primer_entrenamiento':
                    $progressValue = min($stats['total_series'], 1);
                    $targetValue = 1;
                    break;
                case '100_series':
                    $progressValue = min($stats['total_series'], 100);
                    $targetValue = 100;
                    break;
                case 'racha_3_dias':
                    $progressValue = min($stats['streak'], 3);
                    $targetValue = 3;
                    break;
                case 'racha_5_dias':
                    $progressValue = min($stats['streak'], 5);
                    $targetValue = 5;
                    break;
                case '10_entrenamientos':
                    $progressValue = min($stats['unique_days'], 10);
                    $targetValue = 10;
                    break;
                case 'primer_progreso':
                    $progressValue = min($stats['progress_count'], 1);
                    $targetValue = 1;
                    break;
                case 'meta_alcanzada':
                    $progressValue = min($stats['completed_goals_count'], 1);
                    $targetValue = 1;
                    break;
                case 'creador_rutinas':
                    $progressValue = $stats['has_shared'] ? 1 : 0;
                    $targetValue = 1;
                    break;
            }

            $result[] = [
                'slug' => $slug,
                'nombre' => $data['nombre'],
                'descripcion' => $data['descripcion'],
                'icono' => $data['icono'],
                'desbloqueada' => $isUnlocked,
                'ganado_at' => $ganadoAt ? $ganadoAt->format('Y-m-d H:i:s') : null,
                'progreso' => $progressValue,
                'objetivo' => $targetValue,
            ];
        }

        return response()->json([
            'logros' => $result,
            'stats' => $stats,
        ]);
    }
}
