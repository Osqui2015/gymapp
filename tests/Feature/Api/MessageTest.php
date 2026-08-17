<?php

namespace Tests\Feature\Api;

use App\Models\Message;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessageTest extends TestCase
{
    use RefreshDatabase;

    protected User $trainer;
    protected User $alumno;

    protected function setUp(): void
    {
        parent::setUp();
        $this->trainer = User::factory()->create(['role' => User::ROLE_TRAINER]);
        $this->alumno = User::factory()->create([
            'role' => User::ROLE_ALUMNO,
            'trainer_id' => $this->trainer->id,
        ]);
    }

    public function test_user_can_send_message(): void
    {
        $response = $this->actingAs($this->trainer)
            ->postJson('/api/messages', [
                'recipient_id' => $this->alumno->id,
                'body' => 'Hola! Buen trabajo esta semana',
            ]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['data' => ['id', 'body', 'sender']]);

        $this->assertDatabaseHas('messages', [
            'sender_id' => $this->trainer->id,
            'recipient_id' => $this->alumno->id,
            'body' => 'Hola! Buen trabajo esta semana',
        ]);
    }

    public function test_send_message_creates_notification_for_recipient(): void
    {
        $this->actingAs($this->trainer)
            ->postJson('/api/messages', [
                'recipient_id' => $this->alumno->id,
                'body' => 'Test notif',
            ])->assertStatus(201);

        $notif = Notification::forUser($this->alumno->id)->first();
        $this->assertNotNull($notif);
        $this->assertEquals('message', $notif->type);
    }

    public function test_user_cannot_send_message_to_themselves(): void
    {
        $this->actingAs($this->trainer)
            ->postJson('/api/messages', [
                'recipient_id' => $this->trainer->id,
                'body' => 'narcisista',
            ])
            ->assertStatus(400);
    }

    public function test_message_requires_valid_recipient(): void
    {
        $this->actingAs($this->trainer)
            ->postJson('/api/messages', [
                'recipient_id' => 99999,
                'body' => 'Hola fantasma',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('recipient_id');
    }

    public function test_message_body_is_required(): void
    {
        $this->actingAs($this->trainer)
            ->postJson('/api/messages', [
                'recipient_id' => $this->alumno->id,
                'body' => '',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('body');
    }

    public function test_user_can_list_conversation_messages(): void
    {
        Message::create(['sender_id' => $this->trainer->id, 'recipient_id' => $this->alumno->id, 'body' => 'msg 1']);
        Message::create(['sender_id' => $this->alumno->id, 'recipient_id' => $this->trainer->id, 'body' => 'msg 2']);
        Message::create(['sender_id' => $this->trainer->id, 'recipient_id' => $this->alumno->id, 'body' => 'msg 3']);

        $response = $this->actingAs($this->trainer)
            ->getJson("/api/messages/with/{$this->alumno->id}");

        $response->assertOk();
        $messages = $response->json('data');
        $this->assertCount(3, $messages);
        $this->assertEquals('msg 1', $messages[0]['body']);
        $this->assertEquals('msg 3', $messages[2]['body']);
    }

    public function test_listing_conversation_marks_incoming_as_read(): void
    {
        Message::create(['sender_id' => $this->trainer->id, 'recipient_id' => $this->alumno->id, 'body' => 'hola']);
        Message::create(['sender_id' => $this->trainer->id, 'recipient_id' => $this->alumno->id, 'body' => 'cómo estás?']);

        // Antes de leer: ambos están unread
        $unreadBefore = Message::unreadFor($this->alumno->id)->count();
        $this->assertEquals(2, $unreadBefore);

        // Alumno lista la conversación
        $this->actingAs($this->alumno)
            ->getJson("/api/messages/with/{$this->trainer->id}")
            ->assertOk();

        // Después: 0 unread (los del trainer al alumno)
        $unreadAfter = Message::unreadFor($this->alumno->id)
            ->where('sender_id', $this->trainer->id)
            ->count();
        $this->assertEquals(0, $unreadAfter);
    }

    public function test_user_can_list_their_conversations(): void
    {
        $otroAlumno = User::factory()->create(['role' => User::ROLE_ALUMNO]);
        Message::create(['sender_id' => $this->trainer->id, 'recipient_id' => $this->alumno->id, 'body' => 'a']);
        Message::create(['sender_id' => $this->trainer->id, 'recipient_id' => $otroAlumno->id, 'body' => 'b']);

        $response = $this->actingAs($this->trainer)
            ->getJson('/api/messages/conversations');

        $response->assertOk();
        $conversations = $response->json('conversations');
        $this->assertCount(2, $conversations);
    }

    public function test_user_can_mark_message_as_read(): void
    {
        $msg = Message::create([
            'sender_id' => $this->trainer->id,
            'recipient_id' => $this->alumno->id,
            'body' => 'leeme',
        ]);

        $this->actingAs($this->alumno)
            ->postJson("/api/messages/{$msg->id}/read")
            ->assertOk();

        $this->assertNotNull($msg->fresh()->read_at);
    }

    public function test_user_cannot_mark_others_message_as_read(): void
    {
        $msg = Message::create([
            'sender_id' => $this->trainer->id,
            'recipient_id' => $this->alumno->id,
            'body' => 'privado',
        ]);

        $this->actingAs($this->trainer) // el sender, no el recipient
            ->postJson("/api/messages/{$msg->id}/read")
            ->assertStatus(403);
    }

    public function test_mark_all_read_updates_only_unread(): void
    {
        $msg1 = Message::create(['sender_id' => $this->trainer->id, 'recipient_id' => $this->alumno->id, 'body' => 'a']);
        $msg2 = Message::create(['sender_id' => $this->trainer->id, 'recipient_id' => $this->alumno->id, 'body' => 'b']);
        $msg2->update(['read_at' => now()]);

        $response = $this->actingAs($this->alumno)
            ->postJson("/api/messages/with/{$this->trainer->id}/read-all");

        $response->assertJson(['updated' => 1]); // solo msg1 era unread
    }
}
