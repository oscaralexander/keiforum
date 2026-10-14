<?php

namespace Tests\Feature;

use App\Models\Forum;
use App\Models\Post;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class CanonicalUrlTest extends TestCase
{
    use RefreshDatabase;

    private function canonicalTag(string $url): string
    {
        return '<link href="'.e($url).'" rel="canonical">';
    }

    /**
     * @return array{0: Forum, 1: Topic, 2: Collection<int, Post>}
     */
    private function createTopicWithPosts(int $count): array
    {
        $forum = Forum::factory()->create();
        $topic = Topic::factory()->create(['forum_id' => $forum->id]);
        $posts = Post::factory()->count($count)->create(['topic_id' => $topic->id]);

        return [$forum, $topic, $posts];
    }

    public function test_topic_link_to_post_on_first_page_is_canonicalized_to_topic(): void
    {
        [$forum, $topic, $posts] = $this->createTopicWithPosts(2);
        $topicUrl = route('topic.show', [$forum, $topic, $topic->slug]);

        $this->get(route('topic.show', [$forum, $topic, $topic->slug, 'post' => $posts->last()->id]))
            ->assertOk()
            ->assertSee($this->canonicalTag($topicUrl), false);
    }

    public function test_topic_link_to_post_on_later_page_is_canonicalized_to_that_page(): void
    {
        [$forum, $topic, $posts] = $this->createTopicWithPosts(Post::PAGINATE_COUNT + 1);
        $topicUrl = route('topic.show', [$forum, $topic, $topic->slug]);

        $this->get(route('topic.show', [$forum, $topic, $topic->slug, 'post' => $posts->last()->id]))
            ->assertOk()
            ->assertSee($this->canonicalTag($topicUrl.'?p=2'), false);
    }

    public function test_paginated_topic_page_keeps_page_number(): void
    {
        [$forum, $topic] = $this->createTopicWithPosts(Post::PAGINATE_COUNT + 1);
        $topicUrl = route('topic.show', [$forum, $topic, $topic->slug]);

        $this->get($topicUrl.'?p=2')
            ->assertOk()
            ->assertSee($this->canonicalTag($topicUrl.'?p=2'), false);
    }

    public function test_forum_page_drops_first_page_and_other_query_parameters(): void
    {
        $forum = Forum::factory()->create();
        $forumUrl = route('forum.show', $forum);

        $this->get($forumUrl.'?p=1&utm_source=nieuwsbrief')
            ->assertOk()
            ->assertSee($this->canonicalTag($forumUrl), false);
    }

    public function test_login_page_drops_redirect_parameter(): void
    {
        $this->get(route('login', ['redirect' => '/@john']))
            ->assertOk()
            ->assertSee($this->canonicalTag(route('login')), false);
    }

    public function test_member_page_has_canonical_url(): void
    {
        $user = User::factory()->create();

        $this->get(route('member.show', $user))
            ->assertOk()
            ->assertSee($this->canonicalTag(route('member.show', $user)), false);
    }
}
