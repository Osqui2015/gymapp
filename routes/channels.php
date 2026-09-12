<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
*/

// Usuario puede escuchar su propio canal de comentarios
Broadcast::channel('trainer-comments.{alumnoId}', function ($user, $alumnoId) {
    return (int) $user->id === (int) $alumnoId;
});
