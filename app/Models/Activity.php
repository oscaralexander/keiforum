<?php

namespace App\Models;

use App\Enums\ActivityCategory;
use App\Enums\AttendanceStatus;
use App\Lib\HtmlSanitizer;
use Carbon\CarbonInterface;
use Database\Factories\ActivityFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Number;
use Illuminate\Support\Str;

class Activity extends Model
{
    /** @use HasFactory<ActivityFactory> */
    use HasFactory;

    /**
     * Poster dimensions in pixels, in the aspect ratio of A4 paper.
     */
    public const POSTER_WIDTH = 1080;

    public const POSTER_HEIGHT = 1528;

    public const POSTER_DIRECTORY = 'posters';

    /**
     * Activities ending the next day before this time count as one night.
     */
    public const NIGHT_ENDS_AT = '06:00';

    protected $casts = [
        'category' => ActivityCategory::class,
        'ends_on' => 'date',
        'location_latitude' => 'float',
        'location_longitude' => 'float',
        'price' => 'integer',
        'published_at' => 'datetime',
        'starts_on' => 'date',
    ];

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::creating(function (Activity $activity): void {
            $activity->slug ??= $activity->uniqueSlug();
        });

        static::updated(function (Activity $activity): void {
            if ($activity->wasChanged('title')) {
                $activity->topic?->update(['title' => $activity->title]);
            }
        });

        static::deleting(function (Activity $activity): void {
            $activity->topic?->delete();
        });
    }

    /**
     * The hidden topic holding the replies, created on the first reply with
     * the venue subscribed so it hears about questions.
     */
    public function findOrCreateTopic(): Topic
    {
        if ($this->topic) {
            return $this->topic;
        }

        $topic = $this->topic()->create([
            'forum_id' => Forum::agenda()->id,
            'is_visible' => false,
            'title' => $this->title,
            'user_id' => $this->user_id,
        ]);

        $topic->trackedByUsers()->syncWithoutDetaching([
            $this->user_id => ['is_subscribed' => true],
        ]);

        $this->setRelation('topic', $topic);

        return $topic;
    }

    /**
     * A slug based on the title that is unique for the user and start date.
     */
    public function uniqueSlug(): string
    {
        $baseSlug = Str::slug($this->title) ?: 'activiteit';
        $slug = $baseSlug;
        $suffix = 2;

        while (static::query()
            ->where('user_id', $this->user_id)
            ->whereDate('starts_on', $this->starts_on)
            ->where('slug', $slug)
            ->when($this->exists, fn (Builder $query) => $query->whereKeyNot($this->getKey()))
            ->exists()) {
            $slug = $baseSlug.'-'.$suffix++;
        }

        return $slug;
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function url(array $query = []): string
    {
        return route('agenda.show', [$this->user, $this->starts_on->format('Y-m-d'), $this->slug, ...$query]);
    }

    public function editUrl(): string
    {
        return route('agenda.edit', [$this->user, $this->starts_on->format('Y-m-d'), $this->slug]);
    }

    /**
     * Scopes
     */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    /**
     * Activities that have not ended yet.
     */
    #[Scope]
    protected function upcoming(Builder $query): void
    {
        $query->whereDate('ends_on', '>=', today());
    }

    /**
     * Activities that take place on at least one day of the given period.
     */
    #[Scope]
    protected function during(Builder $query, CarbonInterface $from, CarbonInterface $until): void
    {
        $query->whereDate('starts_on', '<=', $until)->whereDate('ends_on', '>=', $from);
    }

    #[Scope]
    protected function chronological(Builder $query): void
    {
        $query->orderBy('starts_on')->orderBy('starts_at')->orderBy('id');
    }

    /**
     * Attributes
     */
    public function description(): Attribute
    {
        return new Attribute(
            set: fn (?string $value): string => (new HtmlSanitizer)->sanitize((string) $value),
        );
    }

    public function endsAtTime(): Attribute
    {
        return new Attribute(
            get: fn (): ?string => $this->ends_at ? substr($this->ends_at, 0, 5) : null,
        );
    }

    public function isFree(): Attribute
    {
        return new Attribute(
            get: fn (): bool => $this->price === 0,
        );
    }

    /**
     * Whether the activity spans several days, not counting a night that
     * ends the next day before NIGHT_ENDS_AT.
     */
    public function isMultiDay(): Attribute
    {
        return new Attribute(
            get: fn (): bool => ! $this->starts_on->isSameDay($this->ends_on)
                && ! ($this->ends_on->isSameDay($this->starts_on->copy()->addDay())
                    && $this->ends_at_time !== null
                    && $this->ends_at_time < self::NIGHT_ENDS_AT),
        );
    }

    public function priceFormatted(): Attribute
    {
        return new Attribute(
            get: fn (): string => $this->is_free
                ? __('agenda/show.free')
                : Number::currency($this->price / 100, in: 'EUR', locale: 'nl', precision: $this->price % 100 === 0 ? 0 : 2),
        );
    }

    /**
     * The start date and time, e.g. "24 okt. 2026, 23:00".
     */
    public function startsFormatted(): Attribute
    {
        return new Attribute(
            get: fn (): string => $this->starts_on->translatedFormat('j M Y').', '.$this->starts_at_time,
        );
    }

    /**
     * The website's host name without "www.", e.g. "podiumfluor.nl".
     */
    public function websiteHost(): Attribute
    {
        return new Attribute(
            get: fn (): ?string => $this->website_url
                ? preg_replace('/^www\./', '', (string) parse_url($this->website_url, PHP_URL_HOST))
                : null,
        );
    }

    public function startsAtTime(): Attribute
    {
        return new Attribute(
            get: fn (): string => substr($this->starts_at, 0, 5),
        );
    }

    /**
     * Relationships
     */
    public function attendees(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('status')
            ->withTimestamps();
    }

    public function goingUsers(): BelongsToMany
    {
        return $this->attendees()->wherePivot('status', AttendanceStatus::GOING->value);
    }

    public function maybeUsers(): BelongsToMany
    {
        return $this->attendees()->wherePivot('status', AttendanceStatus::MAYBE->value);
    }

    public function topic(): HasOne
    {
        return $this->hasOne(Topic::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
