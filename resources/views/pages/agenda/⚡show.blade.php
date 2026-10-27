<?php

use App\Enums\AttendanceStatus;
use App\Models\Activity;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public Activity $activity;

    public function mount(User $user, string $date, string $slug): void
    {
        $activity = $user->activities()
            ->whereDate('starts_on', $date)
            ->where('slug', $slug)
            ->firstOrFail();

        if (! $activity->published_at?->isPast() && ! auth()->user()?->can('update', $activity)) {
            abort(404);
        }

        $this->activity = $activity->setRelation('user', $user);
    }

    #[Computed]
    public function attendanceStatus(): ?AttendanceStatus
    {
        if (! auth()->check()) {
            return null;
        }

        $status = $this->activity->attendees()
            ->where('user_id', auth()->id())
            ->first()
            ?->pivot
            ->status;

        return $status ? AttendanceStatus::from($status) : null;
    }

    #[Computed]
    public function goingUsers(): Collection
    {
        return $this->activity->goingUsers()->orderByPivot('created_at')->get();
    }

    #[Computed]
    public function maybeUsers(): Collection
    {
        return $this->activity->maybeUsers()->orderByPivot('created_at')->get();
    }

    #[Computed]
    public function mapsUrl(): string
    {
        return 'https://www.google.com/maps/search/?'.http_build_query(array_filter([
            'api' => 1,
            'query' => trim($this->activity->location_name.' '.$this->activity->location_address),
            'query_place_id' => $this->activity->location_place_id,
        ]));
    }

    /**
     * @return array<string, mixed>
     */
    #[Computed]
    public function schema(): array
    {
        $activity = $this->activity;

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Event',
            'name' => $activity->title,
            'description' => trim(strip_tags($activity->description)),
            'startDate' => $activity->starts_on->format('Y-m-d').'T'.$activity->starts_at_time,
            'endDate' => $activity->ends_at_time ? $activity->ends_on->format('Y-m-d').'T'.$activity->ends_at_time : $activity->ends_on->format('Y-m-d'),
            'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
            'eventStatus' => 'https://schema.org/EventScheduled',
            'image' => $activity->poster_path ? route('img', ['src' => $activity->poster_path, 'w' => Activity::POSTER_WIDTH, 'h' => Activity::POSTER_HEIGHT, 'f' => 'jpg']) : null,
            'location' => array_filter([
                '@type' => 'Place',
                'name' => $activity->location_name,
                'address' => $activity->location_address,
            ]),
            'offers' => [
                '@type' => 'Offer',
                'price' => number_format($activity->price / 100, 2, '.', ''),
                'priceCurrency' => 'EUR',
                'url' => $activity->url(),
            ],
            'organizer' => [
                '@type' => 'Organization',
                'name' => $activity->user->name ?: $activity->user->username,
                'url' => route('agenda.user', $activity->user),
            ],
            'url' => $activity->url(),
        ]);
    }

    public function attend(string $status): void
    {
        Gate::authorize('attend', $this->activity);

        $status = AttendanceStatus::from($status);

        if ($this->attendanceStatus === $status) {
            $this->activity->attendees()->detach(auth()->id());
        } else {
            $this->activity->attendees()->syncWithoutDetaching([
                auth()->id() => ['status' => $status->value],
            ]);
        }

        unset($this->attendanceStatus, $this->goingUsers, $this->maybeUsers);
    }

    public function render()
    {
        return $this->view()
            ->title($this->activity->title.' - '.$this->activity->starts_on->translatedFormat('j F Y'))
            ->layoutData([
                'description' => str(strip_tags($this->activity->description))->squish()->limit(160)->toString() ?: null,
            ]);
    }
};
?>

<div>
    <x-header
        :path="[
            ['label' => __('agenda/index.title'), 'href' => route('agenda')],
            ['label' => $activity->category->label(), 'href' => route('agenda.category', $activity->category->value)],
        ]"
        :title="$activity->title"
    >
        <x-slot:meta>
            <div class="headerMeta__item">
                <x-icon class="headerMeta__icon" icon="calendar" />
                <span>{{ $activity->starts_formatted }}</span>
            </div>
            <div class="headerMeta__item">
                <x-icon class="headerMeta__icon" icon="map-pin" />
                <a href="{{ $this->mapsUrl }}" rel="noopener" target="_blank">{{ $activity->location_name }}</a>
            </div>
        </x-slot:meta>
        <x-slot:actions>
            @can('update', $activity)
                <x-btn class="m:hide" :href="$activity->editUrl()" icon="pencil" small>@lang('ui.edit')</x-btn>
                <x-btn class="m:show" :href="$activity->editUrl()" icon="pencil">@lang('ui.edit')</x-btn>
            @endcan
        </x-slot:actions>
    </x-header>
    <x-schema :data="$this->schema" />
    <div class="panel panel--padded">
        <div class="activity">
            <div class="activity__poster">
                <div class="activityPoster">
                    @if ($activity->poster_path)
                        <img
                            alt="{{ $activity->title }}"
                            class="activityPoster__img"
                            src="{{ route('img', ['src' => $activity->poster_path, 'w' => 620, 'h' => 877, 'q' => 85]) }}"
                        >
                    @else
                        <x-icon icon="image" />
                    @endif
                </div>
            </div>
            <div class="activity__main">
                @unless ($activity->published_at?->isPast())
                    <x-callout icon="circle-alert" :text="e(__('agenda/show.unpublished'))" />
                @endunless
                <dl class="activity__details">
                    <div class="activity__detail">
                        <dt aria-label="@lang(\'agenda/show.date\')" title="@lang(\'agenda/show.date\')"><x-icon icon="calendar" /></dt>
                        <dd>
                            @if ($activity->is_multi_day)
                                {{ ucfirst($activity->starts_on->translatedFormat('l, j F')) }} – {{ $activity->ends_on->translatedFormat('l, j F Y') }}
                            @else
                                {{ ucfirst($activity->starts_on->translatedFormat('l, j F Y')) }}
                            @endif
                        </dd>
                    </div>
                    <div class="activity__detail">
                        <dt aria-label="@lang(\'agenda/show.time\')" title="@lang(\'agenda/show.time\')"><x-icon icon="clock" /></dt>
                        <dd>{{ $activity->starts_at_time }}@if ($activity->ends_at_time) – {{ $activity->ends_at_time }}@endif</dd>
                    </div>
                    <div class="activity__detail">
                        <dt aria-label="@lang(\'agenda/show.location\')" title="@lang(\'agenda/show.location\')"><x-icon icon="map-pin" /></dt>
                        <dd>
                            <a href="{{ $this->mapsUrl }}" rel="noopener" target="_blank">{{ $activity->location_name }}</a>
                            @if ($activity->location_address)
                                <span class="activity__address">{{ $activity->location_address }}</span>
                            @endif
                        </dd>
                    </div>
                    <div class="activity__detail">
                        <dt aria-label="@lang(\'agenda/show.price\')" title="@lang(\'agenda/show.price\')"><x-icon icon="ticket" /></dt>
                        <dd>{{ $activity->price_formatted }}</dd>
                    </div>
                    @if ($activity->website_url)
                        <div class="activity__detail">
                            <dt aria-label="@lang(\'agenda/show.website\')" title="@lang(\'agenda/show.website\')"><x-icon icon="link" /></dt>
                            <dd><a href="{{ $activity->website_url }}" rel="noopener nofollow" target="_blank">{{ $activity->website_host }}</a></dd>
                        </div>
                    @endif
                </dl>
                <div class="formatted">{!! $activity->description !!}</div>
                <div class="activity__attendance">
                    <div class="activity__attendanceButtons">
                        @auth
                            @can('attend', $activity)
                                <x-btn
                                    :good="$this->attendanceStatus === AttendanceStatus::GOING"
                                    icon="check"
                                    wire:click="attend('{{ AttendanceStatus::GOING->value }}')"
                                >{{ AttendanceStatus::GOING->label() }}</x-btn>
                                <x-btn
                                    :good="$this->attendanceStatus === AttendanceStatus::MAYBE"
                                    icon="circle-help"
                                    wire:click="attend('{{ AttendanceStatus::MAYBE->value }}')"
                                >{{ AttendanceStatus::MAYBE->label() }}</x-btn>
                            @endcan
                        @else
                            <x-btn href="{{ login_url() }}" icon="check">{{ AttendanceStatus::GOING->label() }}</x-btn>
                            <x-btn href="{{ login_url() }}" icon="circle-help">{{ AttendanceStatus::MAYBE->label() }}</x-btn>
                        @endauth
                    </div>
                    <ul class="meta">
                        <li class="meta__item">{{ trans_choice('agenda/show.going_count', $this->goingUsers->count(), ['count' => $this->goingUsers->count()]) }}</li>
                        <li class="meta__item">{{ trans_choice('agenda/show.maybe_count', $this->maybeUsers->count(), ['count' => $this->maybeUsers->count()]) }}</li>
                    </ul>
                    @if ($this->goingUsers->isNotEmpty())
                        <div class="activity__attendees">
                            @foreach ($this->goingUsers as $attendee)
                                <a href="{{ route('member.show', $attendee) }}" wire:key="going-{{ $attendee->id }}" wire:navigate>
                                    <x-avatar img-only :user="$attendee" x-data="tooltip('{{ $attendee->username }}')" />
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
