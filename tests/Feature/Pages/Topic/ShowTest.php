<?php

namespace Tests\Feature\Pages\Topic;

use App\Models\Area;
use App\Models\Forum;
use App\Models\Like;
use App\Models\Post;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_pagination_is_at_the_bottom_of_the_posts_panel(): void
    {
        [$forum, $topic] = $this->createTopicWithPost();
        Post::factory()->count(Post::PAGINATE_COUNT)->create(['topic_id' => $topic->id]);

        $this->get(route('topic.show', [$forum, $topic, $topic->slug]))
            ->assertOk()
            ->assertSeeInOrder([
                '<div class="panel">',
                'id="reageren"',
                '</section>',
                '<div class="panel__body">',
                '<nav class="pagination"',
            ], false);
    }

    public function test_single_page_topic_has_no_pagination(): void
    {
        [$forum, $topic] = $this->createTopicWithPost();

        $this->get(route('topic.show', [$forum, $topic, $topic->slug]))
            ->assertOk()
            ->assertDontSee('class="pagination"', false);
    }

    private function createTopicWithPost(): array
    {
        $forum = Forum::factory()->create();
        $topic = Topic::factory()->create(['forum_id' => $forum->id]);
        $post = Post::factory()->create(['topic_id' => $topic->id]);

        return [$forum, $topic, $post];
    }

    public function test_topic_show_page_renders(): void
    {
        [$forum, $topic] = $this->createTopicWithPost();

        $this->get(route('topic.show', [$forum, $topic, $topic->slug]))->assertOk();
    }

    public function test_topic_show_displays_posts(): void
    {
        [$forum, $topic, $post] = $this->createTopicWithPost();

        Livewire::test('pages::topic.show', ['topic' => $topic])
            ->assertSee($post->user->username);
    }

    public function test_topic_show_displays_deleted_post_placeholder(): void
    {
        [$forum, $topic, $post] = $this->createTopicWithPost();
        $deletedPost = Post::factory()->create(['topic_id' => $topic->id]);
        $deletedPost->delete();

        Livewire::test('pages::topic.show', ['topic' => $topic])
            ->assertSeeLivewire('post');
    }

    public function test_topic_show_reply_form_visible_when_authenticated(): void
    {
        [$forum, $topic] = $this->createTopicWithPost();
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test('pages::topic.show', ['topic' => $topic])
            ->assertSee(__('topic/show.reply'));
    }

    public function test_topic_show_reply_form_hidden_when_guest(): void
    {
        [$forum, $topic] = $this->createTopicWithPost();

        Livewire::test('pages::topic.show', ['topic' => $topic])
            ->assertSee(__('topic/show.reply_login'));
    }

    public function test_guest_reply_login_link_is_nofollow(): void
    {
        [$forum, $topic] = $this->createTopicWithPost();
        $topicPath = '/'.ltrim(parse_url(route('topic.show', [$forum, $topic, $topic->slug]), PHP_URL_PATH), '/');

        $this->get(route('topic.show', [$forum, $topic, $topic->slug]))
            ->assertOk()
            ->assertSee('<a href="'.route('login', ['redirect' => $topicPath]).'" rel="nofollow" wire:navigate>Log in</a> om te reageren.', false);
    }

    public function test_authenticated_user_can_submit_reply(): void
    {
        [$forum, $topic] = $this->createTopicWithPost();
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test('replies', ['topic' => $topic])
            ->set('body', '<p>Test reply</p>')
            ->call('submit')
            ->assertRedirect();

        $this->assertDatabaseHas('posts', [
            'topic_id' => $topic->id,
            'user_id' => $user->id,
            'body' => '<p>Test reply</p>',
        ]);
    }

    public function test_guest_cannot_submit_reply(): void
    {
        [$forum, $topic] = $this->createTopicWithPost();

        Livewire::test('replies', ['topic' => $topic])
            ->set('body', '<p>Test reply</p>')
            ->call('submit')
            ->assertForbidden();
    }

    public function test_topic_shows_first_post_areas_without_extra_queries(): void
    {
        [$forum, $topic] = $this->createTopicWithPost();

        // Ensure the page renders with areas loaded (no lazy-load exception)
        Livewire::test('pages::topic.show', ['topic' => $topic])
            ->assertOk();
    }

    public function test_last_read_post_id_is_set_on_mount_for_authenticated_user(): void
    {
        [$forum, $topic, $post] = $this->createTopicWithPost();
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test('replies', ['topic' => $topic]);

        $this->assertDatabaseHas('topic_user', [
            'topic_id' => $topic->id,
            'user_id' => $user->id,
            'last_read_post_id' => $post->id,
        ]);
    }

    public function test_last_read_post_id_is_not_overwritten_when_back_browsing(): void
    {
        [$forum, $topic] = $this->createTopicWithPost();
        $laterPost = Post::factory()->create(['topic_id' => $topic->id]);
        $user = User::factory()->create();

        $topic->trackedByUsers()->attach($user->id, ['last_read_post_id' => $laterPost->id]);

        // Visit page 1 which has a lower max post ID than what was already tracked
        Livewire::actingAs($user)
            ->test('replies', ['topic' => $topic]);

        $this->assertDatabaseHas('topic_user', [
            'topic_id' => $topic->id,
            'user_id' => $user->id,
            'last_read_post_id' => $laterPost->id,
        ]);
    }

    public function test_last_read_post_id_is_not_tracked_for_guests(): void
    {
        [$forum, $topic] = $this->createTopicWithPost();

        Livewire::test('replies', ['topic' => $topic]);

        $this->assertDatabaseMissing('topic_user', ['topic_id' => $topic->id]);
    }

    public function test_description_strips_html_and_urls(): void
    {
        $topic = Topic::factory()->create();
        Post::factory()->create([
            'topic_id' => $topic->id,
            'body' => '<p><a href="https://assets.test/photo.jpg">https://assets.test/photo.jpg</a></p><p>Club Paap is terug &amp; zie <a href="https://example.com">deze site</a> of www.example.com.</p><ul><li>Een</li><li>Twee</li></ul>',
        ]);

        $this->assertSame('Club Paap is terug & zie deze site of Een Twee', $topic->fresh()->description);
    }

    public function test_description_is_limited_in_length(): void
    {
        $topic = Topic::factory()->create();
        Post::factory()->create(['topic_id' => $topic->id, 'body' => '<p>'.str_repeat('woord ', 100).'</p>']);

        $this->assertSame(203, mb_strlen($topic->fresh()->description));
    }

    public function test_description_is_empty_without_posts(): void
    {
        $this->assertSame('', Topic::factory()->create()->description);
    }

    public function test_topic_show_page_renders_clean_meta_description(): void
    {
        [$forum, $topic, $post] = $this->createTopicWithPost();
        $post->update(['body' => '<p><a href="https://assets.test/photo.jpg">https://assets.test/photo.jpg</a></p><p>Hallo Amersfoort</p>']);

        $this->get(route('topic.show', [$forum, $topic, $topic->slug]))
            ->assertSee('<meta content="Hallo Amersfoort" property="og:description">', false);
    }

    public function test_schema_includes_like_count_of_first_post(): void
    {
        [, $topic, $post] = $this->createTopicWithPost();
        Like::create(['post_id' => $post->id, 'user_id' => User::factory()->create()->id]);

        $schema = Livewire::test('pages::topic.show', ['topic' => $topic])->instance()->schema;

        $this->assertSame(1, $schema['interactionStatistic'][1]['userInteractionCount']);
    }

    public function test_schema_like_count_is_zero_without_likes(): void
    {
        [, $topic] = $this->createTopicWithPost();

        $schema = Livewire::test('pages::topic.show', ['topic' => $topic])->instance()->schema;

        $this->assertSame(0, $schema['interactionStatistic'][1]['userInteractionCount']);
    }

    public function test_topic_show_page_renders_a_single_meta_description(): void
    {
        [$forum, $topic, $post] = $this->createTopicWithPost();
        $post->update(['body' => '<p>Hallo Amersfoort</p>']);

        $html = $this->get(route('topic.show', [$forum, $topic, $topic->slug]))->getContent();

        $this->assertSame(1, substr_count($html, 'name="description"'));
        $this->assertSame(1, substr_count($html, 'property="og:description"'));
        $this->assertStringContainsString('<meta content="Hallo Amersfoort" name="description">', $html);
    }

    public function test_topic_without_description_falls_back_to_site_description(): void
    {
        $forum = Forum::factory()->create();
        $topic = Topic::factory()->create(['forum_id' => $forum->id]);

        $this->get(route('topic.show', [$forum, $topic, $topic->slug]))
            ->assertSee('<meta content="'.e(__('app.description')).'" name="description">', false);
    }

    public function test_header_shows_starter_date_and_area_in_order(): void
    {
        [, $topic] = $this->createTopicWithPost();
        $topic->update(['created_at' => now()->setDate(now()->year - 1, 10, 7)]);
        $area = Area::query()->create(['name' => 'Testwijk', 'slug' => 'testwijk']);
        $topic->areas()->attach($area);

        Livewire::test('pages::topic.show', ['topic' => $topic->fresh()])
            ->assertSeeHtml('href="'.route('member.show', $topic->user).'"')
            ->assertSeeInOrder([$topic->user->username, '7 okt '.(now()->year - 1), 'Testwijk']);
    }

    public function test_header_shows_starter_and_date_without_area(): void
    {
        [, $topic] = $this->createTopicWithPost();

        Livewire::test('pages::topic.show', ['topic' => $topic])
            ->assertSee($topic->user->username)
            ->assertSee($topic->created_at->translatedFormat('j M'))
            ->assertSeeHtml('#pencil')
            ->assertDontSeeHtml('#map-pin');
    }

    public function test_header_date_leaves_out_the_current_year(): void
    {
        [, $topic] = $this->createTopicWithPost();
        $topic->update(['created_at' => now()->setDate(now()->year, 4, 1)]);

        Livewire::test('pages::topic.show', ['topic' => $topic->fresh()])
            ->assertSeeHtml('>1 apr</time>')
            ->assertDontSeeHtml('>1 apr '.now()->year.'</time>');
    }

    public function test_header_date_shows_the_year_for_earlier_years(): void
    {
        [, $topic] = $this->createTopicWithPost();
        $topic->update(['created_at' => now()->setDate(now()->year - 1, 4, 1)]);

        Livewire::test('pages::topic.show', ['topic' => $topic->fresh()])
            ->assertSeeHtml('>1 apr '.(now()->year - 1).'</time>');
    }

    public function test_sidebar_lists_most_active_members_by_post_count(): void
    {
        [, $topic, $firstPost] = $this->createTopicWithPost();
        $mostActive = User::factory()->create(['username' => 'veelschrijver']);
        $lessActive = User::factory()->create(['username' => 'tweeposter']);
        $otherTopicPoster = User::factory()->create(['username' => 'elderswactief']);

        Post::factory()->count(3)->create(['topic_id' => $topic->id, 'user_id' => $mostActive->id]);
        Post::factory()->count(2)->create(['topic_id' => $topic->id, 'user_id' => $lessActive->id]);
        Post::factory()->count(5)->create(['user_id' => $otherTopicPoster->id]);

        $members = Livewire::test('pages::topic.show', ['topic' => $topic])
            ->assertSee(__('topic/show.most_active_members'))
            ->assertSee(trans_choice('members/index.top_posters.posts_count', 3, ['count' => 3]))
            ->instance()
            ->mostActiveMembers();

        $this->assertSame(
            [$mostActive->id, $lessActive->id, $firstPost->user_id],
            $members->pluck('id')->all()
        );
        $this->assertSame([3, 2, 1], $members->pluck('posts_count')->all());
    }

    public function test_sidebar_ignores_deleted_posts_and_shows_at_most_five_members(): void
    {
        [, $topic] = $this->createTopicWithPost();

        Post::factory()->count(6)->create(['topic_id' => $topic->id]);
        $deletedPoster = User::factory()->create();
        Post::factory()->count(4)->create(['topic_id' => $topic->id, 'user_id' => $deletedPoster->id])
            ->each->delete();

        $memberIds = Livewire::test('pages::topic.show', ['topic' => $topic])
            ->instance()
            ->mostActiveMembers()
            ->pluck('id');

        $this->assertCount(5, $memberIds);
        $this->assertNotContains($deletedPoster->id, $memberIds);
    }

    public function test_reply_call_to_action_scrolls_to_reply_form_for_members(): void
    {
        [$forum, $topic] = $this->createTopicWithPost();
        $url = route('topic.show', [$forum, $topic, $topic->slug]);

        $html = $this->actingAs(User::factory()->create())->get($url)->assertOk()->getContent();

        $this->assertStringContainsString('href="#reageren"', $this->callToAction($html));
        $this->assertStringContainsString('id="reageren"', $html);
    }

    public function test_reply_call_to_action_links_guests_to_login(): void
    {
        [$forum, $topic] = $this->createTopicWithPost();
        $url = route('topic.show', [$forum, $topic, $topic->slug]);
        $loginUrl = e(route('login', ['redirect' => parse_url($url, PHP_URL_PATH)]));

        $html = $this->get($url)->assertOk()->getContent();

        $callToAction = $this->callToAction($html);

        $this->assertStringContainsString('href="'.$loginUrl.'"', $callToAction);
        $this->assertStringContainsString('rel="nofollow"', $callToAction);
    }

    /**
     * The opening tag of the link in the header's call to action.
     */
    private function callToAction(string $html): string
    {
        $this->assertMatchesRegularExpression('/<div class="header__cta">.*?(<a\s[^>]*>)/s', $html);
        preg_match('/<div class="header__cta">.*?(<a\s[^>]*>)/s', $html, $matches);

        return $matches[1];
    }
}
