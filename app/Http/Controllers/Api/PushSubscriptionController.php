<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PushSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PushSubscriptionController extends Controller
{
    /**
     * Registra (o actualiza) la suscripción push del usuario actual.
     * Si el endpoint ya existe para otro usuario, se reasigna al actual.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'endpoint' => 'required|string|max:512',
            'keys.p256dh' => 'required|string|max:255',
            'keys.auth' => 'required|string|max:255',
        ]);

        $user = $request->user();

        $sub = PushSubscription::updateOrCreate(
            ['endpoint' => $data['endpoint']],
            [
                'user_id' => $user->id,
                'p256dh' => $data['keys']['p256dh'],
                'auth' => $data['keys']['auth'],
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
                'last_seen_at' => now(),
            ],
        );

        return response()->json(['ok' => true, 'id' => $sub->id]);
    }

    /**
     * Elimina la suscripción (logout, o el usuario desactiva notificaciones).
     */
    public function destroy(Request $request)
    {
        $data = $request->validate([
            'endpoint' => 'required|string|max:512',
        ]);

        $deleted = PushSubscription::where('endpoint', $data['endpoint'])
            ->where('user_id', $request->user()->id)
            ->delete();

        return response()->json(['ok' => true, 'deleted' => $deleted]);
    }

    /**
     * Devuelve la VAPID public key (necesaria para suscribirse en el cliente).
     * Esta ruta es pública: la public key NO es secreta.
     */
    public function publicKey()
    {
        $key = config('services.webpush.vapid_public');
        return response()->json(['vapid_public_key' => $key]);
    }
}
