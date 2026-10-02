<?php

namespace Tests\Feature;

use App\Enums\HeadlineVerdict;
use App\Lib\HeadlineEvaluator;
use App\Models\Forum;
use App\Models\Headline;
use App\Models\Post;
use App\Models\Topic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

class NewsTopicControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake(['www.nieuwsplein33.nl/nieuws/*' => Http::response('', 404)]);
    }

    private function newsForum(): Forum
    {
        return Forum::query()->where('slug', config('news.forum_slug'))->firstOrFail();
    }

    private function feedXml(int $articleId): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?><rss version="2.0"><channel><item>'
            .'<title>Nieuw artikel</title>'
            ."<link>https://www.nieuwsplein33.nl/nieuws/{$articleId}/nieuw-artikel</link>"
            ."<guid>https://www.nieuwsplein33.nl/nieuws/{$articleId}/-</guid>"
            .'<pubDate>'.now()->toRssString().'</pubDate>'
            .'</item></channel></rss>';
    }

    public function test_redirects_to_topic_of_processed_headline(): void
    {
        $topic = Topic::factory()->create();
        $headline = Headline::factory()->verdict(HeadlineVerdict::NEUTRAL)->create(['topic_id' => $topic->id]);

        $this->get(route('news.topic', $headline->article_id))
            ->assertRedirect(route('topic.show', [$topic->forum, $topic, $topic->slug]));
    }

    public function test_redirects_to_news_forum_for_blocked_headline(): void
    {
        $headline = Headline::factory()->verdict(HeadlineVerdict::BLOCKED)->create();

        $this->get(route('news.topic', $headline->article_id))
            ->assertRedirect(route('forum.show', $this->newsForum()));
    }

    public function test_redirects_to_news_forum_when_topic_was_deleted(): void
    {
        $topic = Topic::factory()->create();
        $headline = Headline::factory()->verdict(HeadlineVerdict::APPROVED)->create(['topic_id' => $topic->id]);
        $topic->delete();

        $this->get(route('news.topic', $headline->article_id))
            ->assertRedirect(route('forum.show', $this->newsForum()));
    }

    public function test_processes_unevaluated_headline_before_redirecting(): void
    {
        $this->mock(HeadlineEvaluator::class, function (MockInterface $mock): void {
            $mock->shouldReceive('evaluate')->once()->andReturn([
                'verdict' => HeadlineVerdict::NEUTRAL,
                'reason' => 'Toelichting.',
                'question' => 'Wat vind jij?',
            ]);
        });

        $headline = Headline::factory()->create();

        $response = $this->get(route('news.topic', $headline->article_id));

        $topic = $headline->refresh()->topic;
        $this->assertNotNull($topic);
        $response->assertRedirect(route('topic.show', [$topic->forum, $topic, $topic->slug]));
    }

    public function test_falls_back_to_news_forum_when_evaluation_fails(): void
    {
        $this->mock(HeadlineEvaluator::class, function (MockInterface $mock): void {
            $mock->shouldReceive('evaluate')->andThrow(new RuntimeException('API down'));
        });

        $headline = Headline::factory()->create();

        $this->get(route('news.topic', $headline->article_id))
            ->assertRedirect(route('forum.show', $this->newsForum()));

        $this->assertNull($headline->refresh()->verdict);
    }

    public function test_fetches_feed_for_unknown_article(): void
    {
        Http::fake(['*' => Http::response($this->feedXml(1234567), 200)]);

        $this->mock(HeadlineEvaluator::class, function (MockInterface $mock): void {
            $mock->shouldReceive('evaluate')->once()->andReturn([
                'verdict' => HeadlineVerdict::APPROVED,
                'reason' => 'Toelichting.',
                'question' => 'Wat vind jij?',
            ]);
        });

        $response = $this->get(route('news.topic', 1234567));

        $topic = Headline::query()->where('article_id', 1234567)->firstOrFail()->topic;
        $response->assertRedirect(route('topic.show', [$topic->forum, $topic, $topic->slug]));
    }

    public function test_fetches_feed_at_most_once_per_interval(): void
    {
        Http::fake(['*' => Http::response($this->feedXml(1234567), 200)]);

        $this->mock(HeadlineEvaluator::class, function (MockInterface $mock): void {
            $mock->shouldReceive('evaluate')->once()->andReturn([
                'verdict' => HeadlineVerdict::BLOCKED,
                'reason' => 'Toelichting.',
                'question' => null,
            ]);
        });

        $this->get(route('news.topic', 7654321))->assertRedirect(route('forum.show', $this->newsForum()));
        $this->get(route('news.topic', 7654322))->assertRedirect(route('forum.show', $this->newsForum()));

        Http::assertSentCount(1);
    }

    public function test_redirects_to_hidden_topic(): void
    {
        $topic = Topic::factory()->create(['is_visible' => false]);
        Post::factory()->create(['topic_id' => $topic->id]);
        $headline = Headline::factory()->verdict(HeadlineVerdict::NEUTRAL)->create(['topic_id' => $topic->id]);

        $this->get(route('news.topic', $headline->article_id))
            ->assertRedirect(route('topic.show', [$topic->forum, $topic, $topic->slug]));
    }

    public function test_rejects_non_numeric_article_id(): void
    {
        $this->get('/praat-mee/abc')->assertNotFound();
    }
}
