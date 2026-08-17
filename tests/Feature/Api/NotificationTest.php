<?php

namespace Tests\Feature\Api;

use App\Models\Notification;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->trainer = User::factory()->create(['role' => User::ROLE_TRAINER]);
        $this->alumno = User::factory()->create(['role' => User::ROLE_ALUMNO]);
    }

    public function test_user_can_list_their_notifications(): void
    {
        // Crear 2 notifs para el alumno, 1 para otro user
        app(NotificationService::class)->notify($this->alumno, 'trainer_comment', 'Titulo 1', 'Body 1');
        app(NotificationService::class)->notify($this->alumno, 'milestone', 'Titulo 2', 'Body 2');
        app(NotificationService::class)->notify($this->trainer, 'milestone', 'X', 'Y');

        $response = $this->actingAs($this->alumno)->getJson('/api/notifications');

        $response->assertOk();
        $body = $response->json();
        $data = $body['data'] ?? $body;
        $this->assertCount(2, $data);
    }

    public function test_user_only_sees_their_own_notifications(): void
    {
        app(NotificationService::class)->notify($this->trainer, 'milestone', 'X', 'Y');

        $response = $this->actingAs($this->alumno)->getJson('/api/notifications');

        $response->assertOk();
        $body = $response->json();
        $data = $body['data'] ?? $body;
        $this->assertCount(0, $data);
    }

    public function test_unread_only_filter(): void
    {
        $n1 = app(NotificationService::class)->notify($this->alumno, 'a', 'A', 'A');
        $n2 = app(NotificationService::class)->notify($this->alumno, 'b', 'B', 'B');
        $n2->update(['read_at' => now()]);

        $response = $this->actingAs($this->alumno)
            ->getJson('/api/notifications?unread_only=1');

        $response->assertOk();
        $body = $response->json();
        $data = $body['data'] ?? $body;
        $this->assertCount(1, $data);
        $this->assertEquals($n1->id, $data[0]['id']);
    }

    public function test_user_can_mark_a_notification_as_read(): void
    {
        $notif = app(NotificationService::class)->notify($this->alumno, 'a', 'A', 'A');
        $this->assertNull($notif->read_at);

        $response = $this->actingAs($this->alumno)
            ->postJson("/api/notifications/{$notif->id}/read");

        $response->assertOk();
        $this->assertNotNull($notif->fresh()->read_at);
    }

    public function test_user_cannot_mark_other_users_notification(): void
    {
        $notif = app(NotificationService::class)->notify($this->trainer, 'a', 'A', 'A');

        $response = $this->actingAs($this->alumno)
            ->postJson("/api/notifications/{$notif->id}/read");

        $response->assertStatus(404);
    }

    public function test_user_can_mark_all_as_read(): void
    {
        app(NotificationService::class)->notify($this->alumno, 'a', 'A', 'A');
        app(NotificationService::class)->notify($this->alumno, 'b', 'B', 'B');

        $response = $this->actingAs($this->alumno)
            ->postJson('/api/notifications/read-all');

        $response->assertOk();
        $response->assertJson(['updated' => 2]);
        $this->assertEquals(0, Notification::forUser($this->alumno->id)->unread()->count());
    }

    public function test_user_can_delete_their_notification(): void
    {
        $notif = app(NotificationService::class)->notify($this->alumno, 'a', 'A', 'A');

        $response = $this->actingAs($this->alumno)
            ->deleteJson("/api/notifications/{$notif->id}");

        $response->assertOk();
        $this->assertNull(Notification::find($notif->id));
    }

    public function test_trainer_comment_creates_a_notification_for_the_alumno(): void
    {
        $response = $this->actingAs($this->trainer)
            ->postJson('/api/trainer-comments', [
                'alumno_id' => $this->alumno->id,
                'body' => 'Excelente progreso esta semana',
            ]);

        $response->assertStatus(201);

        $notifs = Notification::forUser($this->alumno->id)->get();
        $this->assertCount(1, $notifs);
        $this->assertEquals('trainer_comment', $notifs->first()->type);
        $this->assertEquals('Excelente progreso esta semana', $notifs->first()->data['body']);
    }

    public function test_unauthenticated_user_cannot_access_notifications(): void
    {
        $this->getJson('/api/notifications')->assertStatus(401);
        $this->postJson('/api/notifications/abc/read')->assertStatus(401);
        $this->postJson('/api/notifications/read-all')->assertStatus(401);
    }
}
