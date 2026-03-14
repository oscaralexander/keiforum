<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class ConversationLastReadTest extends TestCase
{
    use RefreshDatabase;

    public function test_last_read_at_is_updated_when_viewing_conversation(): void
    {
        $user = User::factory()->create(['last_seen_at' => now()]);
        $other = User::factory()->create(['last_seen_at' => now()]);
        $conversation = Conversation::firstOrCreateForParticipants([$user->id, $other->id]);

        Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $other->id,
            'body' => 'Hello!',
        ]);

        $this->assertNull(
            $conversation->users()->where('user_id', $user->id)->first()->pivot->last_read_at
        );

        $this->freezeTime(function () use ($user, $conversation) {
            Livewire::actingAs($user)
                ->test('conversation', ['conversationId' => $conversation->id]);

            $this->assertEquals(
                now()->toDateTimeString(),
                $conversation->users()->where('user_id', $user->id)->first()->pivot->last_read_at
            );
        });
    }

    public function test_last_read_at_is_updated_when_sending_a_message(): void
    {
        Mail::fake();

        $user = User::factory()->create(['last_seen_at' => now()]);
        $other = User::factory()->create(['last_seen_at' => now()]);
        $conversation = Conversation::firstOrCreateForParticipants([$user->id, $other->id]);

        $this->freezeTime(function () use ($user, $conversation) {
            Livewire::actingAs($user)
                ->test('conversation', ['conversationId' => $conversation->id])
                ->set('body', 'Hello!')
                ->call('submit');

            $this->assertEquals(
                now()->toDateTimeString(),
                $conversation->users()->where('user_id', $user->id)->first()->pivot->last_read_at
            );
        });
    }

    public function test_last_read_at_is_not_updated_for_other_participants(): void
    {
        $user = User::factory()->create(['last_seen_at' => now()]);
        $other = User::factory()->create(['last_seen_at' => now()]);
        $conversation = Conversation::firstOrCreateForParticipants([$user->id, $other->id]);

        Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'body' => 'Hello!',
        ]);

        Livewire::actingAs($user)
            ->test('conversation', ['conversationId' => $conversation->id]);

        $this->assertNull(
            $conversation->users()->where('user_id', $other->id)->first()->pivot->last_read_at
        );
    }
}
