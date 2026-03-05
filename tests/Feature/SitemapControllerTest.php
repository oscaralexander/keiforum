<?php

namespace Tests\Feature;

use App\Models\Forum;
use App\Models\Post;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitemapControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_returns_xml_content_type(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml');
    }

    public function test_sitemap_contains_static_pages(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertSee(route('home'), false);
        $response->assertSee(route('members'), false);
        $response->assertSee(route('agenda'), false);
    }

    public function test_sitemap_contains_forum_pages(): void
    {
        $forum = Forum::factory()->create();

        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertSee(route('forum.show', $forum), false);
    }

    public function test_sitemap_contains_paginated_forum_pages(): void
    {
        $forum = Forum::factory()->create();
        $user = User::factory()->create();
        Topic::factory()->count(Topic::PAGINATE_COUNT + 1)->create([
            'forum_id' => $forum->id,
            'user_id' => $user->id,
        ]);

        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertSee(route('forum.show', $forum).'?p=2', false);
    }

    public function test_sitemap_contains_topic_pages(): void
    {
        $topic = Topic::factory()->create();

        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertSee(route('topic.show', [$topic->forum, $topic, $topic->slug]), false);
    }

    public function test_sitemap_contains_paginated_topic_pages(): void
    {
        $topic = Topic::factory()->create();
        $user = User::factory()->create();
        Post::factory()->count(Post::PAGINATE_COUNT + 1)->create([
            'topic_id' => $topic->id,
            'user_id' => $user->id,
        ]);

        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertSee(route('topic.show', [$topic->forum, $topic, $topic->slug]).'?p=2', false);
    }

    public function test_sitemap_contains_member_pages(): void
    {
        $user = User::factory()->create();

        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertSee(route('member.show', $user), false);
    }
}
