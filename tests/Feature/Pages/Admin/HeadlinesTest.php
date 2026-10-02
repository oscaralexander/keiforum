<?php

namespace Tests\Feature\Pages\Admin;

use App\Enums\HeadlineVerdict;
use App\Models\Headline;
use App\Models\Post;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class HeadlinesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake(['www.nieuwsplein33.nl/nieuws/*' => Http::response('', 404)]);
    }

    private function createHeadlineWithTopic(HeadlineVerdict $verdict, int $replies = 0): Headline
    {
        $topic = Topic::factory()->create(['is_visible' => $verdict === HeadlineVerdict::APPROVED || $replies > 0]);
        Post::factory()->count($replies + 1)->create(['topic_id' => $topic->id]);

        return Headline::factory()->verdict($verdict)->create(['topic_id' => $topic->id]);
    }

    private function asAdmin(): Testable
    {
        return Livewire::actingAs(User::factory()->admin()->create())->test('admin.headlines');
    }

    public function test_admin_page_shows_headlines_overview(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin'))
            ->assertOk()
            ->assertSeeLivewire('admin.headlines');
    }

    public function test_lists_ten_latest_headlines_with_verdicts(): void
    {
        Headline::factory()->create(['title' => 'Oudste artikel', 'pub_date' => now()->subDays(5)]);
        Headline::factory()->count(9)->create(['pub_date' => now()->subDays(2)]);
        Headline::factory()->verdict(HeadlineVerdict::BLOCKED)->create(['title' => 'Taakstraf voor inwoner', 'pub_date' => now()->subHour()]);

        $this->asAdmin()
            ->assertSee('Taakstraf voor inwoner')
            ->assertSee(__('admin/index.headlines.verdict.blocked'))
            ->assertSee(__('admin/index.headlines.verdict.pending'))
            ->assertDontSee('Oudste artikel');
    }

    public function test_accepting_blocked_headline_creates_visible_topic(): void
    {
        $headline = Headline::factory()->verdict(HeadlineVerdict::BLOCKED)->create(['description' => 'Een inwoner kreeg een taakstraf.']);

        $this->asAdmin()
            ->call('accept', $headline->id)
            ->assertDispatched('toast');

        $headline->refresh();
        $this->assertSame(HeadlineVerdict::APPROVED, $headline->verdict);
        $this->assertTrue($headline->topic->is_visible);
        $this->assertStringContainsString('Een inwoner kreeg een taakstraf.', $headline->topic->firstPost->body);
    }

    public function test_accepting_neutral_headline_shows_topic(): void
    {
        $headline = $this->createHeadlineWithTopic(HeadlineVerdict::NEUTRAL);

        $this->asAdmin()->call('accept', $headline->id);

        $headline->refresh();
        $this->assertSame(HeadlineVerdict::APPROVED, $headline->verdict);
        $this->assertTrue($headline->topic->is_visible);
        $this->assertDatabaseCount('topics', 1);
    }

    public function test_accepting_unprocessed_headline_creates_topic(): void
    {
        $headline = Headline::factory()->create();

        $this->asAdmin()->call('accept', $headline->id);

        $this->assertNotNull($headline->refresh()->topic);
    }

    public function test_rejecting_approved_headline_removes_topic(): void
    {
        $headline = $this->createHeadlineWithTopic(HeadlineVerdict::APPROVED);
        $topic = $headline->topic;

        $this->asAdmin()
            ->call('reject', $headline->id)
            ->assertDispatched('toast');

        $headline->refresh();
        $this->assertSame(HeadlineVerdict::BLOCKED, $headline->verdict);
        $this->assertNull($headline->topic);
        $this->assertSoftDeleted($topic);
    }

    public function test_accepting_rejected_headline_restores_topic_with_replies(): void
    {
        $headline = $this->createHeadlineWithTopic(HeadlineVerdict::APPROVED, replies: 2);
        $topicId = $headline->topic_id;

        $this->asAdmin()
            ->call('reject', $headline->id)
            ->call('accept', $headline->id);

        $headline->refresh();
        $this->assertSame($topicId, $headline->topic->id);
        $this->assertSame(3, $headline->topic->posts()->count());
        $this->assertTrue($headline->topic->is_visible);
        $this->assertDatabaseCount('topics', 1);
    }

    public function test_buttons_match_verdict(): void
    {
        Headline::factory()->verdict(HeadlineVerdict::APPROVED)->create();

        $this->asAdmin()
            ->assertSee(__('admin/index.headlines.reject'))
            ->assertDontSee(__('admin/index.headlines.accept'));
    }

    public function test_buttons_are_coloured_icon_buttons(): void
    {
        Headline::factory()->verdict(HeadlineVerdict::NEUTRAL)->create();

        $html = $this->asAdmin()->html();

        $this->assertMatchesRegularExpression('/class="btn btn--good btn--icon btn--small"[^>]*aria-label="Accepteren"|aria-label="Accepteren"[^>]*class="btn btn--good btn--icon btn--small"/', $html);
        $this->assertMatchesRegularExpression('/class="btn btn--danger btn--icon btn--small"[^>]*aria-label="Weigeren"|aria-label="Weigeren"[^>]*class="btn btn--danger btn--icon btn--small"/', $html);
        $this->assertStringNotContainsString('<span>Accepteren</span>', $html);
        $this->assertStringNotContainsString('<span>Weigeren</span>', $html);
    }

    public function test_regular_users_cannot_use_headlines_overview(): void
    {
        $headline = Headline::factory()->verdict(HeadlineVerdict::BLOCKED)->create();
        $user = User::factory()->create();

        Livewire::actingAs($user)->test('admin.headlines')->assertForbidden();

        $this->assertSame(HeadlineVerdict::BLOCKED, $headline->refresh()->verdict);
    }
}
