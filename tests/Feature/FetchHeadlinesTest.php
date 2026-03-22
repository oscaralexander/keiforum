<?php

namespace Tests\Feature;

use App\Jobs\FetchHeadlines;
use App\Models\Headline;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FetchHeadlinesTest extends TestCase
{
    use RefreshDatabase;

    private function makeFeedXml(array $items): string
    {
        $itemsXml = '';

        foreach ($items as $item) {
            $enclosure = isset($item['image'])
                ? "<enclosure url=\"{$item['image']}\" />"
                : '';

            $itemsXml .= '<item>'
                ."<title><![CDATA[{$item['title']}]]></title>"
                ."<link>{$item['link']}</link>"
                ."<guid>{$item['guid']}</guid>"
                ."<pubDate>{$item['pubDate']}</pubDate>"
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

    public function test_it_is_scheduled_hourly(): void
    {
        $schedule = app(\Illuminate\Console\Scheduling\Schedule::class);

        $isScheduled = collect($schedule->events())
            ->contains(fn ($event) => str_contains($event->command ?? $event->description ?? '', 'FetchHeadlines'));

        $this->assertTrue($isScheduled, 'FetchHeadlines is not scheduled hourly.');
    }
}
