<?php

namespace Tests\Feature;

use App\Models\Activity;
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
        Post::factory()->count(User::INDEXABLE_POSTS_COUNT)->create(['user_id' => $user->id]);

        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertSee(route('member.show', $user), false);
    }

    public function test_sitemap_excludes_members_without_enough_posts_and_unverified_users(): void
    {
        $withoutPosts = User::factory()->create();
        $withTooFewPosts = User::factory()->create();
        Post::factory()->count(User::INDEXABLE_POSTS_COUNT - 1)->create(['user_id' => $withTooFewPosts->id]);
        $withDeletedPost = User::factory()->create();
        Post::factory()->create(['user_id' => $withDeletedPost->id])->delete();
        $unverified = User::factory()->unverified()->create();
        Post::factory()->count(User::INDEXABLE_POSTS_COUNT)->create(['user_id' => $unverified->id]);

        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertDontSee(route('member.show', $withoutPosts), false);
        $response->assertDontSee(route('member.show', $withTooFewPosts), false);
        $response->assertDontSee(route('member.show', $withDeletedPost), false);
        $response->assertDontSee(route('member.show', $unverified), false);
    }

    public function test_sitemap_topic_lastmod_is_date_of_latest_post(): void
    {
        $topic = Topic::factory()->create(['updated_at' => now()->subYear()]);
        Post::factory()->count(Post::PAGINATE_COUNT)->create(['topic_id' => $topic->id, 'created_at' => now()->subWeek()]);
        $latestPost = Post::factory()->create(['topic_id' => $topic->id, 'created_at' => now()->subDay()]);
        $topicUrl = route('topic.show', [$topic->forum, $topic, $topic->slug]);
        $lastmod = '<lastmod>'.$latestPost->created_at->toAtomString().'</lastmod>';

        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertSeeInOrder([$topicUrl.'</loc>', $lastmod, $topicUrl.'?p=2</loc>', $lastmod], false);
    }

    public function test_robots_txt_references_sitemap(): void
    {
        $this->assertStringContainsString('Sitemap: https://keiforum.nl/sitemap.xml', file_get_contents(public_path('robots.txt')));
    }

    public function test_robots_txt_disallows_auth_routes(): void
    {
        $this->assertStringContainsString('Disallow: /auth/', file_get_contents(public_path('robots.txt')));
    }

    public function test_sitemap_contains_upcoming_published_activities_only(): void
    {
        $upcoming = Activity::factory()->create();
        $past = Activity::factory()->past()->create();
        $unpublished = Activity::factory()->unpublished()->create();

        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertSee($upcoming->url(), false);
        $response->assertDontSee($past->url(), false);
        $response->assertDontSee($unpublished->url(), false);
    }
}
