<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Lista las notificaciones del user autenticado.
     * Paginado, con filtro opcional de leídas/no leídas.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $perPage = min((int) $request->input('per_page', 30), 100);

        $query = Notification::forUser($user->id)
            ->orderByDesc('created_at');

        if ($request->boolean('unread_only')) {
            $query->unread();
        }

        return response()->json($query->paginate($perPage));
    }

    /**
     * Marca una notificación como leída.
     */
    public function markRead(Request $request, string $id)
    {
        $user = $request->user();
        $notif = Notification::forUser($user->id)->findOrFail($id);
        if (! $notif->read_at) {
            $notif->update(['read_at' => now()]);
        }
        return response()->json(['ok' => true]);
    }

    /**
     * Marca todas como leídas (batch).
     */
    public function markAllRead(Request $request)
    {
        $user = $request->user();
        $count = Notification::forUser($user->id)
            ->unread()
            ->update(['read_at' => now()]);
        return response()->json(['ok' => true, 'updated' => $count]);
    }

    /**
     * Elimina una notificación.
     */
    public function destroy(Request $request, string $id)
    {
        $user = $request->user();
        Notification::forUser($user->id)->findOrFail($id)->delete();
        return response()->json(['ok' => true]);
    }
}
