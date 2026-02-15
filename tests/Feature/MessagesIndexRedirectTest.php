<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessagesIndexRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function testRedirectsToLatestConversation(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $older = Conversation::firstOrCreateForParticipants([$user->id, $other->id]);
        Message::create([
            'conversation_id' => $older->id,
            'user_id' => $other->id,
            'body' => 'Old message',
            'created_at' => now()->subDay(),
        ]);

        $another = User::factory()->create();
        $newer = Conversation::firstOrCreateForParticipants([$user->id, $another->id]);
        Message::create([
            'conversation_id' => $newer->id,
            'user_id' => $another->id,
            'body' => 'New message',
            'created_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('messages'))
            ->assertRedirect(route('messages', $newer));
    }

    public function testShowsNewConversationFormWhenNoConversationsExist(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('messages'))
            ->assertOk();
    }

    public function testDoesNotRedirectWhenConversationIsProvided(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $conversation = Conversation::firstOrCreateForParticipants([$user->id, $other->id]);

        Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $other->id,
            'body' => 'Hello!',
        ]);

        $this->actingAs($user)
            ->get(route('messages', $conversation))
            ->assertOk();
    }

    public function testCreateRouteShowsComposeForm(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $conversation = Conversation::firstOrCreateForParticipants([$user->id, $other->id]);
        Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $other->id,
            'body' => 'Hello!',
        ]);

        $this->actingAs($user)
            ->get(route('messages.create'))
            ->assertOk();
    }
}
