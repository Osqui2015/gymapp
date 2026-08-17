<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;

/**
 * Mensajería 1-a-1 trainer ↔ alumno.
 *
 * Diseño:
 *   - Cualquier par de users pueden mensajearse (no solo trainer↔alumno)
 *   - Solo el sender y el recipient pueden ver/leer los mensajes
 *   - Trainer/admin pueden leer los mensajes de sus alumnos (para moderación)
 *   - Al recibir un mensaje, se crea una notificación in-app
 */
class MessageController extends Controller
{
    /**
     * Lista las conversaciones del user autenticado, con el último mensaje
     * de cada una y el contador de no leídos.
     */
    public function conversations(Request $request)
    {
        $user = $request->user();

        // Obtiene todos los mensajes donde el user aparece (sender o recipient)
        $messages = Message::where('sender_id', $user->id)
            ->orWhere('recipient_id', $user->id)
            ->orderByDesc('created_at')
            ->with(['sender:id,name,nick', 'recipient:id,name,nick'])
            ->get();

        // Agrupa por "el otro user" (el que no soy yo)
        $conversations = [];
        foreach ($messages as $msg) {
            $otherId = $msg->sender_id === $user->id ? $msg->recipient_id : $msg->sender_id;
            if (isset($conversations[$otherId])) continue; // ya tenemos el último

            $unread = Message::unreadFor($user->id)
                ->where('sender_id', $otherId)
                ->count();

            $conversations[$otherId] = [
                'other_user' => $msg->sender_id === $user->id ? $msg->recipient : $msg->sender,
                'last_message' => $msg,
                'unread_count' => $unread,
            ];
        }

        return response()->json([
            'conversations' => array_values($conversations),
        ]);
    }

    /**
     * Lista los mensajes de una conversación con otro user.
     */
    public function index(Request $request, int $otherId)
    {
        $user = $request->user();
        $this->authorizeAccess($user, $otherId);

        $messages = Message::conversationWith($user->id, $otherId)
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->with(['sender:id,name,nick'])
            ->limit(100)
            ->get();

        // Marcar como leídos los que el user recibió (no los que envió)
        Message::unreadFor($user->id)
            ->where('sender_id', $otherId)
            ->update(['read_at' => now()]);

        return response()->json(['data' => $messages]);
    }

    /**
     * Envía un mensaje.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'recipient_id' => 'required|integer|exists:users,id',
            'body' => 'required|string|max:5000',
        ]);

        $this->authorizeAccess($user, (int) $data['recipient_id']);

        $message = Message::create([
            'sender_id' => $user->id,
            'recipient_id' => $data['recipient_id'],
            'body' => $data['body'],
        ]);

        // Notificación in-app
        $recipient = User::find($data['recipient_id']);
        app(NotificationService::class)->notify(
            $recipient,
            'message',
            "💬 Mensaje de {$user->name}",
            mb_substr($data['body'], 0, 100),
            [
                'message_id' => $message->id,
                'sender_id' => $user->id,
                'sender_name' => $user->name,
                'url' => '/mensajes',
            ]
        );

        return response()->json([
            'data' => $message->load('sender:id,name,nick'),
        ], 201);
    }

    /**
     * Marca un mensaje como leído.
     */
    public function markRead(Request $request, Message $message)
    {
        $user = $request->user();
        if ($message->recipient_id !== $user->id) {
            return response()->json(['error' => 'No autorizado'], 403);
        }
        if (! $message->read_at) {
            $message->update(['read_at' => now()]);
        }
        return response()->json(['ok' => true]);
    }

    /**
     * Marca todos los mensajes de un otro user como leídos.
     */
    public function markAllRead(Request $request, int $otherId)
    {
        $user = $request->user();
        $count = Message::unreadFor($user->id)
            ->where('sender_id', $otherId)
            ->update(['read_at' => now()]);
        return response()->json(['ok' => true, 'updated' => $count]);
    }

    /**
     * Verifica que el user puede mensajearse con $otherId.
     * Reglas:
     *   - Cualquier user puede mensajearse con cualquier otro (no hay restricción)
     *   - Trainer/admin pueden leer los mensajes de sus alumnos (para moderación)
     */
    protected function authorizeAccess(User $user, int $otherId): void
    {
        if ($otherId === $user->id) {
            abort(400, 'No podés mensajearte con vos mismo.');
        }
        // Por ahora dejamos abierto. Si querés restringir (solo trainer↔alumno, etc.),
        // agregá la lógica acá.
    }
}
