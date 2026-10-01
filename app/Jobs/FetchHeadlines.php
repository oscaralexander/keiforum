<?php

namespace App\Jobs;

use App\Models\Headline;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use SimpleXMLElement;

class FetchHeadlines implements ShouldQueue
{
    use Queueable;

    /**
     * Headlines older than this are no longer turned into topics.
     */
    public const PROCESS_WITHIN_DAYS = 2;

    public function handle(): void
    {
        $response = Http::get(config('news.feed_url'));

        if (! $response->successful()) {
            return;
        }

        $xml = new SimpleXMLElement($response->body());

        foreach ($xml->channel->item as $item) {
            $guid = (string) $item->guid;

            if (Headline::query()->where('guid', $guid)->exists()) {
                continue;
            }

            $enclosureUrl = null;
            if (isset($item->enclosure)) {
                $enclosureUrl = (string) $item->enclosure->attributes()['url'];
            }

            $link = (string) $item->link;
            $description = trim((string) $item->description);

            Headline::query()->create([
                'guid' => $guid,
                'article_id' => Headline::articleIdFromLink($link),
                'title' => (string) $item->title,
                'description' => $description !== '' ? $description : null,
                'link' => $link,
                'image_url' => $enclosureUrl,
                'pub_date' => Carbon::parse((string) $item->pubDate),
            ]);
        }

        Headline::query()
            ->whereNull('verdict')
            ->where('pub_date', '>=', now()->subDays(self::PROCESS_WITHIN_DAYS))
            ->each(fn (Headline $headline) => ProcessHeadline::dispatch($headline));
    }
}
