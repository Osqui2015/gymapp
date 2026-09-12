<?php

namespace App\Http\Controllers;

use App\Services\WorkoutSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkoutSessionController extends Controller
{
    public function __construct(
        protected WorkoutSessionService $sessionService
    ) {}

    public function iniciar(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'uuid' => ['required', 'string', 'max:64'],
            'rutina_nombre' => ['required', 'string', 'max:255'],
            'dia' => ['required', 'string', 'max:255'],
            'started_at' => ['nullable', 'date'],
            'series_totales' => ['nullable', 'integer', 'min:0'],
        ]);

        $sesion = $this->sessionService->iniciar($request->user(), $validated);

        return response()->json([
            'message' => 'Sesión iniciada',
            'sesion' => $sesion,
        ], 201);
    }

    public function activa(Request $request): JsonResponse
    {
        $sesion = $this->sessionService->obtenerActiva($request->user());

        return response()->json([
            'activa' => $sesion !== null,
            'sesion' => $sesion,
        ]);
    }

    public function finalizar(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'uuid' => ['required', 'string', 'max:64'],
            'ended_at' => ['nullable', 'date'],
            'notas' => ['nullable', 'string', 'max:1000'],
        ]);

        $resumen = $this->sessionService->finalizar(
            $request->user(),
            $validated['uuid'],
            $validated
        );

        return response()->json([
            'message' => 'Sesión finalizada exitosamente',
            'resumen' => $resumen,
        ]);
    }

    public function descartar(Request $request, string $uuid): JsonResponse
    {
        $eliminado = $this->sessionService->descartar($request->user(), $uuid);

        if (! $eliminado) {
            return response()->json([
                'error' => 'Sesión no encontrada o ya finalizada',
            ], 404);
        }

        return response()->json([
            'message' => 'Sesión descartada',
        ]);
    }
}
