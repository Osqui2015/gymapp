<?php

namespace App\Services;

use App\Models\PushSubscription;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

/**
 * Envía notificaciones push a los usuarios suscritos vía Web Push (RFC 8030).
 *
 * Requiere:
 *   - composer require minish/web-push
 *   - VAPID keys configuradas (php artisan webpush:vapid)
 *   - Variables en .env:
 *       WEBPUSH_VAPID_SUBJECT=mailto:admin@example.com
 *       WEBPUSH_VAPID_PUBLIC=...
 *       WEBPUSH_VAPID_PRIVATE=...
 *   - Variable en config/services.php bajo 'webpush' (ya existe)
 *
 * Si las VAPID keys no están configuradas, sendToUser() retorna 0 y loguea
 * un warning (degrade silencioso, no rompe la app).
 */
class PushService
{
    private ?WebPush $webPush = null;

    /**
     * Envía una notificación push a un usuario.
     * Retorna la cantidad de suscripciones que devolvieron 2xx.
     */
    public function sendToUser(int $userId, string $title, string $body, array $data = []): int
    {
        $subs = PushSubscription::where('user_id', $userId)->get();
        if ($subs->isEmpty()) return 0;

        $client = $this->getClient();
        if ($client === null) {
            Log::warning('[push] VAPID keys no configuradas, no se enviaron notificaciones');
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
            $subscription = Subscription::create([
                'endpoint' => $sub->endpoint,
                'publicKey' => $sub->p256dh,
                'authToken' => $sub->auth,
                'contentEncoding' => 'aesgcm',
            ]);

            try {
                $report = $client->sendOneNotification($subscription, $payload);

                if ($report->isSuccess()) {
                    $sent++;
                    $sub->update(['last_seen_at' => now()]);
                } else {
                    // 404/410: endpoint inválido o usuario desuscripto, limpiar
                    $statusCode = $report->getResponse()->getStatusCode();
                    if (in_array($statusCode, [404, 410], true)) {
                        $sub->delete();
                        Log::info("[push] endpoint expirado (HTTP {$statusCode}), eliminado: " . substr($sub->endpoint, 0, 80));
                    } else {
                        Log::warning("[push] fallo enviando (HTTP {$statusCode}): " . substr($sub->endpoint, 0, 80));
                    }
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
     * Construye (y cachea) el cliente WebPush con la configuración VAPID.
     * Devuelve null si las keys no están configuradas.
     */
    private function getClient(): ?WebPush
    {
        if ($this->webPush !== null) return $this->webPush;

        $publicKey = config('services.webpush.vapid_public');
        $privateKey = config('services.webpush.vapid_private');
        $subject = config('services.webpush.subject');

        if (!$publicKey || !$privateKey) {
            return null;
        }

        $this->webPush = new WebPush([
            'VAPID' => [
                'subject' => $subject,
                'publicKey' => $publicKey,
                'privateKey' => $privateKey,
            ],
            // Timeouts razonables: no colgar la request si el push server está lento
            'timeout' => 10,
            'connect_timeout' => 5,
        ]);

        // Auto-retry del cliente para 5xx transitorios del push server
        $this->webPush->setReuseVAPIDHeaders(true);

        return $this->webPush;
    }
}
