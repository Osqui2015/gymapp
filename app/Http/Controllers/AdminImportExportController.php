<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Ejercicio;
use App\Models\User;
use App\Support\RolePermissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminImportExportController extends Controller
{
    public function exportUsers()
    {
        $users = User::select(['id', 'name', 'nick', 'email', 'role', 'telefono', 'trainer_id', 'suspended', 'created_at'])
            ->orderBy('name')
            ->get();

        $csv = $this->arrayToCsv([
            ['ID', 'Nombre', 'Nick', 'Email', 'Rol', 'Teléfono', 'Trainer ID', 'Suspendido', 'Fecha Registro'],
            ...$users->map(fn ($u) => [
                $u->id,
                $u->name,
                $u->nick,
                $u->email,
                $u->role,
                $u->telefono,
                $u->trainer_id,
                $u->suspended ? 'Sí' : 'No',
                $u->created_at->format('Y-m-d'),
            ])->toArray(),
        ]);

        return response()->streamDownload(
            fn () => print ($csv),
            'usuarios_export_'.now()->format('Y-m-d').'.csv',
            ['Content-Type' => 'text/csv']
        );
    }

    public function exportEjercicios()
    {
        $ejercicios = Ejercicio::select(['id', 'nombre', 'grupo_muscular', 'equipamiento', 'visibilidad', 'descripcion'])
            ->orderBy('grupo_muscular')
            ->orderBy('nombre')
            ->get();

        $csv = $this->arrayToCsv([
            ['ID', 'Nombre', 'Grupo Muscular', 'Equipamiento', 'Visible', 'Descripción'],
            ...$ejercicios->map(fn ($e) => [
                $e->id,
                $e->nombre,
                $e->grupo_muscular,
                $e->equipamiento,
                $e->visibilidad ? 'Sí' : 'No',
                $e->descripcion,
            ])->toArray(),
        ]);

        return response()->streamDownload(
            fn () => print ($csv),
            'ejercicios_export_'.now()->format('Y-m-d').'.csv',
            ['Content-Type' => 'text/csv']
        );
    }

    /**
     * Importa usuarios desde un CSV.
     *
     * Correcciones aplicadas (P0):
     *  - Límite de tamaño de archivo y de filas (evita DoS por CSV gigante).
     *  - El email queda VERIFICADO (`email_verified_at`): sin esto, el
     *    middleware `verified` de `/dashboard` bloqueaba a todos los
     *    importados y no podían entrar nunca.
     *  - La contraseña temporal se DEVUELVE en la respuesta para que el admin
     *    la comparta. Antes se generaba una aleatoria y se perdía: la cuenta
     *    quedaba inutilizable.
     *  - El mapa de roles acepta TODOS los roles de `RolePermissions::ROLES`
     *    (antes solo admin/trainer/alumno/comun: coordinador y recepcionista
     *    caían silenciosamente en 'comun').
     */
    public function importUsers(Request $request)
    {
        $validated = $request->validate([
            'archivo' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $file = $request->file('archivo');
        $handle = fopen($file->getRealPath(), 'r');

        $headers = fgetcsv($handle);
        $rowCount = 0;
        $errors = [];
        $created = 0;

        // Contraseñas temporales generadas, para devolverlas al admin.
        $temporales = [];

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle)) !== false) {
                $rowCount++;

                // Tope de filas: evita que un CSV enorme se procese entero
                // (y bloquee la transacción) por un solo request.
                if ($rowCount > self::MAX_FILAS_IMPORTACION) {
                    $errors[] = "Se alcanzó el límite de ".self::MAX_FILAS_IMPORTACION.' filas; el resto se ignoró.';

                    break;
                }

                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }

                try {
                    $data = $this->mapRowToUsers($headers, $row);

                    if (empty($data['name']) || empty($data['email'])) {
                        $errors[] = "Fila {$rowCount}: Nombre y email son requeridos";

                        continue;
                    }

                    // Rol desconocido: se rechaza la fila en vez de crear un
                    // usuario con un rol arbitrario.
                    if (isset($data['_rol_invalido'])) {
                        $errors[] = "Fila {$rowCount}: Rol '{$data['_rol_invalido']}' no válido (".implode(', ', RolePermissions::ROLES).')';

                        continue;
                    }

                    unset($data['_rol_invalido']);

                    // El rol por defecto es 'comun'.
                    $data['role'] = $data['role'] ?? User::ROLE_COMUN;

                    // Generar nick si no existe
                    if (empty($data['nick'])) {
                        $data['nick'] = strtolower(str_replace(' ', '.', $data['name'])).$rowCount;
                    }

                    // Contraseña temporal: se genera, se devuelve al admin para
                    // que la comparta y se le pide al usuario que la cambie.
                    // Antes era aleatoria y se perdía -> cuenta inutilizable.
                    $passwordTemporal = Str::random(12);

                    $usuario = User::create([
                        ...$data,
                        'password' => Hash::make($passwordTemporal),
                        // Marcado como verificado: el middleware `verified` de
                        // /dashboard rechazaba a los importados y no podían
                        // entrar nunca.
                        'email_verified_at' => now(),
                    ]);

                    $temporales[] = [
                        'nick' => $usuario->nick,
                        'email' => $usuario->email,
                        'password_temporal' => $passwordTemporal,
                    ];

                    $created++;
                } catch (\Exception $e) {
                    $errors[] = "Fila {$rowCount}: ".$e->getMessage();
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'error' => 'Error al procesar el archivo: '.$e->getMessage(),
                'errors' => $errors,
            ], 422);
        }

        AuditLog::log('import', "Importó {$created} usuarios desde CSV", auth()->id());

        return response()->json([
            'success' => true,
            'created' => $created,
            'processed' => $rowCount,
            'errors' => $errors,
            // Contraseños temporales para que el admin las comparta.
            'passwords_temporales' => $temporales,
        ]);
    }

    public function importEjercicios(Request $request)
    {
        $validated = $request->validate([
            'archivo' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $file = $request->file('archivo');
        $handle = fopen($file->getRealPath(), 'r');

        $headers = fgetcsv($handle);
        $rowCount = 0;
        $errors = [];
        $created = 0;

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle)) !== false) {
                $rowCount++;

                // Tope de filas (protección contra DoS).
                if ($rowCount > self::MAX_FILAS_IMPORTACION) {
                    $errors[] = "Se alcanzó el límite de ".self::MAX_FILAS_IMPORTACION.' filas; el resto se ignoró.';

                    break;
                }

                if (empty(array_filter($row))) {
                    continue;
                }

                try {
                    $data = $this->mapRowToEjercicios($headers, $row);

                    if (empty($data['nombre']) || empty($data['grupo_muscular'])) {
                        $errors[] = "Fila {$rowCount}: Nombre y grupo muscular son requeridos";

                        continue;
                    }

                    Ejercicio::create($data);
                    $created++;
                } catch (\Exception $e) {
                    $errors[] = "Fila {$rowCount}: ".$e->getMessage();
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'error' => 'Error al procesar el archivo: '.$e->getMessage(),
                'errors' => $errors,
            ], 422);
        }

        AuditLog::log('import', "Importó {$created} ejercicios desde CSV", auth()->id());

        return response()->json([
            'success' => true,
            'created' => $created,
            'processed' => $rowCount,
            'errors' => $errors,
        ]);
    }

    /**
     * Tope de filas por importación (protección contra DoS).
     */
    public const MAX_FILAS_IMPORTACION = 5000;

    /**
     * Normaliza el valor de la columna "Rol" del CSV a un rol válido.
     *
     * Antes solo aceptaba admin/trainer/alumno/comun: `coordinador` y
     * `recepcionista` caían silenciosamente en 'comun'. Ahora se mapean
     * TODOS los roles de `RolePermissions::ROLES`, y un valor desconocido
     * devuelve null (el caller lo reporta como error en vez de crear un
     * usuario con permisos que nadie pidió).
     *
     * @return string|null Rol normalizado, o null si no se reconoce.
     */
    private function normalizarRol(?string $valor): ?string
    {
        $v = strtolower(trim((string) $valor));

        if ($v === '') {
            return User::ROLE_COMUN;
        }

        $alias = [
            'admin' => User::ROLE_ADMINISTRADOR,
            'administrador' => User::ROLE_ADMINISTRADOR,
            'entrenador' => User::ROLE_TRAINER,
            'trainer' => User::ROLE_TRAINER,
            'student' => User::ROLE_ALUMNO,
            'alumno' => User::ROLE_ALUMNO,
            'comun' => User::ROLE_COMUN,
            'coordinador' => User::ROLE_COORDINADOR,
            'recepcionista' => User::ROLE_RECEPCIONISTA,
        ];

        if (isset($alias[$v])) {
            return $alias[$v];
        }

        // Rol canónico escrito tal cual (p. ej. 'administrador').
        if (in_array($v, RolePermissions::ROLES, true)) {
            return $v;
        }

        return null;
    }

    private function mapRowToUsers(array $headers, array $row): array
    {
        $data = [];
        foreach ($headers as $i => $header) {
            $key = strtolower(trim($header));
            $value = trim($row[$i] ?? '');

            if (empty($value)) {
                continue;
            }

            switch ($key) {
                case 'nombre':
                case 'name':
                    $data['name'] = $value;
                    break;
                case 'nick':
                    $data['nick'] = $value;
                    break;
                case 'email':
                    $data['email'] = $value;
                    break;
                case 'rol':
                case 'role':
                    $rol = $this->normalizarRol($value);

                    if ($rol === null) {
                        // Rol desconocido: se marca para reportarlo, pero NO
                        // se asigna un rol arbitrario (que podría darle
                        // permisos de admin sin querer).
                        $data['_rol_invalido'] = $value;
                    } else {
                        $data['role'] = $rol;
                    }
                    break;
                case 'teléfono':
                case 'telefono':
                    $data['telefono'] = $value;
                    break;
                case 'trainer id':
                case 'trainer_id':
                    $data['trainer_id'] = is_numeric($value) ? (int) $value : null;
                    break;
            }
        }

        return $data;
    }

    private function mapRowToEjercicios(array $headers, array $row): array
    {
        $data = [];
        foreach ($headers as $i => $header) {
            $key = strtolower(trim($header));
            $value = trim($row[$i] ?? '');

            if (empty($value)) {
                continue;
            }

            switch ($key) {
                case 'nombre':
                    $data['nombre'] = $value;
                    break;
                case 'grupo muscular':
                case 'grupo_muscular':
                    $data['grupo_muscular'] = $value;
                    break;
                case 'equipamiento':
                    $data['equipamiento'] = $value;
                    break;
                case 'visible':
                    $data['visibilidad'] = in_array(strtolower($value), ['sí', 'si', 'yes', '1', 'true']);
                    break;
                case 'descripción':
                case 'descripcion':
                    $data['descripcion'] = $value;
                    break;
            }
        }

        return $data;
    }

    private function arrayToCsv(array $data): string
    {
        $output = fopen('php://temp', 'r+');

        foreach ($data as $row) {
            fputcsv($output, $row);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }
}
