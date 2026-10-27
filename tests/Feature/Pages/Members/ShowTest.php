<?php

namespace Tests\Feature\Pages\Members;

use App\Models\Post;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_topics_show_posts_count_and_link_to_latest_post(): void
    {
        $user = User::factory()->create();
        $topic = Topic::factory()->create();
        Post::factory()->create(['topic_id' => $topic->id, 'user_id' => $user->id]);
        $latestPost = Post::factory()->create(['topic_id' => $topic->id, 'user_id' => $user->id]);
        Post::factory()->create(['topic_id' => $topic->id]);

        $latestPostUrl = route('topic.show', [$topic->forum, $topic, $topic->slug, 'post' => $latestPost->id]);

        $this->get(route('member.show', $user))
            ->assertOk()
            ->assertSeeInOrder([
                'class="profile__topic-title" href="'.e($latestPostUrl).'"',
                e($topic->title),
                '2 berichten',
                'href="'.e($latestPostUrl).'"',
            ], false);
    }

    public function test_active_topics_are_sorted_by_most_recent_post_of_user(): void
    {
        $user = User::factory()->create();
        $busyTopic = Topic::factory()->create();
        $recentTopic = Topic::factory()->create();
        Post::factory()->count(3)->create(['topic_id' => $busyTopic->id, 'user_id' => $user->id]);
        Post::factory()->create(['topic_id' => $recentTopic->id, 'user_id' => $user->id]);
        Post::factory()->create(['topic_id' => $busyTopic->id]);

        $this->get(route('member.show', $user))
            ->assertOk()
            ->assertSeeInOrder([e($recentTopic->title), '1 bericht', e($busyTopic->title), '3 berichten'], false);
    }

    public function test_empty_state_is_shown_without_posts(): void
    {
        $user = User::factory()->create();

        $this->get(route('member.show', $user))
            ->assertOk()
            ->assertSee(__('members/show.no_topics'))
            ->assertDontSee('profile__topics');
    }

    public function test_profile_without_posts_is_not_indexed(): void
    {
        $user = User::factory()->create();

        $this->get(route('member.show', $user))
            ->assertOk()
            ->assertSee('<meta content="noindex" name="robots">', false);
    }

    public function test_profile_with_only_deleted_posts_is_not_indexed(): void
    {
        $user = User::factory()->create();
        Post::factory()->create(['user_id' => $user->id])->delete();

        $this->get(route('member.show', $user))
            ->assertOk()
            ->assertSee('<meta content="noindex" name="robots">', false);
    }

    public function test_profile_with_too_few_posts_is_not_indexed(): void
    {
        $user = User::factory()->create();
        Post::factory()->count(User::INDEXABLE_POSTS_COUNT - 1)->create(['user_id' => $user->id]);

        $this->get(route('member.show', $user))
            ->assertOk()
            ->assertSee('<meta content="noindex" name="robots">', false);
    }

    public function test_profile_with_enough_posts_is_indexed(): void
    {
        $user = User::factory()->create();
        Post::factory()->count(User::INDEXABLE_POSTS_COUNT)->create(['user_id' => $user->id]);

        $this->get(route('member.show', $user))
            ->assertOk()
            ->assertDontSee('name="robots"', false);
    }
}
