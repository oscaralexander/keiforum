<?php

namespace App\Models;

use App\Enums\AdType;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
    public function slug(): Attribute
    {
        return new Attribute(
            get: fn () => Str::slug($this->title),
        );
    }

    /**
     * Relationships
     */
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
}
