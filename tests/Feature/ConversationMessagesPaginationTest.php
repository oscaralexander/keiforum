<?php

namespace Tests\Feature;

use App\Events\MessageCreated;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Tests\TestCase;

class ConversationMessagesPaginationTest extends TestCase
{
    use RefreshDatabase;

    private function createConversationWithMessageCount(User $user, int $count): Conversation
    {
        $other = User::factory()->create();
        $conversation = Conversation::firstOrCreateForParticipants([$user->id, $other->id]);

        for ($i = 0; $i < $count; $i++) {
            Message::create([
                'conversation_id' => $conversation->id,
                'user_id' => $user->id,
                'body' => "Message {$i}",
                'created_at' => now()->subMinutes($count - $i),
            ]);
        }

        return $conversation;
    }

    public function testShowsOnly25MessagesByDefault(): void
    {
        $user = User::factory()->create();
        $conversation = $this->createConversationWithMessageCount($user, 30);

        $component = Livewire::actingAs($user)
            ->test('pages::messages.index', ['conversation' => $conversation]);

        $component->assertSet('messagesLimit', 25);

        $messages = $component->get('conversationMessages');
        $this->assertCount(25, $messages);
    }

    public function testShowsAllMessagesWhenLessThan25(): void
    {
        $user = User::factory()->create();
        $conversation = $this->createConversationWithMessageCount($user, 10);

        $component = Livewire::actingAs($user)
            ->test('pages::messages.index', ['conversation' => $conversation]);

        $messages = $component->get('conversationMessages');
        $this->assertCount(10, $messages);
    }

    public function testHasMoreMessagesReturnsTrueWhenMoreExist(): void
    {
        $user = User::factory()->create();
        $conversation = $this->createConversationWithMessageCount($user, 30);

        $component = Livewire::actingAs($user)
            ->test('pages::messages.index', ['conversation' => $conversation]);

        $this->assertTrue($component->get('hasMoreMessages'));
    }

    public function testHasMoreMessagesReturnsFalseWhenAllLoaded(): void
    {
        $user = User::factory()->create();
        $conversation = $this->createConversationWithMessageCount($user, 20);

        $component = Livewire::actingAs($user)
            ->test('pages::messages.index', ['conversation' => $conversation]);

        $this->assertFalse($component->get('hasMoreMessages'));
    }

    public function testLoadMoreMessagesIncreasesLimit(): void
    {
        $user = User::factory()->create();
        $conversation = $this->createConversationWithMessageCount($user, 60);

        $component = Livewire::actingAs($user)
            ->test('pages::messages.index', ['conversation' => $conversation]);

        $component->call('loadMoreMessages');
        $component->assertSet('messagesLimit', 50);

        $messages = $component->get('conversationMessages');
        $this->assertCount(50, $messages);
    }

    public function testMessagesAreInChronologicalOrder(): void
    {
        $user = User::factory()->create();
        $conversation = $this->createConversationWithMessageCount($user, 5);

        $component = Livewire::actingAs($user)
            ->test('pages::messages.index', ['conversation' => $conversation]);

        $messages = $component->get('conversationMessages');
        for ($i = 1; $i < $messages->count(); $i++) {
            $this->assertTrue(
                $messages[$i]->created_at->gte($messages[$i - 1]->created_at),
                'Messages should be in chronological order'
            );
        }
    }

    public function testSubmitReplyDispatchesMessageSentEvent(): void
    {
        Event::fake([MessageCreated::class]);

        $user = User::factory()->create();
        $conversation = $this->createConversationWithMessageCount($user, 1);

        Livewire::actingAs($user)
            ->test('pages::messages.index', ['conversation' => $conversation])
            ->set('replyBody', 'Test reply')
            ->call('submitReply')
            ->assertDispatched('message-sent');
    }

    public function testLoadMoreButtonVisibleWhenMoreMessagesExist(): void
    {
        $user = User::factory()->create();
        $conversation = $this->createConversationWithMessageCount($user, 30);

        $this->actingAs($user)
            ->get(route('conversations', $conversation))
            ->assertSee(__('messages/index.load_more'));
    }

    public function testLoadMoreButtonHiddenWhenAllMessagesLoaded(): void
    {
        $user = User::factory()->create();
        $conversation = $this->createConversationWithMessageCount($user, 10);

        $this->actingAs($user)
            ->get(route('conversations', $conversation))
            ->assertDontSee(__('messages/index.load_more'));
    }
}
