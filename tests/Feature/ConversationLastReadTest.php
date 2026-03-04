<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationLastReadTest extends TestCase
{
    use RefreshDatabase;

    public function testLastReadAtIsUpdatedWhenViewingConversation(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
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
            $this->actingAs($user)
                ->get(route('conversations', $conversation))
                ->assertOk();

            $this->assertEquals(
                now()->toDateTimeString(),
                $conversation->users()->where('user_id', $user->id)->first()->pivot->last_read_at
            );
        });
    }

    public function testLastReadAtIsNotUpdatedForOtherParticipants(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $conversation = Conversation::firstOrCreateForParticipants([$user->id, $other->id]);

        Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'body' => 'Hello!',
        ]);

        $this->actingAs($user)
            ->get(route('conversations', $conversation))
            ->assertOk();

        $this->assertNull(
            $conversation->users()->where('user_id', $other->id)->first()->pivot->last_read_at
        );
    }
}
