<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class TopicUser extends Pivot
{
    protected $table = 'topic_user';

    protected $casts = [
        'is_subscribed' => 'boolean',
    ];

    /**
     * Relationships
     */
    public function lastReadPost(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'last_read_post_id');
    }

    public function lastNotifiedPost(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'last_notified_post_id');
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
