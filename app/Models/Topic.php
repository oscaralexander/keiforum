<?php

namespace App\Models;

use App\Enums\AdType;
use App\Enums\HeadlineVerdict;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Topic extends Model
{
    use HasFactory, SoftDeletes;

    public const PAGINATE_COUNT = 25;

    protected $guarded = ['id'];

    protected $casts = [
        'ad_type' => AdType::class,
        'is_locked' => 'boolean',
        'is_pinned' => 'boolean',
        'is_visible' => 'boolean',
    ];

    protected $with = ['user'];

    public function postUsers(): Collection
    {
        return $this->posts
            ->groupBy('user_id')
            ->map(function ($posts) {
                return [
                    'post_count' => $posts->count(),
                    'user' => $posts->first()->user,
                ];
            })
            ->sortByDesc('post_count');
    }

    /**
     * Topics created from a news headline stay hidden from listings until
     * Claude approved the headline or someone replied.
     */
    public function refreshVisibility(): void
    {
        $headline = $this->headline;

        if (! $headline || $headline->verdict === HeadlineVerdict::APPROVED) {
            return;
        }

        $isVisible = $this->posts()->count() > 1;

        if ($this->is_visible !== $isVisible) {
            $this->update(['is_visible' => $isVisible]);
        }
    }

    /**
     * Change the verdict of the news headline behind this topic: approved
     * topics are visible right away, neutral ones once someone replied.
     */
    public function setNewsVerdict(HeadlineVerdict $verdict): void
    {
        $this->headline->update(['verdict' => $verdict]);

        if ($verdict === HeadlineVerdict::APPROVED) {
            $this->update(['is_visible' => true]);
        } else {
            $this->refreshVisibility();
        }
    }

    /**
     * The image shown at the top of the opening post: the article image of a
     * news topic, or else the first image linked in the post.
     */
    public function openingImageUrl(): ?string
    {
        if ($this->headline?->showsArticleImage()) {
            return $this->headline->article_image_url;
        }

        $body = $this->firstPost?->body ?? '';

        if (preg_match('/<a[^>]*href=["\'](https?:\/\/[^"\']+\.(?:jpe?g|png|webp|avif|gif))["\']/i', $body, $matches)) {
            return html_entity_decode($matches[1]);
        }

        return null;
    }

    /**
     * Scopes
     */
    #[Scope]
    protected function visible(Builder $query): void
    {
        $query->where('topics.is_visible', true);
    }

    /**
     * Attributes
     */
    /**
     * Plain text summary of the opening post for meta and sharing previews,
     * without HTML or URLs.
     */
    public function description(): Attribute
    {
        return new Attribute(
            get: function (): string {
                $text = preg_replace('/<\/(?:p|li|ol|ul|h\d|blockquote)>|<br\s*\/?>/i', '$0 ', $this->firstPost?->body ?? '');
                $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5);
                $text = preg_replace('/\b(?:https?:\/\/|www\.)\S+/i', '', $text);

                return Str::limit(Str::squish($text), 200);
            },
        );
    }

    public function hasReplies(): Attribute
    {
        return new Attribute(
            get: fn () => $this->posts()->count() > 1,
        );
    }

    public function slug(): Attribute
    {
        return new Attribute(
            get: fn () => Str::slug($this->title),
        );
    }

    /**
     * Relationships
     */
    public function firstPost(): HasOne
    {
        return $this->hasOne(Post::class)->oldestOfMany();
    }

    public function headline(): HasOne
    {
        return $this->hasOne(Headline::class);
    }

    public function poll(): HasOne
    {
        return $this->hasOne(Poll::class);
    }

    public function pollVotes(): HasManyThrough
    {
        return $this->hasManyThrough(PollVote::class, Poll::class);
    }

    public function forum(): BelongsTo
    {
        return $this->belongsTo(Forum::class);
    }

    public function latestPost(): HasOne
    {
        return $this->hasOne(Post::class)->latestOfMany();
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function areas(): BelongsToMany
    {
        return $this->belongsToMany(Area::class);
    }

    public function subscribers(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->using(TopicUser::class)
            ->withPivot('last_read_post_id', 'is_subscribed', 'last_notified_post_id')
            ->withTimestamps()
            ->wherePivot('is_subscribed', true);
    }

    public function trackedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->using(TopicUser::class)
            ->withPivot('last_read_post_id', 'is_subscribed', 'last_notified_post_id')
            ->withTimestamps();
    }
}
