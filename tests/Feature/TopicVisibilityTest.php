<?php

namespace Tests\Feature;

use App\Enums\HeadlineVerdict;
use App\Models\Area;
use App\Models\Headline;
use App\Models\Post;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class TopicVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private function createNewsTopic(HeadlineVerdict $verdict = HeadlineVerdict::NEUTRAL, array $attributes = []): Topic
    {
        $topic = Topic::factory()->create([
            'is_visible' => $verdict === HeadlineVerdict::APPROVED,
            ...$attributes,
        ]);

        Post::factory()->create(['topic_id' => $topic->id, 'user_id' => $topic->user_id]);
        Headline::factory()->verdict($verdict)->create(['topic_id' => $topic->id]);

        return $topic->refresh();
    }

    public function test_first_reply_makes_hidden_topic_visible(): void
    {
        $topic = $this->createNewsTopic();

        Post::factory()->create(['topic_id' => $topic->id]);

        $this->assertTrue($topic->refresh()->is_visible);
    }

    public function test_replying_through_the_topic_page_makes_hidden_topic_visible(): void
    {
        $topic = $this->createNewsTopic();

        Livewire::actingAs(User::factory()->create())
            ->test('pages::topic.show', ['topic' => $topic])
            ->set('body', '<p>Goed plan!</p>')
            ->call('submit');

        $this->assertTrue($topic->refresh()->is_visible);
    }

    public function test_deleting_only_reply_hides_topic_again(): void
    {
        $topic = $this->createNewsTopic();
        $reply = Post::factory()->create(['topic_id' => $topic->id]);

        $reply->update(['deleted_at' => now()]);

        $this->assertFalse($topic->refresh()->is_visible);
    }

    public function test_soft_deleting_only_reply_hides_topic_again(): void
    {
        $topic = $this->createNewsTopic();
        $reply = Post::factory()->create(['topic_id' => $topic->id]);

        $reply->delete();

        $this->assertFalse($topic->refresh()->is_visible);
    }

    public function test_approved_topic_stays_visible_without_replies(): void
    {
        $topic = $this->createNewsTopic(HeadlineVerdict::APPROVED);
        $reply = Post::factory()->create(['topic_id' => $topic->id]);

        $reply->delete();

        $this->assertTrue($topic->refresh()->is_visible);
    }

    public function test_regular_topic_is_unaffected(): void
    {
        $topic = Topic::factory()->create();
        Post::factory()->create(['topic_id' => $topic->id]);

        $this->assertTrue($topic->refresh()->is_visible);
    }

    public function test_hidden_topic_is_excluded_from_home_page(): void
    {
        $hidden = $this->createNewsTopic(attributes: ['title' => 'Verborgen nieuwsbericht']);
        $visible = $this->createNewsTopic(HeadlineVerdict::APPROVED, ['forum_id' => $hidden->forum_id, 'title' => 'Zichtbaar nieuwsbericht']);

        Livewire::test('pages::index')
            ->assertSee($visible->title)
            ->assertDontSee($hidden->title);
    }

    public function test_hidden_topic_is_excluded_from_topic_count(): void
    {
        $hidden = $this->createNewsTopic();

        $forum = Livewire::test('pages::index')
            ->instance()
            ->forums()
            ->firstWhere('id', $hidden->forum_id);

        $this->assertSame(0, $forum->topics_count);
    }

    public function test_hidden_topic_is_excluded_from_forum_page(): void
    {
        $hidden = $this->createNewsTopic(attributes: ['title' => 'Verborgen nieuwsbericht']);
        $visible = $this->createNewsTopic(HeadlineVerdict::APPROVED, ['forum_id' => $hidden->forum_id, 'title' => 'Zichtbaar nieuwsbericht']);

        Livewire::test('pages::forum.show', ['forum' => $hidden->forum])
            ->assertSee($visible->title)
            ->assertDontSee($hidden->title);
    }

    public function test_hidden_topic_is_excluded_from_area_page(): void
    {
        $area = Area::query()->first() ?? Area::query()->create(['name' => 'Centrum', 'slug' => 'centrum']);
        $hidden = $this->createNewsTopic(attributes: ['title' => 'Verborgen nieuwsbericht']);
        $visible = $this->createNewsTopic(HeadlineVerdict::APPROVED, ['title' => 'Zichtbaar nieuwsbericht']);
        $hidden->areas()->attach($area);
        $visible->areas()->attach($area);

        Livewire::test('pages::area.show', ['area' => $area])
            ->assertSee($visible->title)
            ->assertDontSee($hidden->title);
    }

    public function test_hidden_topic_is_excluded_from_member_profile(): void
    {
        $hidden = $this->createNewsTopic(attributes: ['title' => 'Verborgen nieuwsbericht']);
        $visible = $this->createNewsTopic(HeadlineVerdict::APPROVED, ['user_id' => $hidden->user_id, 'title' => 'Zichtbaar nieuwsbericht']);

        Livewire::test('pages::members.show', ['user' => $hidden->user])
            ->assertSee($visible->title)
            ->assertDontSee($hidden->title);
    }

    public function test_hidden_topic_is_excluded_from_sitemap(): void
    {
        $hidden = $this->createNewsTopic();
        $visible = $this->createNewsTopic(HeadlineVerdict::APPROVED);

        $this->get(route('sitemap'))
            ->assertOk()
            ->assertSee(route('topic.show', [$visible->forum, $visible, $visible->slug]))
            ->assertDontSee(route('topic.show', [$hidden->forum, $hidden, $hidden->slug]));
    }

    public function test_hidden_topic_is_reachable_but_not_indexed(): void
    {
        $hidden = $this->createNewsTopic();

        $this->get(route('topic.show', [$hidden->forum, $hidden, $hidden->slug]))
            ->assertOk()
            ->assertSee('<meta content="noindex" name="robots">', false)
            ->assertDontSee('application/ld+json', false);
    }

    public function test_visible_topic_is_indexed(): void
    {
        $visible = $this->createNewsTopic(HeadlineVerdict::APPROVED);

        $this->get(route('topic.show', [$visible->forum, $visible, $visible->slug]))
            ->assertOk()
            ->assertDontSee('<meta content="noindex" name="robots">', false);
    }

    private function firstPostComponent(Topic $topic, User $user): Testable
    {
        return Livewire::actingAs($user)
            ->test('post', ['post' => $topic->firstPost, 'isFirstPost' => true, 'number' => 1]);
    }

    public function test_admin_can_hide_approved_topic_until_replies(): void
    {
        $topic = $this->createNewsTopic(HeadlineVerdict::APPROVED);

        $this->firstPostComponent($topic, User::factory()->admin()->create())
            ->assertSee(__('post/show.news_hide'))
            ->call('toggleNewsVisibility')
            ->assertDispatched('toast')
            ->assertSee(__('post/show.news_show'));

        $topic->refresh();
        $this->assertFalse($topic->is_visible);
        $this->assertSame(HeadlineVerdict::NEUTRAL, $topic->headline->verdict);
    }

    public function test_hiding_topic_with_replies_keeps_it_visible(): void
    {
        $topic = $this->createNewsTopic(HeadlineVerdict::APPROVED);
        Post::factory()->create(['topic_id' => $topic->id]);

        $this->firstPostComponent($topic, User::factory()->admin()->create())
            ->call('toggleNewsVisibility');

        $topic->refresh();
        $this->assertTrue($topic->is_visible);
        $this->assertSame(HeadlineVerdict::NEUTRAL, $topic->headline->verdict);
    }

    public function test_admin_can_show_hidden_topic_immediately(): void
    {
        $topic = $this->createNewsTopic();

        $this->firstPostComponent($topic, User::factory()->admin()->create())
            ->assertSee(__('post/show.news_show'))
            ->call('toggleNewsVisibility');

        $topic->refresh();
        $this->assertTrue($topic->is_visible);
        $this->assertSame(HeadlineVerdict::APPROVED, $topic->headline->verdict);
    }

    public function test_non_admin_cannot_toggle_news_visibility(): void
    {
        $topic = $this->createNewsTopic(HeadlineVerdict::APPROVED);

        $this->firstPostComponent($topic, User::factory()->create())
            ->assertDontSee(__('post/show.news_hide'))
            ->call('toggleNewsVisibility')
            ->assertForbidden();

        $this->assertTrue($topic->refresh()->is_visible);
    }

    public function test_news_visibility_option_is_absent_for_regular_topics(): void
    {
        $topic = Topic::factory()->create();
        Post::factory()->create(['topic_id' => $topic->id]);

        $this->firstPostComponent($topic, User::factory()->admin()->create())
            ->assertDontSee(__('post/show.news_hide'))
            ->assertDontSee(__('post/show.news_show'))
            ->call('toggleNewsVisibility')
            ->assertNotFound();
    }
}
