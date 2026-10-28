<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Forum extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'is_hidden' => 'boolean',
        'is_locked' => 'boolean',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * The hidden forum holding the topics behind activity replies.
     */
    public static function agenda(): self
    {
        return static::query()->firstOrCreate(
            ['slug' => config('agenda.forum_slug')],
            [
                'description' => 'Reacties op activiteiten in de agenda.',
                'icon' => 'calendar',
                'is_hidden' => true,
                'is_locked' => true,
                'name' => 'Agenda',
            ],
        );
    }

    /**
     * Scopes
     */
    #[Scope]
    protected function listed(Builder $query): void
    {
        $query->where('forums.is_hidden', false);
    }

    /**
     * Attributes
     */
    public function isMarketplace(): Attribute
    {
        return new Attribute(
            get: fn () => $this->id === 2,
        );
    }

    /**
     * Relationships
     */
    public function topics(): HasMany
    {
        return $this->hasMany(Topic::class);
    }

    public function latestTopic(): HasOne
    {
        return $this->hasOne(Topic::class)->latestOfMany();
    }

    public function posts(): HasManyThrough
    {
        return $this->hasManyThrough(Post::class, Topic::class);
    }
}
