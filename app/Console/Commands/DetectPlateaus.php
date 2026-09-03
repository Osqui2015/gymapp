<?php

namespace App\Console\Commands;

use App\Models\Historial;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\PushService;
use App\Services\StatsService;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Detecta "mesetas" (plateaus) en el progreso de los usuarios activos.
 *
 * Para cada user con membresía activa, recorre los ejercicios con sets
 * en las últimas 6 semanas. Si el peso máximo en las últimas 4 semanas
 * no subió respecto a las 2 anteriores → plateau.
 *
 * Manda UNA notificación por user/día con la lista de plateaus detectados
 * (no spam por cada ejercicio).
 *
 * Scheduler: correr una vez al día, ideal a la mañana.
 */
class DetectPlateaus extends Command
{
    protected $signature = 'plateaus:detect {--weeks=4 : Ventana de comparación (semanas)} {--min-sets=6 : Mínimo de sets para evaluar}';
    protected $description = 'Detecta mesetas en el progreso de los usuarios y los notifica';

    public function handle(StatsService $stats, NotificationService $notif, PushService $push): int
    {
        $weeks = (int) $this->option('weeks');
        $minSets = (int) $this->option('min-sets');

        // Users con al menos 1 set completado en las últimas 6 semanas
        $cutoff = now()->subWeeks($weeks + 2)->toDateString();
        $userIds = Historial::where('completado', true)
            ->whereDate('fecha', '>=', $cutoff)
            ->distinct()
            ->pluck('user_id');

        $notificados = 0;

        foreach ($userIds as $userId) {
            $user = User::find($userId);
            if (!$user) continue;

            // Rate-limit: si ya le mandamos un plateau hoy, skip
            if ($this->notifiedToday($user, 'plateau_detected')) {
                continue;
            }

            $plateaus = $stats->detectPlateaus($userId, $minSets, $weeks);
            if (empty($plateaus)) {
                continue;
            }

            $this->sendNotification($user, $plateaus, $notif, $push);
            $notificados++;
        }

        $this->info("Plateaus detectados y notificados: {$notificados} usuarios");
        return self::SUCCESS;
    }

    protected function notifiedToday(User $user, string $type): bool
    {
        return $user->notifications()
            ->where('type', $type)
            ->whereDate('created_at', Carbon::today())
            ->exists();
    }

    protected function sendNotification(User $user, array $plateaus, NotificationService $notif, PushService $push): void
    {
        $count = count($plateaus);
        $ejemplos = array_slice(array_column($plateaus, 'ejercicio'), 0, 2);
        $lista = count($ejemplos) === 1
            ? $ejemplos[0]
            : implode(' y ', $ejemplos);

        $titulo = $count === 1
            ? "📉 Plateau detectado en {$lista}"
            : "📉 Plateaus en {$count} ejercicios";

        $sugerenciaMap = [
            'deload' => 'Probable fatiga acumulada. Probá una semana de deload (-10% peso, +2 reps por set).',
            'cambio_ejercicio' => 'Probá una variante del ejercicio (agarre, ángulo, máquina) para "resetear" la adaptación.',
            'aumentar_volumen' => 'Sumá 1-2 series por semana a este ejercicio. La fuerza responde al volumen total.',
        ];

        // Construir el body con el detalle por ejercicio
        $lineas = [];
        foreach (array_slice($plateaus, 0, 3) as $p) {
            $pesoStr = number_format($p['peso_reciente'], 1);
            $sug = $sugerenciaMap[$p['sugerencia']] ?? 'Variá la刺激 para romper el estancamiento.';
            $lineas[] = "• {$p['ejercicio']} ({$pesoStr}kg): {$sug}";
        }
        $body = implode("\n", $lineas);

        $notif->notify($user, 'plateau_detected', $titulo, $body, [
            'plateaus' => $plateaus,
            'url' => '/progreso',
        ]);

        $push->sendToUser($user->id, $titulo, $body, [
            'tag' => 'plateau_detected',
        ]);
    }
}
