<?php

namespace App\Http\Controllers;

use App\Jobs\FetchHeadlines;
use App\Jobs\ProcessHeadline;
use App\Models\Forum;
use App\Models\Headline;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Throwable;

class NewsTopicController extends Controller
{
    /**
     * The feed is fetched at most once per this many seconds when an unknown
     * article is requested.
     */
    private const REFETCH_INTERVAL = 60;

    public function __invoke(int $articleId): RedirectResponse
    {
        $headline = Headline::query()->where('article_id', $articleId)->first();

        if (! $headline && Cache::add('news-feed-refetch', true, self::REFETCH_INTERVAL)) {
            FetchHeadlines::dispatchSync();
            $headline = Headline::query()->where('article_id', $articleId)->first();
        }

        if ($headline && $headline->verdict === null) {
            try {
                ProcessHeadline::dispatchSync($headline);
                $headline->refresh();
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        if ($topic = $headline?->topic) {
            return redirect()->route('topic.show', [$topic->forum, $topic, $topic->slug]);
        }

        return redirect()->route('forum.show', Forum::query()->where('slug', config('news.forum_slug'))->firstOrFail());
    }
}
