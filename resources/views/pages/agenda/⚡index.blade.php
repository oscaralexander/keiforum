<?php

use App\Enums\ActivityCategory;
use App\Models\Activity;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public const PAGINATE_COUNT = 30;

    public ?ActivityCategory $category = null;

    public ?User $user = null;

    /**
     * The category is typed as a union, so the container does not try to
     * build an enum when the route has no category.
     */
    public function mount(?User $user = null, ActivityCategory|string|null $category = null): void
    {
        $this->user = $user?->exists ? $user : null;
        $this->category = is_string($category) ? ActivityCategory::from($category) : $category;
    }

    #[Computed]
    public function activities(): LengthAwarePaginator
    {
        return Activity::query()
            ->published()
            ->upcoming()
            ->with('user')
            ->when($this->user, fn ($query) => $query->whereBelongsTo($this->user))
            ->when($this->category, fn ($query) => $query->where('category', $this->category))
            ->chronological()
            ->paginate(self::PAGINATE_COUNT, pageName: 'p')
            ->setPath($this->url($this->category));
    }

    /**
     * The activities of the current page by day, with activities that
     * started before today listed under today.
     *
     * @return Collection<string, Collection<int, Activity>>
     */
    #[Computed]
    public function activitiesByDay(): Collection
    {
        return $this->activities->getCollection()->groupBy(
            fn (Activity $activity): string => $activity->starts_on->max(today())->format('Y-m-d')
        );
    }

    #[Computed]
    public function title(): string
    {
        return match (true) {
            $this->user && $this->category => __('agenda/index.title_user_category', ['username' => $this->user->username, 'category' => $this->category->label()]),
            (bool) $this->user => __('agenda/index.title_user', ['username' => $this->user->username]),
            (bool) $this->category => __('agenda/index.title_category', ['category' => $this->category->label()]),
            default => __('agenda/index.title'),
        };
    }

    /**
     * The URL of this agenda for the given category, keeping the user.
     */
    public function url(?ActivityCategory $category): string
    {
        return match (true) {
            $this->user && $category => route('agenda.user.category', [$this->user, $category->value]),
            (bool) $this->user => route('agenda.user', $this->user),
            (bool) $category => route('agenda.category', $category->value),
            default => route('agenda'),
        };
    }

    public function render()
    {
        return $this->view()
            ->title($this->title);
    }
};
?>

<div>
    <x-header
        :hide-path="! $user && ! $category"
        :path="[['label' => __('agenda/index.title'), 'href' => route('agenda')]]"
        :title="$this->title"
    >
        <x-slot:actions>
            @can('create', App\Models\Activity::class)
                <x-btn class="m:hide" :href="route('agenda.create')" icon="plus" primary small>@lang('agenda/index.create')</x-btn>
                <x-btn class="m:show" :href="route('agenda.create')" icon="plus" primary>@lang('agenda/index.create')</x-btn>
            @endcan
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
                        @if ($day === today()->format('Y-m-d'))
                            @lang('agenda/index.today') <span class="text-color-lc">{{ today()->translatedFormat('j F Y') }}</span>
                        @elseif ($day === today()->addDay()->format('Y-m-d'))
                            @lang('agenda/index.tomorrow') <span class="text-color-lc">{{ today()->addDay()->translatedFormat('j F Y') }}</span>
                        @else
                            {{ ucfirst(\Illuminate\Support\Carbon::parse($day)->translatedFormat('l j F')) }}
                        @endif
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
            {{ $this->activities->links() }}
        </div>
    </div>
</div>
