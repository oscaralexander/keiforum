<?php

use App\Enums\ActivityCategory;
use App\Models\Activity;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

new class extends Component
{
    public ?ActivityCategory $category = null;

    public ?User $user = null;

    /**
     * The first day of the page as Y-m-d, empty for today.
     */
    #[Url(as: 'vanaf', except: '')]
    public string $start = '';

    /**
     * The category is typed as a union, so the container does not try to
     * build an enum when the route has no category.
     */
    public function mount(?User $user = null, ActivityCategory|string|null $category = null): void
    {
        $this->user = $user?->exists ? $user : null;
        $this->category = is_string($category) ? ActivityCategory::from($category) : $category;
    }

    /**
     * The first day of the page, never before today.
     */
    #[Computed]
    public function from(): Carbon
    {
        $date = rescue(fn (): Carbon => Carbon::createFromFormat('!Y-m-d', $this->start), report: false);

        return $date ? $date->max(today()) : today();
    }

    /**
     * The last day of the page: the day of the last activity that fits on
     * it, or null when all remaining activities fit.
     */
    #[Computed]
    public function until(): ?Carbon
    {
        $startsOn = $this->query()
            ->whereDate('ends_on', '>=', $this->from)
            ->chronological()
            ->skip(Activity::PAGINATE_COUNT - 1)
            ->value('starts_on');

        return $startsOn ? Carbon::parse($startsOn)->max($this->from) : null;
    }

    /**
     * @return Collection<int, Activity>
     */
    #[Computed]
    public function activities(): Collection
    {
        return $this->query()
            ->whereDate('ends_on', '>=', $this->from)
            ->when($this->until, fn (Builder $query) => $query->whereDate('starts_on', '<=', $this->until))
            ->with('user')
            ->chronological()
            ->get();
    }

    /**
     * The first day of the next page, or null when this is the last page.
     */
    #[Computed]
    public function nextStart(): ?Carbon
    {
        if (! $this->until) {
            return null;
        }

        $hasMore = $this->query()->whereDate('starts_on', '>', $this->until)->exists();

        return $hasMore ? $this->until->copy()->addDay() : null;
    }

    /**
     * The published activities of this agenda's user and category.
     *
     * @return Builder<Activity>
     */
    private function query(): Builder
    {
        return Activity::query()
            ->published()
            ->when($this->user, fn (Builder $query) => $query->whereBelongsTo($this->user))
            ->when($this->category, fn (Builder $query) => $query->where('category', $this->category));
    }

    /**
     * The activities of this page by day, with activities that started
     * before this page listed under its first day.
     *
     * @return Collection<string, Collection<int, Activity>>
     */
    #[Computed]
    public function activitiesByDay(): Collection
    {
        return $this->activities->groupBy(
            fn (Activity $activity): string => $activity->starts_on->max($this->from)->format('Y-m-d')
        );
    }

    /**
     * The relative name of a day: today, tomorrow or the weekday.
     */
    public function dayName(string $day): string
    {
        $date = Carbon::parse($day);

        return match (true) {
            $date->isToday() => __('agenda/index.today'),
            $date->isTomorrow() => __('agenda/index.tomorrow'),
            default => ucfirst($date->translatedFormat('l')),
        };
    }

    /**
     * The date of a day, with the year only when it is not the current year.
     */
    public function dayDate(string $day): string
    {
        $date = Carbon::parse($day);

        return $date->translatedFormat($date->isCurrentYear() ? 'j F' : 'j F Y');
    }

    #[Computed]
    public function title(): string
    {
        return match (true) {
            $this->user && $this->category => __('agenda/index.title_user_category', ['name' => $this->user->organizer_name, 'category' => $this->category->label()]),
            (bool) $this->user => __('agenda/index.title_user', ['name' => $this->user->organizer_name]),
            (bool) $this->category => __('agenda/index.title_category', ['category' => $this->category->label()]),
            default => __('agenda/index.title'),
        };
    }

    /**
     * The URL of this agenda for the given category, keeping the user.
     */
    public function url(?ActivityCategory $category, ?Carbon $start = null): string
    {
        $query = $start && ! $start->isToday() ? ['vanaf' => $start->format('Y-m-d')] : [];

        return match (true) {
            $this->user && $category => route('agenda.user.category', [$this->user, $category->value, ...$query]),
            (bool) $this->user => route('agenda.user', [$this->user, ...$query]),
            (bool) $category => route('agenda.category', [$category->value, ...$query]),
            default => route('agenda', $query),
        };
    }

    public function render()
    {
        return $this->view()
            ->title($this->title);
    }
};
?>

@if (! $this->from->isToday())
    @push('meta')
        <meta content="noindex" name="robots">
    @endpush
@endif

<div>
    <x-header
        :hide-path="! $user && ! $category"
        :path="[['label' => __('agenda/index.title'), 'href' => route('agenda')]]"
        :title="$this->title"
    >
        <x-slot:actions>
            <x-btn class="m:hide" :href="route('agenda.create')" icon="plus" primary small>@lang('agenda/index.create')</x-btn>
            <x-btn class="m:show" :href="route('agenda.create')" icon="plus" primary>@lang('agenda/index.create')</x-btn>
        </x-slot:actions>
    </x-header>
    <div class="panel panel--padded">
        <div class="flex flex-col flex-gap-xl">
            <div class="agendaFilters">
                <nav class="agendaFilters__categories">
                    <a
                        @class(['chip', 'is-active' => ! $category])
                        href="{{ $this->url(null) }}"
                        wire:navigate
                    >@lang('agenda/index.all')</a>
                    @foreach (ActivityCategory::cases() as $categoryOption)
                        <a
                            @class(['chip', 'is-active' => $category === $categoryOption])
                            href="{{ $this->url($categoryOption) }}"
                            wire:key="category-{{ $categoryOption->value }}"
                            wire:navigate
                        >{{ $categoryOption->label() }}</a>
                    @endforeach
                </nav>
            </div>
            @forelse ($this->activitiesByDay as $day => $dayActivities)
                <section class="agendaDay" wire:key="day-{{ $day }}">
                    <h2 class="agendaDay__title">
                        {{ $this->dayName($day) }} <span class="text-color-lc">{{ $this->dayDate($day) }}</span>
                    </h2>
                    <div class="agendaDay__activities">
                        @foreach ($dayActivities as $activity)
                            <x-activity-list-item :activity="$activity" wire:key="activity-{{ $activity->id }}" />
                        @endforeach
                    </div>
                </section>
            @empty
                <p class="text-color-lc">@lang('agenda/index.empty')</p>
            @endforelse
            @if (! $this->from->isToday() || $this->nextStart)
                <nav class="flex flex-justify-spaceBetween" aria-label="@lang('agenda/index.pages')">
                    @if (! $this->from->isToday())
                        <x-btn :href="$this->url($category)" icon="arrow-left" navigate>@lang('agenda/index.back_to_today')</x-btn>
                    @else
                        <span></span>
                    @endif
                    @if ($this->nextStart)
                        <x-btn :href="$this->url($category, $this->nextStart)" icon="arrow-right" icon-end navigate rel="next">@lang('agenda/index.next')</x-btn>
                    @endif
                </nav>
            @endif
        </div>
    </div>
</div>
