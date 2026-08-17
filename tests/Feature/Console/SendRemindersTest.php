<?php

namespace Tests\Feature\Console;

use App\Models\Membresia;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SendRemindersTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_notification_for_membership_expiring_in_7_days(): void
    {
        $user = User::factory()->create();
        Membresia::create([
            'user_id' => $user->id,
            'tipo_plan' => 'mensual',
            'precio' => 1000,
            'fecha_inicio' => Carbon::today()->subDays(23),
            'fecha_vencimiento' => Carbon::today()->addDays(7),
            'estado' => 'activo',
        ]);

        $this->artisan('reminders:send')->assertSuccessful();

        $notif = Notification::forUser($user->id)->first();
        $this->assertNotNull($notif);
        $this->assertEquals('membership_expiring', $notif->type);
        $this->assertEquals(7, $notif->data['dias_restantes']);
    }

    public function test_creates_notification_for_membership_expiring_in_3_days(): void
    {
        $user = User::factory()->create();
        Membresia::create([
            'user_id' => $user->id,
            'tipo_plan' => 'mensual',
            'precio' => 1000,
            'fecha_inicio' => Carbon::today()->subDays(27),
            'fecha_vencimiento' => Carbon::today()->addDays(3),
            'estado' => 'activo',
        ]);

        $this->artisan('reminders:send')->assertSuccessful();

        $notif = Notification::forUser($user->id)->first();
        $this->assertNotNull($notif);
        $this->assertEquals(3, $notif->data['dias_restantes']);
    }

    public function test_does_not_duplicate_notification_same_day(): void
    {
        $user = User::factory()->create();
        Membresia::create([
            'user_id' => $user->id,
            'tipo_plan' => 'mensual',
            'precio' => 1000,
            'fecha_inicio' => Carbon::today()->subDays(23),
            'fecha_vencimiento' => Carbon::today()->addDays(7),
            'estado' => 'activo',
        ]);

        $this->artisan('reminders:send');
        $this->artisan('reminders:send');

        $count = Notification::forUser($user->id)->count();
        $this->assertEquals(1, $count);
    }

    public function test_does_not_notify_users_without_active_membership(): void
    {
        $user = User::factory()->create();
        // sin membresía

        $this->artisan('reminders:send')->assertSuccessful();

        $this->assertEquals(0, Notification::forUser($user->id)->count());
    }

    public function test_creates_inactive_reminder_for_users_without_recent_workouts(): void
    {
        $user = User::factory()->create();
        Membresia::create([
            'user_id' => $user->id,
            'tipo_plan' => 'mensual',
            'precio' => 1000,
            'fecha_inicio' => Carbon::today()->subDays(30),
            'fecha_vencimiento' => Carbon::today()->addDays(30),
            'estado' => 'activo',
        ]);
        // El user no tiene historiales recientes (más de 5 días)

        $this->artisan('reminders:send')->assertSuccessful();

        $notif = Notification::forUser($user->id)
            ->where('type', 'inactive_reminder')
            ->first();
        $this->assertNotNull($notif);
    }

    public function test_does_not_create_inactive_reminder_for_active_users(): void
    {
        $user = User::factory()->create();
        Membresia::create([
            'user_id' => $user->id,
            'tipo_plan' => 'mensual',
            'precio' => 1000,
            'fecha_inicio' => Carbon::today()->subDays(30),
            'fecha_vencimiento' => Carbon::today()->addDays(30),
            'estado' => 'activo',
        ]);
        // El user entrenó ayer
        \App\Models\Historial::create([
            'user_id' => $user->id,
            'fecha' => Carbon::yesterday()->toDateString(),
            'ejercicio_nombre' => 'Press banca',
            'rutina_nombre' => 'Push',
            'dia' => 'Día 1',
            'series_numero' => 1,
            'reps_min' => '8',
            'reps_max' => '12',
            'descanso_min' => 2,
        ]);

        $this->artisan('reminders:send')->assertSuccessful();

        $notif = Notification::forUser($user->id)
            ->where('type', 'inactive_reminder')
            ->first();
        $this->assertNull($notif);
    }
}
