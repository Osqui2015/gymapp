<?php

namespace App\Console\Commands;

use App\Models\Membresia;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\PushService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Envía recordatorios automáticos:
 *   - Membresías que vencen en los próximos 7, 3 o 1 días
 *   - Usuarios que no entrenan hace más de 5 días
 *
 * Se programa en el scheduler (app/Console/Kernel.php) para correr 1 vez
 * por día (típicamente a la mañana, hora local del server).
 *
 * El comando es idempotente en el día: si ya existe una notificación del
 * mismo tipo para el mismo user en las últimas 24h, no la duplica.
 */
class SendReminders extends Command
{
    protected $signature = 'reminders:send {--days=7 : Días de anticipación para membresías}';
    protected $description = 'Envía recordatorios de membresías por vencer + usuarios inactivos';

    public function handle(NotificationService $notifService, PushService $pushService): int
    {
        $daysAnticipacion = (int) $this->option('days');
        $sent = 0;

        $sent += $this->remindExpiringMemberships($notifService, $pushService, $daysAnticipacion);
        $sent += $this->remindInactiveUsers($notifService, $pushService);

        $this->info("Recordatorios enviados: {$sent}");
        return self::SUCCESS;
    }

    /**
     * Recorre membresías activas que vencen en [hoy+1, hoy+days]
     * y manda una notificación. Usa el helper `notifiedToday` para no duplicar.
     */
    protected function remindExpiringMemberships(NotificationService $notif, PushService $push, int $days): int
    {
        $hoy = Carbon::today();
        $limite = $hoy->copy()->addDays($days);
        $count = 0;

        // Hitos: 7, 3, 1 días (los más útiles para el user)
        $hitos = array_unique(array_filter([7, 3, 1, $days]));

        foreach ($hitos as $dias) {
            $fechaObjetivo = $hoy->copy()->addDays($dias);

            $membresias = Membresia::where('estado', 'activo')
                ->whereDate('fecha_vencimiento', $fechaObjetivo)
                ->with('user')
                ->get();

            foreach ($membresias as $m) {
                if (! $m->user) continue;
                if ($this->notifiedToday($m->user, 'membership_expiring')) continue;

                $titulo = $dias === 1
                    ? '⚠️ Tu membresía vence mañana'
                    : "Tu membresía vence en {$dias} días";
                $body = "Vence el {$m->fecha_vencimiento->format('d/m/Y')}. Renová desde Configuración.";

                $notif->notify($m->user, 'membership_expiring', $titulo, $body, [
                    'membresia_id' => $m->id,
                    'dias_restantes' => $dias,
                    'url' => '/configuracion',
                ]);

                // Si tiene push suscrito, mandar también push real
                $push->sendToUser($m->user->id, $titulo, $body, [
                    'tag' => 'membership_expiring',
                ]);

                $count++;
            }
        }

        return $count;
    }

    /**
     * Recorre users con membresía activa que no entrenan hace >5 días.
     */
    protected function remindInactiveUsers(NotificationService $notif, PushService $push): int
    {
        $limite = Carbon::today()->subDays(5);
        $count = 0;

        $usersInactivos = User::whereDoesntHave('historials', function ($q) use ($limite) {
            $q->where('fecha', '>=', $limite);
        })->whereHas('membresias', function ($q) {
            $q->whereIn('estado', ['activo', 'por_vencer']);
        })->get();

        foreach ($usersInactivos as $user) {
            if ($this->notifiedToday($user, 'inactive_reminder')) continue;
            if ($this->notifiedToday($user, 'membership_expiring')) continue;

            $titulo = '💪 ¿Volvemos a entrenar?';
            $body = 'Llevás varios días sin registrar series. Mantené la constancia y vení a darle.';

            $notif->notify($user, 'inactive_reminder', $titulo, $body, [
                'url' => '/dashboard',
            ]);

            $push->sendToUser($user->id, $titulo, $body, [
                'tag' => 'inactive_reminder',
            ]);

            $count++;
        }

        return $count;
    }

    /**
     * Helper: ¿ya se le notificó hoy a este user este tipo?
     * Usa la tabla `notifications` y mira el created_at del mismo día.
     */
    protected function notifiedToday(User $user, string $type): bool
    {
        return $user->notifications()
            ->where('type', $type)
            ->whereDate('created_at', Carbon::today())
            ->exists();
    }
}
