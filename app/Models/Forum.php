<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Forum extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'is_locked' => 'boolean',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function topics(): HasMany
    {
        return $this->hasMany(Topic::class);
    }

    public function latestTopic(): HasOne
    {
        return $this->hasOne(Topic::class)->latestOfMany();
    }

    public function posts(): hasManyThrough
    {
        return $this->hasManyThrough(Post::class, Topic::class);
    }
}

