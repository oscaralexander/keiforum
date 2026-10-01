<?php

namespace Tests\Feature;

use App\Enums\HeadlineVerdict;
use App\Jobs\ProcessHeadline;
use App\Lib\HeadlineEvaluator;
use App\Models\Headline;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class ProcessHeadlineTest extends TestCase
{
    use RefreshDatabase;

    private function mockEvaluation(HeadlineVerdict $verdict, ?string $question = 'Wat vind jij hiervan?'): void
    {
        $this->mock(HeadlineEvaluator::class, function (MockInterface $mock) use ($verdict, $question): void {
            $mock->shouldReceive('evaluate')->once()->andReturn([
                'verdict' => $verdict,
                'reason' => 'Toelichting.',
                'question' => $question,
            ]);
        });
    }

    public function test_approved_headline_creates_visible_topic(): void
    {
        $this->mockEvaluation(HeadlineVerdict::APPROVED);
        $headline = Headline::factory()->create(['title' => 'Windmolens Isselt']);

        ProcessHeadline::dispatchSync($headline);

        $headline->refresh();
        $topic = $headline->topic;

        $this->assertSame(HeadlineVerdict::APPROVED, $headline->verdict);
        $this->assertSame('Toelichting.', $headline->verdict_reason);
        $this->assertNotNull($topic);
        $this->assertTrue($topic->is_visible);
        $this->assertSame('Windmolens Isselt', $topic->title);
        $this->assertSame(config('news.forum_slug'), $topic->forum->slug);
        $this->assertSame(config('news.username'), $topic->user->username);
    }

    public function test_topic_body_contains_description_question_and_link(): void
    {
        $this->mockEvaluation(HeadlineVerdict::APPROVED);
        $headline = Headline::factory()->create(['description' => 'Een besluit is in zicht.']);

        ProcessHeadline::dispatchSync($headline);

        $body = $headline->refresh()->topic->firstPost->body;

        $this->assertStringContainsString('<p>Een besluit is in zicht.</p>', $body);
        $this->assertStringContainsString('<p>Wat vind jij hiervan?</p>', $body);
        $this->assertStringContainsString('href="'.e($headline->link).'"', $body);
    }

    public function test_topic_body_escapes_feed_content(): void
    {
        $this->mockEvaluation(HeadlineVerdict::APPROVED);
        $headline = Headline::factory()->create(['description' => '<script>alert(1)</script>']);

        ProcessHeadline::dispatchSync($headline);

        $body = $headline->refresh()->topic->firstPost->body;

        $this->assertStringNotContainsString('<script>', $body);
        $this->assertStringContainsString('&lt;script&gt;', $body);
    }

    public function test_topic_body_without_description(): void
    {
        $this->mockEvaluation(HeadlineVerdict::APPROVED);
        $headline = Headline::factory()->create(['description' => null]);

        ProcessHeadline::dispatchSync($headline);

        $body = $headline->refresh()->topic->firstPost->body;

        $this->assertStringStartsWith('<p>Wat vind jij hiervan?</p>', $body);
    }

    public function test_neutral_headline_creates_hidden_topic(): void
    {
        $this->mockEvaluation(HeadlineVerdict::NEUTRAL);
        $headline = Headline::factory()->create();

        ProcessHeadline::dispatchSync($headline);

        $topic = $headline->refresh()->topic;

        $this->assertNotNull($topic);
        $this->assertFalse($topic->is_visible);
    }

    public function test_blocked_headline_creates_no_topic(): void
    {
        $this->mockEvaluation(HeadlineVerdict::BLOCKED, null);
        $headline = Headline::factory()->create();

        ProcessHeadline::dispatchSync($headline);

        $headline->refresh();

        $this->assertSame(HeadlineVerdict::BLOCKED, $headline->verdict);
        $this->assertNull($headline->topic_id);
        $this->assertDatabaseCount('topics', 0);
    }

    public function test_processed_headline_is_not_evaluated_again(): void
    {
        $this->mock(HeadlineEvaluator::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('evaluate');
        });

        $headline = Headline::factory()->verdict(HeadlineVerdict::BLOCKED)->create();

        ProcessHeadline::dispatchSync($headline);

        $this->assertDatabaseCount('topics', 0);
    }

    public function test_news_topic_does_not_subscribe_the_news_user(): void
    {
        $this->mockEvaluation(HeadlineVerdict::APPROVED);
        $headline = Headline::factory()->create();

        ProcessHeadline::dispatchSync($headline);

        $this->assertSame(0, $headline->refresh()->topic->subscribers()->count());
    }
}
