<?php

namespace App\Http\Controllers\Api;

use App\Events\TrainerCommentSent;
use App\Http\Controllers\Controller;
use App\Models\Historial;
use App\Models\TrainerComment;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class TrainerCommentController extends Controller
{
    /**
     * Listar comentarios del alumno autenticado (si es alumno)
     * o de un alumno específico (si es trainer/admin).
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $alumnoId = $request->integer('alumno_id') ?: $user->id;

        // autorización: trainer/admin pueden ver cualquier alumno, alumno solo el suyo
        if ($alumnoId !== $user->id && !$user->hasAnyRole(['trainer', 'admin'])) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $comments = TrainerComment::with('trainer:id,name,nick')
            ->where('alumno_id', $alumnoId)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return response()->json(['data' => $comments]);
    }

    /**
     * Trainer envía un comentario a un alumno.
     * Disparamos el evento broadcast (si broadcasting está configurado).
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'alumno_id' => 'required|integer|exists:users,id',
            'historial_id' => 'nullable|integer|exists:historials,id',
            'body' => 'required|string|max:2000',
        ]);

        $user = $request->user();
        if (!$user->hasAnyRole(['trainer', 'admin'])) {
            return response()->json(['error' => 'Solo trainers o admins pueden enviar comentarios.'], 403);
        }

        $comment = TrainerComment::create([
            'trainer_id' => $user->id,
            'alumno_id' => $data['alumno_id'],
            'historial_id' => $data['historial_id'] ?? null,
            'body' => $data['body'],
        ]);

        // Crear notificación in-app para el alumno (persiste en la DB)
        $alumno = User::find($data['alumno_id']);
        if ($alumno) {
            app(NotificationService::class)->notify(
                $alumno,
                'trainer_comment',
                'Tu trainer te dejó un comentario',
                mb_substr($data['body'], 0, 120),
                [
                    'comment_id' => $comment->id,
                    'trainer_id' => $user->id,
                    'trainer_name' => $user->name,
                    'url' => '/historial',
                ]
            );
        }

        // Si broadcasting está activo, dispara el evento realtime
        if (config('services.broadcasting.driver') !== 'null') {
            event(new TrainerCommentSent($comment));
        }

        return response()->json([
            'data' => $comment->load('trainer:id,name,nick'),
        ], 201);
    }

    /**
     * Marca un comentario como leído.
     */
    public function markRead(Request $request, TrainerComment $comment)
    {
        $user = $request->user();
        if ($comment->alumno_id !== $user->id) {
            return response()->json(['error' => 'No autorizado'], 403);
        }
        $comment->update(['read_at' => now()]);
        return response()->json(['ok' => true]);
    }
}
