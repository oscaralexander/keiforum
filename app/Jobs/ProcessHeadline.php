<?php

namespace App\Jobs;

use App\Enums\HeadlineVerdict;
use App\Lib\HeadlineEvaluator;
use App\Lib\NewsArticleImage;
use App\Models\Forum;
use App\Models\Headline;
use App\Models\Post;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class ProcessHeadline implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public function __construct(public Headline $headline) {}

    public function uniqueId(): string
    {
        return (string) $this->headline->id;
    }

    public function handle(HeadlineEvaluator $evaluator, NewsArticleImage $articleImage): void
    {
        Cache::lock("process-headline-{$this->headline->id}", 120)->block(60, function () use ($evaluator, $articleImage): void {
            $headline = $this->headline->fresh();

            if ($headline->verdict !== null) {
                return;
            }

            $evaluation = $evaluator->evaluate($headline);

            if ($evaluation['verdict'] !== HeadlineVerdict::BLOCKED) {
                $this->storeArticleImage($headline, $articleImage);
            }

            DB::transaction(function () use ($headline, $evaluation): void {
                $headline->verdict = $evaluation['verdict'];
                $headline->verdict_reason = $evaluation['reason'];

                if ($evaluation['verdict'] !== HeadlineVerdict::BLOCKED) {
                    $headline->topic_id = $this->createTopic($headline, $evaluation['verdict'], $evaluation['question'])->id;
                }

                $headline->save();
            });
        });
    }

    /**
     * A missing article image never stops the topic from being created.
     */
    protected function storeArticleImage(Headline $headline, NewsArticleImage $articleImage): void
    {
        try {
            $image = $articleImage->fetch($headline->link, $headline->image_url);
        } catch (Throwable $exception) {
            report($exception);

            return;
        }

        if ($image) {
            $headline->article_image_url = $image['url'];
            $headline->article_image_caption = $image['caption'];
            $headline->article_image_credit = $image['credit'];
        }
    }

    protected function createTopic(Headline $headline, HeadlineVerdict $verdict, ?string $question): Topic
    {
        $topic = Topic::query()->create([
            'forum_id' => Forum::query()->where('slug', config('news.forum_slug'))->value('id'),
            'user_id' => User::query()->where('username', config('news.username'))->value('id'),
            'title' => $headline->title,
            'is_visible' => $verdict === HeadlineVerdict::APPROVED,
        ]);

        $body = '';

        if ($headline->description) {
            $body .= '<p>'.e($headline->description).'</p>';
        }

        if ($question) {
            $body .= '<p>'.e($question).'</p>';
        }

        $body .= sprintf('<p><a href="%s">%s</a></p>', e($headline->link), e(__('news.read_more')));

        Post::query()->create([
            'body' => $body,
            'topic_id' => $topic->id,
            'user_id' => $topic->user_id,
        ]);

        return $topic;
    }
}
