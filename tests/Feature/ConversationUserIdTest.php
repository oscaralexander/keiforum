<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationUserIdTest extends TestCase
{
    use RefreshDatabase;

    public function testConversationStoresCreatorId(): void
    {
        $creator = User::factory()->create();
        $other = User::factory()->create();

        $conversation = Conversation::firstOrCreateForParticipants(
            [$creator->id, $other->id],
            $creator->id,
        );

        $this->assertEquals($creator->id, $conversation->user_id);
        $this->assertTrue($conversation->user->is($creator));
    }

    public function testExistingConversationIsReturnedWithoutOverwritingCreator(): void
    {
        $creator = User::factory()->create();
        $other = User::factory()->create();

        $original = Conversation::firstOrCreateForParticipants(
            [$creator->id, $other->id],
            $creator->id,
        );

        $found = Conversation::firstOrCreateForParticipants(
            [$creator->id, $other->id],
            $other->id,
        );

        $this->assertTrue($found->is($original));
        $this->assertEquals($creator->id, $found->user_id);
    }

    public function testConversationCreatorIdIsNullableByDefault(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $conversation = Conversation::firstOrCreateForParticipants(
            [$userA->id, $userB->id],
        );

        $this->assertNull($conversation->user_id);
    }
}
