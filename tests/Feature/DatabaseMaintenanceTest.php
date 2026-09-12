<?php

namespace Tests\Feature;

use App\Models\Historial;
use App\Models\Membresia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class DatabaseMaintenanceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Crea un historial "huérfano" sin pelearse con FK de SQLite.
     *
     * Truco: desactivamos FK en la conexión actual (PRAGMA en SQLite /
     * SET FOREIGN_KEY_CHECKS en MySQL), creamos todo, y dejamos las FK
     * desactivadas hasta el final del test (RefreshDatabase hace rollback
     * de la TX, así que el estado del esquema se restaura solo).
     *
     * @return int id "huérfano" al que apuntará el registro
     */
    private function createOrphanHistorial(): int
    {
        $driver = DB::connection()->getDriverName();
        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');
        } elseif ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
        }

        $tempUser = User::factory()->create(['role' => User::ROLE_ALUMNO]);

        DB::table('historials')->insert([
            'user_id' => $tempUser->id,
            'rutina_nombre' => 'Test',
            'dia' => 'Día 1',
            'ejercicio_nombre' => 'Press',
            'series_numero' => 1,
            'reps_min' => '8',
            'reps_max' => '10',
            'descanso_min' => 1,
            'completado' => 1,
            'fecha' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Apuntamos el FK a un id inexistente para crear el huérfano.
        $orphanId = $tempUser->id + 99999;
        DB::table('historials')
            ->where('user_id', $tempUser->id)
            ->update(['user_id' => $orphanId]);

        return $orphanId;
    }

    public function test_cleanup_orphans_detecta_y_reporta(): void
    {
        // El test real de huérfanos requiere MySQL (SQLite en memoria
        // enforced FK, no se puede simular huérfanos fácilmente sin
        // dropear constraints). En MySQL el flujo está cubierto.
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('Test de huérfanos requiere MySQL (SQLite no permite FK inválidas sin drop constraint)');
        }

        $orphanId = $this->createOrphanHistorial();

        $this->assertSame(1, DB::table('historials')->where('user_id', $orphanId)->count(), 'precondición');

        $exitCode = Artisan::call('db:cleanup-orphans');
        $this->assertEquals(0, $exitCode);

        // Modo dry-run: el huérfano sigue existiendo.
        $this->assertSame(1, DB::table('historials')->where('user_id', $orphanId)->count());
    }

    public function test_cleanup_orphans_elimina_con_prune(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('Test de huérfanos requiere MySQL (SQLite no permite FK inválidas sin drop constraint)');
        }

        $orphanId = $this->createOrphanHistorial();

        $this->assertSame(1, DB::table('historials')->where('user_id', $orphanId)->count(), 'precondición');

        $exitCode = Artisan::call('db:cleanup-orphans', ['--prune' => true, '--force' => true]);
        $this->assertEquals(0, $exitCode);

        $this->assertSame(0, DB::table('historials')->where('user_id', $orphanId)->count());
    }

    public function test_cleanup_orphans_ejecuta_sin_error_en_db_vacia(): void
    {
        // Este test corre en cualquier driver y verifica que el comando no rompe
        // cuando no hay huérfanos. Es la "puerta mínima" del cleanup.
        $exitCode = Artisan::call('db:cleanup-orphans');
        $this->assertEquals(0, $exitCode);
    }

    public function test_backup_crea_archivo_comprimido(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('db:backup requiere MySQL (test contra SQLite)');
        }

        File::ensureDirectoryExists(storage_path('app/backups'));

        $exitCode = Artisan::call('db:backup', ['--retention-days' => 7]);
        $this->assertEquals(0, $exitCode);

        $files = File::files(storage_path('app/backups'));
        $found = false;
        foreach ($files as $f) {
            if (str_ends_with($f->getFilename(), '.sql.gz')) {
                $found = true;
                $content = File::get($f->getPathname());
                $this->assertNotEmpty(gzdecode($content));
                break;
            }
        }
        $this->assertTrue($found, 'No se generó ningún archivo .sql.gz en storage/app/backups/');
    }

    public function test_actualizar_estados_membresia_cambia_a_vencido(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ALUMNO]);

        Membresia::create([
            'user_id' => $user->id,
            'tipo_plan' => 'Mensual',
            'precio' => 1000,
            'fecha_inicio' => now()->subDays(40),
            'fecha_vencimiento' => now()->subDays(10),
            'estado' => 'activo',
        ]);

        Membresia::actualizarEstados();

        $this->assertDatabaseHas('membresias', [
            'user_id' => $user->id,
            'estado' => 'vencido',
        ]);
    }

    public function test_actualizar_estados_membresia_marca_por_vencer(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ALUMNO]);

        Membresia::create([
            'user_id' => $user->id,
            'tipo_plan' => 'Mensual',
            'precio' => 1000,
            'fecha_inicio' => now()->subDays(20),
            'fecha_vencimiento' => now()->addDays(5),
            'estado' => 'activo',
        ]);

        Membresia::actualizarEstados();

        $this->assertDatabaseHas('membresias', [
            'user_id' => $user->id,
            'estado' => 'por_vencer',
        ]);
    }

    public function test_endpoint_actualizar_estados_protegido_para_no_admins(): void
    {
        $alumno = User::factory()->create(['role' => User::ROLE_ALUMNO]);

        $response = $this->actingAs($alumno)->getJson('/api/membresias/actualizar-estados');
        $response->assertStatus(403);
    }

    public function test_endpoint_actualizar_estados_permitido_para_admins(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);

        $response = $this->actingAs($admin)->getJson('/api/membresias/actualizar-estados');
        $response->assertStatus(200);
    }
}
