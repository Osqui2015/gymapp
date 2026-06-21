<?php

namespace App\Events;

use App\Models\TrainerComment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TrainerCommentSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public TrainerComment $comment) {}

    /**
     * Canal privado: solo el alumno destinatario (y el trainer emisor)
     * pueden escuchar.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('trainer-comments.' . $this->comment->alumno_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'comment.sent';
    }

    public function broadcastWith(): array
    {
        $comment = $this->comment->loadMissing('trainer:id,name,nick');
        return [
            'id' => $comment->id,
            'trainer' => [
                'id' => $comment->trainer?->id,
                'name' => $comment->trainer?->name,
                'nick' => $comment->trainer?->nick,
            ],
            'body' => $comment->body,
            'historial_id' => $comment->historial_id,
            'created_at' => $comment->created_at?->toIso8601String(),
        ];
    }
}
