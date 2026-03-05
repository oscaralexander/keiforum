<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Area extends Model
{
    protected $guarded = ['id'];

    public $timestamps = false;

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function topics(): BelongsToMany
    {
        return $this->belongsToMany(Topic::class);
    }
}
