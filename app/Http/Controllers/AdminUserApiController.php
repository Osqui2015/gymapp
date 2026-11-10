<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAdminUserRequest;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\TelefonoService;
use App\Support\RolePermissions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AdminUserApiController extends Controller
{
    public function __construct(private TelefonoService $telefonoService) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', User::class);

        $search = $request->query('search');
        $perPage = $request->query('per_page', 10);

        $query = User::with('trainer:id,name')
            ->orderBy('name');

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('nick', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('role', 'like', "%{$search}%");
            });
        }

        $users = $query->paginate($perPage, ['id', 'nick', 'name', 'email', 'telefono', 'role', 'suspended', 'trainer_id']);

        // Marca de teléfono no válido (Fase 0.8): la UI la usa para mostrar
        // el badge "⚠ No válido" en vez de generar un link de WhatsApp roto.
        $items = collect($users->items())->map(function ($u) {
            $u->telefono_valido = $this->telefonoService->esValido($u->telefono);

            return $u;
        })->values();

        return response()->json([
            'users' => $items,
            'total' => $users->total(),
            'current_page' => $users->currentPage(),
            'last_page' => $users->lastPage(),
            'per_page' => $users->perPage(),
            'trainers' => User::where('role', User::ROLE_TRAINER)
                ->orWhere('role', User::ROLE_ADMINISTRADOR)
                ->orderBy('name')
                ->get(['id', 'name']),
            'current_user_id' => $request->user()->id,
        ]);
    }

    public function store(StoreAdminUserRequest $request): JsonResponse
    {
        $data = $request->validated();

        if ($data['role'] !== User::ROLE_ALUMNO) {
            $data['trainer_id'] = null;
        }

        if (! empty($data['trainer_id'])) {
            $trainer = User::findOrFail($data['trainer_id']);
            if (! $trainer->hasRole([User::ROLE_TRAINER, User::ROLE_ADMINISTRADOR])) {
                return response()->json([
                    'message' => 'El usuario seleccionado no es trainer.',
                    'errors' => ['trainer_id' => ['El usuario seleccionado no es trainer.']],
                ], 422);
            }
        }

        // Anti-escalada de privilegios: solo administrador puede crear roles privilegiados
        $rolesPrivilegiados = [
            User::ROLE_ADMINISTRADOR,
            User::ROLE_TRAINER,
            User::ROLE_COORDINADOR,
        ];

        if (in_array($data['role'], $rolesPrivilegiados, true)
            && ! $request->user()->hasRole(User::ROLE_ADMINISTRADOR)) {
            $message = 'Solo un administrador puede crear usuarios con roles privilegiados.';

            return response()->json([
                'message' => $message,
                'errors' => ['role' => [$message]],
            ], 403);
        }

        $user = User::create([
            'nick' => $data['nick'],
            'name' => $data['name'],
            'email' => $data['email'],
            'telefono' => $this->telefonoService->normalizar($data['telefono'] ?? null),
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'trainer_id' => $data['trainer_id'] ?? null,
        ]);

        AuditLog::forModel($user, 'created', null, [
            'nick' => $user->nick,
            'name' => $user->name,
            'role' => $user->role,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Usuario creado exitosamente',
            'user' => $user,
        ], 201);
    }

    public function update(Request $request, int $id)
    {
        $user = User::findOrFail($id);

        $this->authorize('update', $user);

        $oldValues = $user->toArray();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'telefono' => ['nullable', 'string', 'max:255'],
            // La lista de roles sale de RolePermissions (fuente única de
            // verdad). Antes era un string hardcodeado
            // ('in:comun,alumno,trainer,administrador') que dejaba fuera a
            // `coordinador` y `recepcionista`: eran roles IMPOSIBLES de
            // asignar desde el panel, aunque las policies los contemplaran.
            'role' => ['required', 'string', Rule::in(RolePermissions::ROLES)],
            'trainer_id' => ['nullable', 'integer', 'exists:users,id'],
            // Endurecido (Oleada 2 - seguridad): antes min:6, ahora Password::defaults()
            'password' => ['nullable', Password::defaults()],
        ]);

        $updateData = [
            'name' => $data['name'],
            'email' => $data['email'],
            'telefono' => $this->normalizarTelefono($data['telefono'] ?? null),
            'role' => $data['role'],
        ];

        // Registrar cambio de rol
        if ($oldValues['role'] !== $data['role']) {
            AuditLog::log('role_changed', "Cambió rol de {$user->name} de {$oldValues['role']} a {$data['role']}", auth()->id(), User::class, $user->id, ['role' => $oldValues['role']], ['role' => $data['role']]);
        }

        if ($data['role'] !== User::ROLE_ALUMNO) {
            $updateData['trainer_id'] = null;
        } else {
            $updateData['trainer_id'] = $data['trainer_id'] ?? null;
            if (! empty($updateData['trainer_id'])) {
                $trainer = User::findOrFail($updateData['trainer_id']);
                if (! $trainer->hasRole([User::ROLE_TRAINER, User::ROLE_ADMINISTRADOR])) {
                    return response()->json([
                        'message' => 'El usuario seleccionado no es trainer.',
                        'errors' => ['trainer_id' => ['El usuario seleccionado no es trainer.']],
                    ], 422);
                }
            }
        }

        if (! empty($data['password'])) {
            $updateData['password'] = Hash::make($data['password']);
        }

        $user->update($updateData);

        AuditLog::forModel($user, 'updated', $oldValues, $user->toArray());

        return response()->json([
            'success' => true,
            'user' => $user,
            // El frontend muestra el estado "teléfono no válido" (Fase 0.8).
            'telefono_valido' => $this->telefonoService->esValido($user->telefono),
        ]);
    }

    /**
     * Normaliza un teléfono a E.164 antes de guardarlo (Fase 0.8).
     *
     * Si el número no es normalizable (ambiguo o vacío), se guarda NULL:
     * es preferible un hueco visible que un teléfono que le pertenece a
     * otra persona o que rompe el índice único.
     */
    private function normalizarTelefono(?string $telefono): ?string
    {
        return $this->telefonoService->normalizar($telefono);
    }

    public function toggleSuspend(Request $request, int $id)
    {
        $user = User::findOrFail($id);

        if ($user->id === $request->user()->id) {
            return response()->json(['error' => 'No puedes suspender tu propia cuenta'], 403);
        }

        $this->authorize('suspend', $user);

        $data = $request->validate([
            'motivo' => ['nullable', 'string', 'max:255'],
        ]);

        $oldSuspended = $user->suspended;
        $willSuspend = ! $user->suspended;

        $user->update([
            'suspended' => $willSuspend,
            // Guardamos el motivo al suspender; lo limpiamos al reactivar.
            'motivo_suspension' => $willSuspend ? ($data['motivo'] ?? null) : null,
        ]);

        $action = $willSuspend ? 'suspended' : 'unsuspended';
        AuditLog::log(
            $action,
            "{$action} al usuario {$user->name}".($willSuspend && ! empty($data['motivo']) ? " — Motivo: {$data['motivo']}" : ''),
            auth()->id(),
            User::class,
            $user->id,
            ['suspended' => $oldSuspended, 'motivo_suspension' => $user->getOriginal('motivo_suspension')],
            ['suspended' => $user->suspended, 'motivo_suspension' => $user->motivo_suspension]
        );

        return response()->json([
            'success' => true,
            'suspended' => $user->suspended,
            'motivo_suspension' => $user->motivo_suspension,
        ]);
    }

    public function destroy(Request $request, int $id)
    {
        $user = User::findOrFail($id);

        if ($user->id === $request->user()->id) {
            return response()->json(['error' => 'No puedes eliminar tu propia cuenta'], 403);
        }

        $this->authorize('delete', $user);

        $userName = $user->name;
        $userData = $user->toArray();
        $user->delete();

        AuditLog::log('deleted', "Eliminó al usuario {$userName}", auth()->id(), User::class, $id, $userData, null);

        return response()->json(['success' => true]);
    }

    public function getTrainersAndAlumnos(Request $request)
    {
        $trainers = User::query()
            ->whereIn('role', [User::ROLE_TRAINER, User::ROLE_ADMINISTRADOR])
            ->orderBy('name')
            ->get(['id', 'name', 'nick', 'role']);

        $alumnos = User::query()
            ->where('role', User::ROLE_ALUMNO)
            ->with('trainer:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'nick', 'email', 'trainer_id']);

        return response()->json([
            'trainers' => $trainers,
            'alumnos' => $alumnos,
        ]);
    }

    public function assignAlumnosToTrainer(Request $request, int $trainerId)
    {
        $trainer = User::findOrFail($trainerId);

        if (! $trainer->hasRole([User::ROLE_TRAINER, User::ROLE_ADMINISTRADOR])) {
            return response()->json(['error' => 'El usuario seleccionado no es un entrenador válido'], 422);
        }

        $data = $request->validate([
            'alumno_ids' => ['nullable', 'array'],
            'alumno_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $alumnoIds = $data['alumno_ids'] ?? [];

        if (! empty($alumnoIds)) {
            $invalidCount = User::whereIn('id', $alumnoIds)
                ->where('role', '!=', User::ROLE_ALUMNO)
                ->count();
            if ($invalidCount > 0) {
                return response()->json(['error' => 'Uno o más usuarios seleccionados no son alumnos válidos'], 422);
            }
        }

        \DB::transaction(function () use ($trainerId, $alumnoIds) {
            User::where('trainer_id', $trainerId)
                ->whereNotIn('id', $alumnoIds)
                ->update(['trainer_id' => null]);

            if (! empty($alumnoIds)) {
                User::whereIn('id', $alumnoIds)
                    ->update(['trainer_id' => $trainerId]);
            }
        });

        $count = ! empty($alumnoIds) ? count($alumnoIds) : 0;
        AuditLog::log('assigned_trainer', "Asignó {$count} alumnos a {$trainer->name}", auth()->id(), User::class, $trainerId);

        return response()->json([
            'success' => true,
            'message' => 'Alumnos asignados correctamente al entrenador.',
        ]);
    }
}
