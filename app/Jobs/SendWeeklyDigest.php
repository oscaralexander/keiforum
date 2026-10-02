<?php

namespace App\Jobs;

use App\Mail\WeeklyDigest;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendWeeklyDigest implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $digest = $this->digest(now()->subDays(config('digest.days')));

        if ($digest['active_topics_count'] < config('digest.min_active_topics')) {
            Log::info('Weekly digest skipped: too little activity.', ['active_topics' => $digest['active_topics_count']]);

            return;
        }

        User::query()
            ->digestRecipients()
            ->each(fn (User $user) => Mail::to($user)->queue((new WeeklyDigest($user, $digest))->onQueue('notifications')));
    }

    /**
     * Posts by the news account don't count as activity: news topics only
     * count once a member replied.
     *
     * @return array{
     *     active_topics_count: int,
     *     new_members_count: int,
     *     new_topics: list<array{title: string, url: string, forum: string, posts_count: int}>,
     *     popular_topics: list<array{title: string, url: string, forum: string, posts_count: int}>,
     * }
     */
    public function digest(Carbon $since): array
    {
        $newsUserId = User::query()->where('username', config('news.username'))->value('id');

        $memberPostsSince = fn (Builder $query) => $query
            ->where('created_at', '>=', $since)
            ->when($newsUserId, fn (Builder $query) => $query->where('user_id', '!=', $newsUserId));

        $popularTopics = Topic::query()
            ->visible()
            ->whereHas('posts', $memberPostsSince)
            ->withCount(['posts' => $memberPostsSince])
            ->with('forum')
            ->orderByDesc('posts_count')
            ->latest()
            ->limit(config('digest.popular_topics'))
            ->get();

        $newTopics = Topic::query()
            ->visible()
            ->where('created_at', '>=', $since)
            ->when($newsUserId, fn (Builder $query) => $query->where('user_id', '!=', $newsUserId))
            ->whereNotIn('id', $popularTopics->pluck('id'))
            ->withCount(['posts' => $memberPostsSince])
            ->with('forum')
            ->latest()
            ->limit(config('digest.new_topics'))
            ->get();

        return [
            'active_topics_count' => Topic::query()->visible()->whereHas('posts', $memberPostsSince)->count(),
            'new_members_count' => User::query()->members()->where('created_at', '>=', $since)->count(),
            'new_topics' => $newTopics->map($this->topicSummary(...))->all(),
            'popular_topics' => $popularTopics->map($this->topicSummary(...))->all(),
        ];
    }

    /**
     * @return array{title: string, url: string, forum: string, posts_count: int}
     */
    private function topicSummary(Topic $topic): array
    {
        return [
            'title' => $topic->title,
            'url' => route('topic.show', [$topic->forum, $topic, $topic->slug]),
            'forum' => $topic->forum->name,
            'posts_count' => $topic->posts_count,
        ];
    }
}
