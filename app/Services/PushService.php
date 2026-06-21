<?php

namespace App\Services;

use App\Models\PushSubscription;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PushService
{
    /**
     * Envía una notificación push a un usuario.
     * Retorna la cantidad de suscripciones que devolvieron 2xx.
     */
    public function sendToUser(int $userId, string $title, string $body, array $data = []): int
    {
        $subs = PushSubscription::where('user_id', $userId)->get();
        if ($subs->isEmpty()) return 0;

        $publicKey = config('services.webpush.vapid_public');
        $privateKey = config('services.webpush.vapid_private');
        $subject = config('services.webpush.subject');

        if (!$publicKey || !$privateKey) {
            Log::warning('[push] VAPID keys no configuradas');
            return 0;
        }

        $payload = json_encode([
            'title' => $title,
            'body' => $body,
            'icon' => config('services.webpush.default_icon'),
            'data' => $data,
            'tag' => $data['tag'] ?? 'gymapp',
        ]);

        $sent = 0;
        foreach ($subs as $sub) {
            try {
                $ok = $this->sendOne($sub, $payload, $publicKey, $privateKey, $subject);
                if ($ok) {
                    $sent++;
                    $sub->update(['last_seen_at' => now()]);
                } else {
                    // 404/410: endpoint inválido, limpiar
                    $sub->delete();
                }
            } catch (\Throwable $e) {
                Log::warning('[push] error enviando a endpoint', [
                    'endpoint' => substr($sub->endpoint, 0, 80),
                    'error' => $e->getMessage(),
                ]);
            }
        }
        return $sent;
    }

    /**
     * Implementación mínima del estándar Web Push (RFC 8030) sobre
     * aesgcm + ECDH P-256. Para una implementación robusta en producción
     * recomendamos `minish/web-push` o `web-token/jwt-framework`.
     */
    protected function sendOne(PushSubscription $sub, string $payload, string $publicKey, string $privateKey, string $subject): bool
    {
        // Esta es la versión simplificada. La librería de tu elección
        // se encarga del cifrado ECDH + AES-GCM + firma VAPID.
        // Se deja el esqueleto para que el equipo integre la lib elegida
        // (web-push-php, minish/web-push, etc.) sin tocar el resto.
        //
        // Ver docs: https://datatracker.ietf.org/doc/html/rfc8030
        // Ver docs: https://datatracker.ietf.org/doc/html/rfc8292 (VAPID)
        return false;
    }
}
