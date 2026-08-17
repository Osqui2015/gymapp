<?php

namespace App\Services;

use App\Models\User;
use App\Models\Historial;
use App\Models\Progreso;
use App\Models\Meta;
use App\Models\MedallaUsuario;
use Carbon\Carbon;

class AchievementService
{
    /**
     * Define all available medals.
     */
    public static function listAllMedallas(): array
    {
        return [
            'primer_entrenamiento' => [
                'nombre' => 'Primer Paso',
                'descripcion' => 'Completa y guarda tu primera serie de entrenamiento.',
                'icono' => '👟',
            ],
            'racha_3_dias' => [
                'nombre' => 'Racha de 3 días',
                'descripcion' => 'Entrena durante 3 días consecutivos.',
                'icono' => '🔥',
            ],
            'racha_5_dias' => [
                'nombre' => 'Racha de 5 días',
                'descripcion' => 'Entrena durante 5 días consecutivos.',
                'icono' => '⚡',
            ],
            '10_entrenamientos' => [
                'nombre' => 'Constancia Pura',
                'descripcion' => 'Registra entrenamientos en 10 días diferentes.',
                'icono' => '📅',
            ],
            '100_series' => [
                'nombre' => 'Toro Indomable',
                'descripcion' => 'Completa un total de 100 series de ejercicios.',
                'icono' => '🐂',
            ],
            'primer_progreso' => [
                'nombre' => 'Escultor',
                'descripcion' => 'Registra tu primer progreso corporal.',
                'icono' => '📐',
            ],
            'meta_alcanzada' => [
                'nombre' => 'Objetivo Cumplido',
                'descripcion' => 'Alcanza tu primera meta personal.',
                'icono' => '🎯',
            ],
            'creador_rutinas' => [
                'nombre' => 'Espíritu Comunitario',
                'descripcion' => 'Comparte una de tus rutinas con la comunidad.',
                'icono' => '🤝',
            ],
        ];
    }

    /**
     * Unlocks a medal for a user if they don't have it yet.
     */
    protected static function unlock(User $user, string $slug): ?MedallaUsuario
    {
        $alreadyUnlocked = MedallaUsuario::where('user_id', $user->id)
            ->where('slug', $slug)
            ->exists();

        if ($alreadyUnlocked) {
            return null;
        }

        $medallas = self::listAllMedallas();
        if (!isset($medallas[$slug])) {
            return null;
        }

        return MedallaUsuario::create([
            'user_id' => $user->id,
            'slug' => $slug,
            'nombre' => $medallas[$slug]['nombre'],
            'descripcion' => $medallas[$slug]['descripcion'],
            'ganado_at' => Carbon::now(),
        ]);
    }

    /**
     * Checks workout achievements (Series count, consecutive days, total days).
     * Returns array of newly unlocked medals.
     * OPTIMIZADO: calcula todas las stats en una sola pasada por los datos.
     */
    public static function checkWorkoutMilestones(User $user): array
    {
        // Una sola query trae todos los datos que necesitamos
        $historial = Historial::where('user_id', $user->id)
            ->where('completado', true)
            ->select('fecha')
            ->orderBy('fecha')
            ->get();

        if ($historial->isEmpty()) {
            return [];
        }

        $newlyUnlocked = [];

        // 1. Total series
        $totalCompletedSeries = $historial->count();
        if ($totalCompletedSeries >= 1) {
            $medal = self::unlock($user, 'primer_entrenamiento');
            if ($medal) $newlyUnlocked[] = $medal;
        }
        if ($totalCompletedSeries >= 100) {
            $medal = self::unlock($user, '100_series');
            if ($medal) $newlyUnlocked[] = $medal;
        }

        // 2. Fechas únicas ordenadas ascendente
        $sortedDates = $historial->pluck('fecha')
            ->unique()
            ->sort()
            ->values()
            ->map(fn($d) => Carbon::parse($d));

        $uniqueDaysCount = $sortedDates->count();
        if ($uniqueDaysCount >= 10) {
            $medal = self::unlock($user, '10_entrenamientos');
            if ($medal) $newlyUnlocked[] = $medal;
        }

        // 3. Calcular racha máxima (single-pass, sin allocs intermedios)
        $maxStreak = 1;
        $currentStreak = 1;
        for ($i = 0; $i < $sortedDates->count() - 1; $i++) {
            $diff = $sortedDates[$i]->diffInDays($sortedDates[$i + 1]);
            if ($diff == 1) {
                $currentStreak++;
                if ($currentStreak > $maxStreak) {
                    $maxStreak = $currentStreak;
                }
            } elseif ($diff > 1) {
                $currentStreak = 1;
            }
        }

        if ($maxStreak >= 3) {
            $medal = self::unlock($user, 'racha_3_dias');
            if ($medal) $newlyUnlocked[] = $medal;
        }
        if ($maxStreak >= 5) {
            $medal = self::unlock($user, 'racha_5_dias');
            if ($medal) $newlyUnlocked[] = $medal;
        }

        return $newlyUnlocked;
    }

    /**
     * Checks body progress achievements.
     */
    public static function checkProgressMilestones(User $user): array
    {
        $newlyUnlocked = [];

        $progressCount = Progreso::where('user_id', $user->id)->count();
        if ($progressCount >= 1) {
            $medal = self::unlock($user, 'primer_progreso');
            if ($medal) $newlyUnlocked[] = $medal;
        }

        return $newlyUnlocked;
    }

    /**
     * Checks personal goal achievements.
     */
    public static function checkGoalMilestones(User $user): array
    {
        $newlyUnlocked = [];

        $completedGoalsCount = Meta::where('user_id', $user->id)
            ->where('completada', true)
            ->count();

        if ($completedGoalsCount >= 1) {
            $medal = self::unlock($user, 'meta_alcanzada');
            if ($medal) $newlyUnlocked[] = $medal;
        }

        return $newlyUnlocked;
    }

    /**
     * Checks routine sharing achievements.
     */
    public static function checkRoutineMilestones(User $user): array
    {
        $newlyUnlocked = [];

        // Check if user has shared any routine (where created_by is this user and publica is true)
        $hasShared = \App\Models\Rutina::where('created_by', $user->id)
            ->where('publica', true)
            ->exists();

        if ($hasShared) {
            $medal = self::unlock($user, 'creador_rutinas');
            if ($medal) $newlyUnlocked[] = $medal;
        }

        return $newlyUnlocked;
    }

    /**
     * Returns stats for the achievements panel.
     */
    public static function getAchievementsStats(User $user): array
    {
        $totalCompletedSeries = Historial::where('user_id', $user->id)
            ->where('completado', true)
            ->count();

        $dates = Historial::where('user_id', $user->id)
            ->where('completado', true)
            ->select('fecha')
            ->distinct()
            ->orderBy('fecha', 'asc')
            ->pluck('fecha')
            ->map(fn($d) => Carbon::parse($d))
            ->values();

        $uniqueDaysCount = $dates->count();

        $maxStreak = 0;
        if ($dates->isNotEmpty()) {
            $maxStreak = 1;
            $currentStreak = 1;

            for ($i = 1; $i < $dates->count(); $i++) {
                $diff = $dates[$i - 1]->diffInDays($dates[$i]);
                if ($diff == 1) {
                    $currentStreak++;
                    if ($currentStreak > $maxStreak) {
                        $maxStreak = $currentStreak;
                    }
                } else {
                    $currentStreak = 1;
                    if ($currentStreak > $maxStreak) {
                        $maxStreak = $currentStreak;
                    }
                }
            }
        }

        $goalsCount = Meta::where('user_id', $user->id)->count();
        $completedGoalsCount = Meta::where('user_id', $user->id)->where('completada', true)->count();
        $progressCount = Progreso::where('user_id', $user->id)->count();
        $hasShared = \App\Models\Rutina::where('created_by', $user->id)->where('publica', true)->exists();

        return [
            'total_series' => $totalCompletedSeries,
            'unique_days' => $uniqueDaysCount,
            'streak' => $maxStreak,
            'goals_count' => $goalsCount,
            'completed_goals_count' => $completedGoalsCount,
            'progress_count' => $progressCount,
            'has_shared' => $hasShared,
        ];
    }
}
