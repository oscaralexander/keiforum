<?php

namespace App\Models;

use App\Enums\HeadlineVerdict;
use App\Lib\NewsArticleImage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Throwable;

class Headline extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'pub_date' => 'datetime',
            'verdict' => HeadlineVerdict::class,
        ];
    }

    /**
     * Extract the Nieuwsplein33 article ID from an article URL, e.g.
     * https://www.nieuwsplein33.nl/nieuws/4097209/some-slug
     */
    public static function articleIdFromLink(string $link): ?int
    {
        if (preg_match('#/nieuws/(\d+)#', $link, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    /**
     * Approve the headline: its topic becomes visible right away, and is
     * created or restored when the headline was blocked before.
     */
    public function accept(): void
    {
        $topic = $this->topic()->withTrashed()->first();

        if (! $topic && ! $this->article_image_url) {
            $this->fetchArticleImage();
        }

        DB::transaction(function () use ($topic): void {
            $this->verdict = HeadlineVerdict::APPROVED;

            if ($topic) {
                $topic->restore();
                $topic->update(['is_visible' => true]);
            } else {
                $this->topic_id = $this->createTopic(null)->id;
            }

            $this->save();
        });
    }

    /**
     * Block the headline and remove its topic. Accepting it again restores
     * the topic, replies included.
     */
    public function reject(): void
    {
        DB::transaction(function (): void {
            $this->update(['verdict' => HeadlineVerdict::BLOCKED]);
            $this->topic?->delete();
        });
    }

    /**
     * Create the news topic for this headline. Topics of approved headlines
     * are visible right away; others once someone replied.
     */
    public function createTopic(?string $question): Topic
    {
        $topic = Topic::query()->create([
            'forum_id' => Forum::query()->where('slug', config('news.forum_slug'))->value('id'),
            'user_id' => User::query()->where('username', config('news.username'))->value('id'),
            'title' => $this->title,
            'is_visible' => $this->verdict === HeadlineVerdict::APPROVED,
        ]);

        $body = '';

        if ($this->description) {
            $body .= '<p>'.e($this->description).'</p>';
        }

        if ($question) {
            $body .= '<p>'.e($question).'</p>';
        }

        $body .= sprintf('<p><a href="%s">%s</a></p>', e($this->link), e(__('news.read_more')));

        Post::query()->create([
            'body' => $body,
            'topic_id' => $topic->id,
            'user_id' => $topic->user_id,
        ]);

        return $topic;
    }

    /**
     * Look up the article's lead image, caption and credit. A missing image
     * never stops a topic from being created.
     */
    public function fetchArticleImage(): void
    {
        try {
            $image = app(NewsArticleImage::class)->fetch($this->link, $this->image_url);
        } catch (Throwable $exception) {
            report($exception);

            return;
        }

        if ($image) {
            $this->article_image_url = $image['url'];
            $this->article_image_caption = $image['caption'];
            $this->article_image_credit = $image['credit'];
        }
    }

    /**
     * Whether the article image should be shown above the topic's opening post.
     */
    public function showsArticleImage(): bool
    {
        return config('news.show_article_image') && $this->article_image_url !== null;
    }

    /**
     * Relationships
     */
    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }
}
