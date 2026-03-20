<?php

namespace App\Models;

use App\Enums\Gender;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $casts = [
        'banned_until' => 'datetime',
        'birthdate' => 'date',
        'email_verified_at' => 'datetime',
        'gender' => Gender::class,
        'has_avatar' => 'boolean',
        'is_admin' => 'boolean',
        'last_seen_at' => 'datetime',
        'password' => 'hashed',
    ];

    protected $guarded = ['id'];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public const USERNAME_MAX_LENGTH = 20;

    public function avatarUrl(int $size = 256): string
    {
        $initial = strtolower(is_numeric($this->username[0]) ? '0' : $this->username[0]);

        return $this->has_avatar
            ? route('img', ['src' => $this->avatar, 'w' => $size, 'h' => $size, 'q' => 80])
            : '/assets/img/avatar/webp/'.$initial.'.webp';
    }

    public function getRouteKeyName(): string
    {
        return 'username';
    }

    /**
     * Attributes
     */

    public function age(): Attribute
    {
        return new Attribute(
            get: fn (): int => $this->birthdate ? $this->birthdate->diffInYears(now()) : 0,
        );
    }

    public function avatar(): Attribute
    {
        return new Attribute(
            get: fn ($value) => env('APP_PATH_AVATARS').DIRECTORY_SEPARATOR.$this->username.'.webp',
        );
    }

    public function emailName(): Attribute
    {
        return new Attribute(
            get: fn (): string => empty($this->firstName) ? $this->username : $this->firstName,
        );
    }

    public function firstName(): Attribute
    {
        return new Attribute(
            get: fn (): string => explode(' ', $this->name)[0],
        );
    }

    public function isBanned(): Attribute
    {
        return new Attribute(
            get: fn (): bool => $this->banned_until && $this->banned_until->isAfter(now()),
        );
    }

    public function isOnline(): Attribute
    {
        return new Attribute(
            get: fn (): bool => $this->last_seen_at && $this->last_seen_at->isAfter(now()->subMinutes(5)),
        );
    }

    public function lastName(): Attribute
    {
        return new Attribute(
            get: fn (): string => explode(' ', $this->name)[1],
        );
    }

    public function unreadMessagesCount(): Attribute
    {
        $attribute = new Attribute(
            get: fn (): int => Message::join('conversation_user', function ($join) {
                $join->on('messages.conversation_id', '=', 'conversation_user.conversation_id')
                    ->where('conversation_user.user_id', $this->id);
            })
                ->where('messages.user_id', '!=', $this->id)
                ->where(function ($query) {
                    $query->whereNull('conversation_user.last_read_at')
                        ->orWhereColumn('messages.created_at', '>', 'conversation_user.last_read_at');
                })
                ->count()
        );

        return $attribute->shouldCache();
    }

    /**
     * Relationships
     */
    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function conversations(): BelongsToMany
    {
        return $this->belongsToMany(Conversation::class)
            ->withPivot('last_read_at', 'last_notified_at')
            ->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }
}
