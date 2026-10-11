<?php

namespace App\Jobs;

use App\Enums\AvatarSize;
use App\Mail\WeeklyDigest;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendWeeklyDigest implements ShouldQueue
{
    use Queueable;

    /**
     * Avatars are shown at 32px; twice that keeps them sharp on high-density
     * screens. A standard avatar size is used, so cached copies are cleared
     * when a member changes their avatar.
     */
    private const AVATAR_SIZE = AvatarSize::S;

    public function handle(): void
    {
        $digest = $this->digest(now()->subDays(config('digest.days')));

        if ($digest['active_topics_count'] < config('digest.min_active_topics')) {
            Log::info('Weekly digest skipped: too little activity.', ['active_topics' => $digest['active_topics_count']]);

            return;
        }

        $position = 0;

        User::query()
            ->digestRecipients()
            ->each(function (User $user) use ($digest, &$position): void {
                $mail = (new WeeklyDigest($user, $digest))
                    ->onQueue('notifications')
                    ->delay(now()->addSeconds($position++ * config('digest.send_interval')));

                Mail::to($user)->queue($mail);
            });
    }

    /**
     * Posts by the news account don't count as activity: news topics only
     * count once a member replied.
     *
     * @return array{
     *     active_topics_count: int,
     *     new_members_count: int,
     *     new_topics: list<array{title: string, url: string, forum: string, posts_count: int, avatar_url: string, username: string}>,
     *     popular_topics: list<array{title: string, url: string, forum: string, posts_count: int, avatar_url: string, username: string}>,
     * }
     */
    public function digest(Carbon $since): array
    {
        $newsUserId = User::query()->where('username', config('news.username'))->value('id');

        $memberPostsSince = fn (Builder $query) => $query
            ->where('created_at', '>=', $since)
            ->when($newsUserId, fn (Builder $query) => $query->where('user_id', '!=', $newsUserId));

        $digestTopics = fn () => Topic::query()
            ->visible()
            ->whereRelation('forum', fn (Builder $query) => $query->whereIn('slug', config('digest.forums')))
            ->withCount(['posts' => $memberPostsSince])
            ->with(['forum', 'latestPost.user', 'user']);

        $popularTopics = $this->fillFromForums(
            $digestTopics()->whereHas('posts', $memberPostsSince)->orderByDesc('posts_count')->latest(),
            config('digest.popular_topics'),
        );

        $newTopics = $this->fillFromForums(
            $digestTopics()
                ->where('created_at', '>=', $since)
                ->when($newsUserId, fn (Builder $query) => $query->where('user_id', '!=', $newsUserId))
                ->whereNotIn('id', $popularTopics->pluck('id'))
                ->latest(),
            config('digest.new_topics'),
        );

        return [
            'active_topics_count' => $digestTopics()->whereHas('posts', $memberPostsSince)->count(),
            'new_members_count' => User::query()->members()->where('created_at', '>=', $since)->count(),
            'new_topics' => $newTopics->map($this->topicSummary(...))->all(),
            'popular_topics' => $popularTopics->map($this->topicSummary(...))->all(),
        ];
    }

    /**
     * Takes topics from the first digest forum, and only pads with the
     * next forums when it has too few.
     *
     * @param  Builder<Topic>  $query
     * @return Collection<int, Topic>
     */
    private function fillFromForums(Builder $query, int $limit): Collection
    {
        $topics = collect();

        foreach (config('digest.forums') as $slug) {
            if ($topics->count() >= $limit) {
                break;
            }

            $topics = $topics->concat(
                (clone $query)->whereRelation('forum', 'slug', $slug)->limit($limit - $topics->count())->get()
            );
        }

        return $topics;
    }

    /**
     * @return array{title: string, url: string, forum: string, posts_count: int, avatar_url: string, username: string}
     */
    private function topicSummary(Topic $topic): array
    {
        $latestPoster = $topic->latestPost?->user ?? $topic->user;

        return [
            'title' => $topic->title,
            'url' => route('topic.show', [$topic->forum, $topic, $topic->slug]),
            'forum' => $topic->forum->name,
            'posts_count' => $topic->posts_count,
            'avatar_url' => $latestPoster->emailAvatarUrl(self::AVATAR_SIZE->value),
            'username' => $latestPoster->username,
        ];
    }
}
