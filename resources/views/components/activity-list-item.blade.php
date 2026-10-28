@props([
    'activity',
    'isDateVisible' => false,
])

<article {{ $attributes->class(['activityListItem']) }}>
    <a class="activityListItem__poster" href="{{ $activity->url() }}" tabindex="-1" wire:navigate>
        <div @class(['activityPoster', 'activityPoster--placeholder' => ! $activity->poster_path])>
            @if ($activity->poster_path)
                <img
                    alt=""
                    class="activityPoster__img"
                    loading="lazy"
                    src="{{ route('img', ['src' => $activity->poster_path, 'w' => 400, 'h' => 566, 'q' => 80]) }}"
                >
            @else
                <x-icon icon="image" />
            @endif
        </div>
    </a>
    <div class="activityListItem__content">
        <a class="activityListItem__category" href="{{ route('agenda.category', $activity->category->value) }}" wire:navigate>{{ $activity->category->label() }}</a>
        <h4 class="activityListItem__title">
            <a href="{{ $activity->url() }}" wire:navigate>{{ $activity->title }}</a>
        </h4>
        <ul class="meta">
            @if ($isDateVisible)
                <li class="meta__item">{{ $activity->starts_on->translatedFormat('j M') }}</li>
            @endif
            <li class="meta__item">{{ $activity->location_name }}</li>
            @unless ($isDateVisible)
                <li class="meta__item">{{ $activity->starts_at_time }}</li>
            @endunless
        </ul>
    </div>
</article>
