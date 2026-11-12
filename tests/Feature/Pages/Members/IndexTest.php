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

    public function test_intro_shows_total_member_count(): void
    {
        User::factory()->count(3)->create();

        Livewire::test('pages::members.index')
            ->assertSee('Al 3 Amersfoorters op Keiforum.');
    }

    public function test_intro_uses_singular_for_one_member(): void
    {
        User::factory()->create();

        Livewire::test('pages::members.index')
            ->assertSee('Al 1 Amersfoorter op Keiforum.');
    }

    public function test_intro_invites_the_first_member_when_there_are_none(): void
    {
        Livewire::test('pages::members.index')
            ->assertSee('Word jij de eerste Amersfoorter op Keiforum?');
    }

    public function test_intro_formats_large_member_counts_with_a_thousands_separator(): void
    {
        User::factory()->count(1000)->create();

        Livewire::test('pages::members.index')
            ->assertSee('Al 1.000 Amersfoorters');
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

    public function test_new_members_lists_the_five_newest_verified_users_newest_first(): void
    {
        $oldest = User::factory()->create(['created_at' => now()->subDays(30)]);
        $newest = User::factory()->count(5)->sequence(
            fn ($sequence) => ['created_at' => now()->subDays($sequence->index)]
        )->create();
        User::factory()->unverified()->create(['created_at' => now()]);

        Livewire::test('pages::members.index')
            ->assertSet('newMembers', fn ($members) => $members->pluck('id')->all() === $newest->pluck('id')->all())
            ->assertSeeInOrder([__('members/index.new_members.title'), $newest->first()->username, $newest->first()->created_at->translatedFormat('j M Y')]);

        $this->assertNotContains($oldest->id, Livewire::test('pages::members.index')->get('newMembers')->pluck('id'));
    }

    public function test_new_members_section_is_hidden_without_members(): void
    {
        Livewire::test('pages::members.index')
            ->assertDontSee(__('members/index.new_members.title'));
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

    public function test_top_posters_are_limited_to_5(): void
    {
        $topic = Topic::factory()->create();

        User::factory()->count(7)->create()->each(
            fn (User $user) => Post::factory()->create(['topic_id' => $topic->id, 'user_id' => $user->id])
        );

        Livewire::test('pages::members.index')
            ->assertSet('topPosters', fn ($members) => $members->count() === 5);
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
