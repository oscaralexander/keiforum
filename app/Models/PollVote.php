<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PollVote extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'poll_id' => 'integer',
            'poll_option_id' => 'integer',
            'user_id' => 'integer',
        ];
    }

    /**
     * Relationships
     */
    public function poll(): BelongsTo
    {
        return $this->belongsTo(Poll::class);
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(PollOption::class, 'poll_option_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
