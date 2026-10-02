<?php

namespace Tests\Feature;

use App\Enums\HeadlineVerdict;
use App\Jobs\FetchHeadlines;
use App\Jobs\ProcessHeadline;
use App\Models\Headline;
use App\Models\Post;
use App\Models\Topic;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class FetchHeadlinesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
    }

    private function makeFeedXml(array $items): string
    {
        $itemsXml = '';

        foreach ($items as $item) {
            $description = isset($item['description'])
                ? "<description><![CDATA[{$item['description']}]]></description>"
                : '';

            $enclosure = isset($item['image'])
                ? "<enclosure url=\"{$item['image']}\" />"
                : '';

            $itemsXml .= '<item>'
                ."<title><![CDATA[{$item['title']}]]></title>"
                ."<link>{$item['link']}</link>"
                ."<guid>{$item['guid']}</guid>"
                ."<pubDate>{$item['pubDate']}</pubDate>"
                ."{$description}"
                ."{$enclosure}"
                .'</item>';
        }

        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<rss version="2.0"><channel>'
            .$itemsXml
            .'</channel></rss>';
    }

    public function test_it_saves_new_headlines_from_feed(): void
    {
        Http::fake([
            '*' => Http::response($this->makeFeedXml([
                [
                    'guid' => 'https://example.com/article-1',
                    'title' => 'Eerste nieuwsbericht',
                    'link' => 'https://example.com/article-1',
                    'pubDate' => 'Mon, 20 Mar 2026 10:00:00 +0100',
                    'image' => 'https://example.com/image-1.jpg',
                ],
            ]), 200),
        ]);

        (new FetchHeadlines)->handle();

        $this->assertDatabaseCount('headlines', 1);

        $headline = Headline::first();
        $this->assertSame('Eerste nieuwsbericht', $headline->title);
        $this->assertSame('https://example.com/article-1', $headline->guid);
        $this->assertSame('https://example.com/article-1', $headline->link);
        $this->assertSame('https://example.com/image-1.jpg', $headline->image_url);
        $this->assertNotNull($headline->pub_date);
    }

    public function test_it_skips_existing_headlines(): void
    {
        Headline::factory()->create(['guid' => 'https://example.com/article-1']);

        Http::fake([
            '*' => Http::response($this->makeFeedXml([
                [
                    'guid' => 'https://example.com/article-1',
                    'title' => 'Eerste nieuwsbericht',
                    'link' => 'https://example.com/article-1',
                    'pubDate' => 'Mon, 20 Mar 2026 10:00:00 +0100',
                ],
            ]), 200),
        ]);

        (new FetchHeadlines)->handle();

        $this->assertDatabaseCount('headlines', 1);
    }

    public function test_it_handles_items_without_enclosure(): void
    {
        Http::fake([
            '*' => Http::response($this->makeFeedXml([
                [
                    'guid' => 'https://example.com/no-image',
                    'title' => 'Bericht zonder afbeelding',
                    'link' => 'https://example.com/no-image',
                    'pubDate' => 'Mon, 20 Mar 2026 10:00:00 +0100',
                ],
            ]), 200),
        ]);

        (new FetchHeadlines)->handle();

        $this->assertDatabaseCount('headlines', 1);
        $this->assertNull(Headline::first()->image_url);
    }

    public function test_it_does_nothing_on_failed_request(): void
    {
        Http::fake([
            '*' => Http::response('', 500),
        ]);

        (new FetchHeadlines)->handle();

        $this->assertDatabaseCount('headlines', 0);
    }

    public function test_it_saves_description_and_article_id(): void
    {
        Http::fake([
            '*' => Http::response($this->makeFeedXml([
                [
                    'guid' => 'https://www.nieuwsplein33.nl/nieuws/4097209/-',
                    'title' => 'Windmolens Isselt',
                    'link' => 'https://www.nieuwsplein33.nl/nieuws/4097209/windmolens-isselt',
                    'pubDate' => now()->toRssString(),
                    'description' => 'Een besluit over twee windmolens is in zicht.',
                ],
            ]), 200),
        ]);

        (new FetchHeadlines)->handle();

        $headline = Headline::first();
        $this->assertSame(4097209, $headline->article_id);
        $this->assertSame('Een besluit over twee windmolens is in zicht.', $headline->description);
    }

    public function test_it_dispatches_processing_for_new_headlines(): void
    {
        Http::fake([
            '*' => Http::response($this->makeFeedXml([
                [
                    'guid' => 'https://www.nieuwsplein33.nl/nieuws/4097209/-',
                    'title' => 'Windmolens Isselt',
                    'link' => 'https://www.nieuwsplein33.nl/nieuws/4097209/windmolens-isselt',
                    'pubDate' => now()->toRssString(),
                ],
            ]), 200),
        ]);

        (new FetchHeadlines)->handle();

        Queue::assertPushed(ProcessHeadline::class, fn (ProcessHeadline $job) => $job->headline->article_id === 4097209);
    }

    public function test_it_retries_recent_unprocessed_headlines(): void
    {
        Http::fake(['*' => Http::response($this->makeFeedXml([]), 200)]);

        $pending = Headline::factory()->create(['pub_date' => now()->subHour()]);
        Headline::factory()->create(['pub_date' => now()->subDays(FetchHeadlines::PROCESS_WITHIN_DAYS + 1)]);
        Headline::factory()->verdict(HeadlineVerdict::APPROVED)->create(['pub_date' => now()->subHour()]);

        (new FetchHeadlines)->handle();

        Queue::assertPushed(ProcessHeadline::class, 1);
        Queue::assertPushed(ProcessHeadline::class, fn (ProcessHeadline $job) => $job->headline->is($pending));
    }

    private function feedWithDescription(string $description): string
    {
        return $this->makeFeedXml([
            [
                'guid' => 'https://www.nieuwsplein33.nl/nieuws/4097209/-',
                'title' => 'Windmolens Isselt',
                'link' => 'https://www.nieuwsplein33.nl/nieuws/4097209/windmolens-isselt',
                'pubDate' => now()->toRssString(),
                'description' => $description,
            ],
        ]);
    }

    public function test_it_fills_missing_description_of_existing_headline(): void
    {
        Http::fake(['*' => Http::response($this->feedWithDescription('Een besluit is in zicht.'), 200)]);
        $headline = Headline::factory()->create(['guid' => 'https://www.nieuwsplein33.nl/nieuws/4097209/-', 'description' => null]);

        (new FetchHeadlines)->handle();

        $this->assertSame('Een besluit is in zicht.', $headline->fresh()->description);
        $this->assertDatabaseCount('headlines', 1);
    }

    public function test_it_adds_missing_description_to_existing_topic(): void
    {
        Http::fake(['*' => Http::response($this->feedWithDescription('Een besluit is in zicht.'), 200)]);
        $topic = Topic::factory()->create();
        $post = Post::factory()->create(['topic_id' => $topic->id, 'body' => '<p>Wat vind jij?</p>']);
        Headline::factory()->verdict(HeadlineVerdict::APPROVED)->create([
            'guid' => 'https://www.nieuwsplein33.nl/nieuws/4097209/-',
            'description' => null,
            'topic_id' => $topic->id,
        ]);

        (new FetchHeadlines)->handle();
        (new FetchHeadlines)->handle();

        $this->assertSame('<p>Een besluit is in zicht.</p><p>Wat vind jij?</p>', $post->fresh()->body);
    }

    public function test_it_keeps_existing_description(): void
    {
        Http::fake(['*' => Http::response($this->feedWithDescription('Nieuwe tekst.'), 200)]);
        $topic = Topic::factory()->create();
        $post = Post::factory()->create(['topic_id' => $topic->id, 'body' => '<p>Oude tekst.</p>']);
        $headline = Headline::factory()->create([
            'guid' => 'https://www.nieuwsplein33.nl/nieuws/4097209/-',
            'description' => 'Oude tekst.',
            'topic_id' => $topic->id,
        ]);

        (new FetchHeadlines)->handle();

        $this->assertSame('Oude tekst.', $headline->fresh()->description);
        $this->assertSame('<p>Oude tekst.</p>', $post->fresh()->body);
    }

    public function test_it_processes_headlines_from_oldest_to_newest(): void
    {
        Http::fake([
            '*' => Http::response($this->makeFeedXml([
                [
                    'guid' => 'https://www.nieuwsplein33.nl/nieuws/3/-',
                    'title' => 'Nieuwste',
                    'link' => 'https://www.nieuwsplein33.nl/nieuws/3/nieuwste',
                    'pubDate' => now()->subHour()->toRssString(),
                ],
                [
                    'guid' => 'https://www.nieuwsplein33.nl/nieuws/2/-',
                    'title' => 'Middelste',
                    'link' => 'https://www.nieuwsplein33.nl/nieuws/2/middelste',
                    'pubDate' => now()->subHours(2)->toRssString(),
                ],
                [
                    'guid' => 'https://www.nieuwsplein33.nl/nieuws/1/-',
                    'title' => 'Oudste',
                    'link' => 'https://www.nieuwsplein33.nl/nieuws/1/oudste',
                    'pubDate' => now()->subHours(3)->toRssString(),
                ],
            ]), 200),
        ]);

        (new FetchHeadlines)->handle();

        $titles = Queue::pushed(ProcessHeadline::class)
            ->map(fn (ProcessHeadline $job) => $job->headline->title)
            ->values()
            ->all();

        $this->assertSame(['Oudste', 'Middelste', 'Nieuwste'], $titles);
    }

    public function test_it_is_scheduled_every_fifteen_minutes(): void
    {
        $event = collect(app(Schedule::class)->events())
            ->first(fn ($event) => str_contains($event->command ?? $event->description ?? '', 'FetchHeadlines'));

        $this->assertNotNull($event, 'FetchHeadlines is not scheduled.');
        $this->assertSame('*/15 * * * *', $event->expression);
    }
}
