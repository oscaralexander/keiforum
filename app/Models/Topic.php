<?php

namespace App\Models;

use App\Enums\AdType;
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
     * Attributes
     */
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
