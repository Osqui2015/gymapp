<?php

namespace App\Http\Controllers;

use App\Models\ProgresoFoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProgresoFotoController extends Controller
{
    /**
     * Lista las fotos de progreso del user actual, ordenadas por fecha desc.
     * GET /api/progreso/fotos
     *   query params: ?tipo=front|side|back (opcional, filtra por ángulo)
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = ProgresoFoto::where('user_id', $user->id)
            ->orderBy('fecha', 'desc')
            ->orderBy('id', 'desc');

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        $fotos = $query->get()->map(function ($foto) {
            return [
                'id' => $foto->id,
                'fecha' => $foto->fecha?->toDateString(),
                'tipo' => $foto->tipo,
                'url' => $foto->url,
                'notas' => $foto->notas,
                'peso' => $foto->peso,
                'created_at' => $foto->created_at?->toIso8601String(),
            ];
        });

        return response()->json($fotos);
    }

    /**
     * Sube una nueva foto de progreso.
     * POST /api/progreso/fotos
     *   multipart: foto (file), fecha (date), tipo (front|side|back), notas?, peso?
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'foto' => ['required', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:8192'], // 8MB
            'fecha' => ['required', 'date'],
            'tipo' => ['required', Rule::in(['front', 'side', 'back'])],
            'notas' => ['nullable', 'string', 'max:500'],
            'peso' => ['nullable', 'numeric', 'min:0', 'max:500'],
        ]);

        $user = $request->user();

        // Guardamos en storage/app/public/progreso_fotos/{user_id}/{timestamp}.{ext}
        $ext = $request->file('foto')->getClientOriginalExtension();
        $filename = "progreso_fotos/{$user->id}/" . now()->format('Ymd_His') . '_' . substr(uniqid(), -6) . ".{$ext}";

        $request->file('foto')->storeAs(dirname($filename), basename($filename), 'public');

        $foto = ProgresoFoto::create([
            'user_id' => $user->id,
            'fecha' => $data['fecha'],
            'tipo' => $data['tipo'],
            'foto_path' => $filename,
            'notas' => $data['notas'] ?? null,
            'peso' => $data['peso'] ?? null,
        ]);

        return response()->json([
            'message' => 'Foto subida',
            'foto' => [
                'id' => $foto->id,
                'fecha' => $foto->fecha?->toDateString(),
                'tipo' => $foto->tipo,
                'url' => $foto->url,
                'notas' => $foto->notas,
                'peso' => $foto->peso,
            ],
        ], 201);
    }

    /**
     * Borra una foto del user actual.
     * DELETE /api/progreso/fotos/{id}
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $foto = ProgresoFoto::where('user_id', $user->id)->findOrFail($id);

        // Borrar archivo del disco antes de borrar el registro
        if ($foto->foto_path && Storage::disk('public')->exists($foto->foto_path)) {
            Storage::disk('public')->delete($foto->foto_path);
        }
        $foto->delete();

        return response()->json(['message' => 'Foto eliminada']);
    }
}
