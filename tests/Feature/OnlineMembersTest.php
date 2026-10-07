<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OnlineMembersTest extends TestCase
{
    use RefreshDatabase;

    public function test_recently_seen_members_are_listed(): void
    {
        $online = User::factory()->create(['last_seen_at' => now()->subMinutes(2)]);

        Livewire::test('online-members')
            ->assertSee('1 gebruiker online:')
            ->assertSee($online->username)
            ->assertSee(route('member.show', $online), false);
    }

    public function test_members_not_seen_recently_are_not_listed(): void
    {
        $online = User::factory()->create(['last_seen_at' => now()]);
        $offline = User::factory()->create(['last_seen_at' => now()->subMinutes(User::ONLINE_MINUTES + 1)]);
        $neverSeen = User::factory()->create(['last_seen_at' => null]);

        Livewire::test('online-members')
            ->assertSee($online->username)
            ->assertDontSee($offline->username)
            ->assertDontSee($neverSeen->username);
    }

    public function test_unverified_users_and_news_user_are_not_listed(): void
    {
        $unverified = User::factory()->unverified()->create(['last_seen_at' => now()]);
        User::query()->where('username', config('news.username'))->update(['last_seen_at' => now()]);

        Livewire::test('online-members')
            ->assertDontSee($unverified->username)
            ->assertDontSee(config('news.username'));
    }

    public function test_members_are_sorted_by_username(): void
    {
        User::factory()->create(['username' => 'zeger', 'last_seen_at' => now()]);
        User::factory()->create(['username' => 'anna', 'last_seen_at' => now()]);

        Livewire::test('online-members')
            ->assertSee('2 gebruikers online')
            ->assertSeeInOrder(['anna', ', ', 'zeger']);
    }

    public function test_nothing_is_shown_when_nobody_is_online(): void
    {
        Livewire::test('online-members')
            ->assertDontSee('gebruikers online');
    }

    public function test_online_members_are_shown_on_index_forum_and_topic_pages(): void
    {
        $online = User::factory()->create(['last_seen_at' => now()]);
        $topic = Topic::factory()->create();
        Post::factory()->create(['topic_id' => $topic->id]);

        $this->get(route('home'))->assertOk()->assertSeeLivewire('online-members')->assertSee($online->username);
        $this->get(route('forum.show', $topic->forum))->assertOk()->assertSeeLivewire('online-members');
        $this->get(route('topic.show', [$topic->forum, $topic, $topic->slug]))->assertOk()->assertSeeLivewire('online-members');
    }
}
