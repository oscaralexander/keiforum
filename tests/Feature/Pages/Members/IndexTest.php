<?php

namespace Tests\Feature\Pages\Members;

use App\Models\Post;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_members_index_page_renders(): void
    {
        $this->get(route('members'))->assertOk();
    }

    public function test_all_members_heading_shows_total_count(): void
    {
        User::factory()->count(3)->create();

        Livewire::test('pages::members.index')
            ->assertSee(__('members/index.all_members', ['count' => 3]));
    }

    public function test_unverified_users_are_not_listed_or_counted(): void
    {
        $verified = User::factory()->create(['created_at' => now()->subDays(2)]);
        $unverified = User::factory()->unverified()->create(['created_at' => now()->subDay()]);

        Livewire::test('pages::members.index')
            ->assertSet('totalMembers', 1)
            ->assertSet('newMembers', fn ($members) => $members->pluck('id')->all() === [$verified->id])
            ->assertSee($verified->username)
            ->assertDontSee($unverified->username);
    }

    public function test_active_scope_only_includes_verified_users(): void
    {
        $verified = User::factory()->create();
        User::factory()->unverified()->create();

        $this->assertSame([$verified->id], User::query()->active()->members()->pluck('id')->all());
    }

    public function test_news_user_is_not_listed_as_member(): void
    {
        Livewire::test('pages::members.index')
            ->assertSet('totalMembers', 0)
            ->assertDontSee(config('news.username'));
    }

    public function test_new_members_lists_users_from_last_7_days_newest_first(): void
    {
        $older = User::factory()->create(['created_at' => now()->subDays(5)]);
        $newer = User::factory()->create(['created_at' => now()->subDay()]);
        $outside = User::factory()->create(['created_at' => now()->subDays(8)]);
        User::factory()->unverified()->create(['created_at' => now()->subDay()]);

        Livewire::test('pages::members.index')
            ->assertSet('newMembers', fn ($members) => $members->pluck('id')->all() === [$newer->id, $older->id])
            ->assertSeeInOrder([__('members/index.new_members.title'), $newer->username, $newer->created_at->translatedFormat('j F Y'), $older->username]);

        $this->assertNotContains($outside->id, Livewire::test('pages::members.index')->get('newMembers')->pluck('id'));
    }

    public function test_new_members_shows_empty_message_when_nobody_joined(): void
    {
        User::factory()->create(['created_at' => now()->subDays(10)]);

        Livewire::test('pages::members.index')
            ->assertSee(__('members/index.new_members.empty'));
    }

    public function test_top_posters_are_ordered_by_posts_count(): void
    {
        $topic = Topic::factory()->create();
        $quiet = User::factory()->create();
        $chatty = User::factory()->create();
        User::factory()->create();

        Post::factory()->count(2)->create(['topic_id' => $topic->id, 'user_id' => $quiet->id]);
        Post::factory()->count(5)->create(['topic_id' => $topic->id, 'user_id' => $chatty->id]);

        Livewire::test('pages::members.index')
            ->assertSet('topPosters', fn ($members) => $members->pluck('id')->all() === [$chatty->id, $quiet->id])
            ->assertSeeInOrder([__('members/index.top_posters.title'), $chatty->username, '5 berichten', $quiet->username, '2 berichten']);
    }

    public function test_top_posters_are_limited_to_10(): void
    {
        $topic = Topic::factory()->create();

        User::factory()->count(12)->create()->each(
            fn (User $user) => Post::factory()->create(['topic_id' => $topic->id, 'user_id' => $user->id])
        );

        Livewire::test('pages::members.index')
            ->assertSet('topPosters', fn ($members) => $members->count() === 10);
    }

    public function test_top_posters_exclude_deleted_posts_and_unverified_users(): void
    {
        $topic = Topic::factory()->create();
        $member = User::factory()->create();
        $unverified = User::factory()->unverified()->create();

        Post::factory()->create(['topic_id' => $topic->id, 'user_id' => $member->id]);
        Post::factory()->count(2)->create(['topic_id' => $topic->id, 'user_id' => $member->id])->each->delete();
        Post::factory()->count(3)->create(['topic_id' => $topic->id, 'user_id' => $unverified->id]);

        $topPosters = Livewire::test('pages::members.index')->get('topPosters');

        $this->assertSame([$member->id], $topPosters->pluck('id')->all());
        $this->assertSame(1, $topPosters->first()->posts_count);
    }

    public function test_top_posters_exclude_keiforum_account(): void
    {
        $topic = Topic::factory()->create();
        $keiforum = User::factory()->create(['username' => 'keiforum']);
        $member = User::factory()->create();

        Post::factory()->count(3)->create(['topic_id' => $topic->id, 'user_id' => $keiforum->id]);
        Post::factory()->create(['topic_id' => $topic->id, 'user_id' => $member->id]);

        Livewire::test('pages::members.index')
            ->assertSet('topPosters', fn ($members) => $members->pluck('id')->all() === [$member->id]);
    }
}
