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
            $description = trim((string) $item->description);
            $headline = Headline::query()->where('guid', $guid)->first();

            if ($headline) {
                $this->fillMissingDescription($headline, $description);

                continue;
            }

            $enclosureUrl = null;
            if (isset($item->enclosure)) {
                $enclosureUrl = (string) $item->enclosure->attributes()['url'];
            }

            $link = (string) $item->link;

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
            ->orderBy('pub_date')
            ->orderBy('id')
            ->each(fn (Headline $headline) => ProcessHeadline::dispatch($headline));
    }

    /**
     * Headlines fetched before descriptions were stored get theirs from the
     * feed, including the opening post of a topic that was already created.
     */
    protected function fillMissingDescription(Headline $headline, string $description): void
    {
        if ($headline->description !== null || $description === '') {
            return;
        }

        $headline->update(['description' => $description]);

        $post = $headline->topic?->firstPost;
        $descriptionHtml = '<p>'.e($description).'</p>';

        if ($post && ! str_contains($post->body, $descriptionHtml)) {
            $post->update(['body' => $descriptionHtml.$post->body]);
        }
    }
}
