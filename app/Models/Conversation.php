<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Conversation extends Model
{
    protected $guarded = ['id'];

    public const PAGINATE_COUNT = 10;

    /**
     * Relationships
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function lastMessage()
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('last_read_at', 'last_notified_at')
            ->withTimestamps();
    }

    /**
     * Find or create a conversation with exactly the given participant user IDs.
     */
    public static function firstOrCreateForParticipants(array $userIds, ?int $creatorId = null): self
    {
        $userIds = array_values(array_unique(array_map('intval', $userIds)));
        sort($userIds);

        $existing = static::whereHas('users', fn ($q) => $q->whereIn('users.id', $userIds))
            ->with('users')
            ->get()
            ->first(fn ($c) => $c->users->pluck('id')->sort()->values()->all() === $userIds);

        if ($existing) {
            return $existing;
        }

        $conversation = static::create([
            'user_id' => $creatorId,
        ]);
        $conversation->users()->attach($userIds);

        return $conversation;
    }

    /**
     * Other participants (users in this conversation excluding the given user).
     */
    public function otherParticipants(?User $user = null): Collection
    {
        $user ??= auth()->user();

        return $this->users->where('id', '!=', $user->id)->values();
    }
}
