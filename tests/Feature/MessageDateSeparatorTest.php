<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessageDateSeparatorTest extends TestCase
{
    use RefreshDatabase;

    private function createConversationWithMessages(User $user, array $dates): Conversation
    {
        $other = User::factory()->create(['username' => 'other_' . fake()->unique()->word()]);
        $conversation = Conversation::firstOrCreateForParticipants([$user->id, $other->id]);

        foreach ($dates as $date) {
            Message::create([
                'conversation_id' => $conversation->id,
                'user_id' => $user->id,
                'body' => 'Message on ' . $date->toDateString(),
                'created_at' => $date,
                'updated_at' => $date,
            ]);
        }

        return $conversation;
    }

    public function testDateSeparatorShowsTodayLabel(): void
    {
        $user = User::factory()->create(['username' => 'testuser']);
        $conversation = $this->createConversationWithMessages($user, [now()]);

        $this->actingAs($user)
            ->get(route('conversations', $conversation))
            ->assertSee(__('messages/index.today'));
    }

    public function testDateSeparatorShowsYesterdayLabel(): void
    {
        $user = User::factory()->create(['username' => 'testuser2']);
        $conversation = $this->createConversationWithMessages($user, [now()->subDay()]);

        $this->actingAs($user)
            ->get(route('conversations', $conversation))
            ->assertSee(__('messages/index.yesterday'));
    }

    public function testDateSeparatorShowsWeekdayForEarlierThisWeek(): void
    {
        // Travel to a Thursday so Monday of this week is neither today nor yesterday
        $this->travelTo(now()->next('Thursday')->setTime(12, 0));

        $user = User::factory()->create(['username' => 'testuser3']);
        $date = now()->startOfWeek(); // Monday

        $conversation = $this->createConversationWithMessages($user, [$date]);

        $this->actingAs($user)
            ->get(route('conversations', $conversation))
            ->assertSee($date->translatedFormat('l'));
    }

    public function testDateSeparatorShowsAbsoluteDateForOlderMessages(): void
    {
        $user = User::factory()->create(['username' => 'testuser4']);
        $date = now()->subWeeks(2);
        $conversation = $this->createConversationWithMessages($user, [$date]);

        $this->actingAs($user)
            ->get(route('conversations', $conversation))
            ->assertSee($date->translatedFormat('j F Y'));
    }

    public function testMultipleDateSeparatorsAppearForDifferentDays(): void
    {
        $user = User::factory()->create(['username' => 'testuser5']);
        $conversation = $this->createConversationWithMessages($user, [
            now()->subWeeks(2),
            now()->subDay(),
            now(),
        ]);

        $this->actingAs($user)
            ->get(route('conversations', $conversation))
            ->assertSee(now()->subWeeks(2)->translatedFormat('j F Y'))
            ->assertSee(__('messages/index.yesterday'))
            ->assertSee(__('messages/index.today'));
    }

    public function testNoExtraSeparatorForConsecutiveMessagesOnSameDay(): void
    {
        $user = User::factory()->create(['username' => 'testuser6']);
        $today = now();
        $conversation = $this->createConversationWithMessages($user, [
            $today->copy()->setTime(10, 0),
            $today->copy()->setTime(14, 0),
            $today->copy()->setTime(18, 0),
        ]);

        $response = $this->actingAs($user)
            ->get(route('conversations', $conversation));

        $content = $response->getContent();
        $this->assertEquals(1, substr_count($content, 'conversation__date'));
    }
}
