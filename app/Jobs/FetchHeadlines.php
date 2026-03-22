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

    public function __construct() {}

    public function handle(): void
    {
        $response = Http::get('https://www.nieuwsplein33.nl/rss/nieuws.xml');

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

            Headline::query()->create([
                'guid' => $guid,
                'title' => (string) $item->title,
                'link' => (string) $item->link,
                'image_url' => $enclosureUrl,
                'pub_date' => Carbon::parse((string) $item->pubDate),
            ]);
        }
    }
}
