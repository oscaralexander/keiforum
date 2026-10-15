@props(['activity'])

<article {{ $attributes->class(['activityListItem']) }}>
    <a class="activityListItem__poster" href="{{ $activity->url() }}" tabindex="-1" wire:navigate>
        <div class="activityPoster">
            @if ($activity->poster_path)
                <img
                    alt=""
                    class="activityPoster__img"
                    loading="lazy"
                    src="{{ route('img', ['src' => $activity->poster_path, 'w' => 240, 'h' => 340, 'q' => 80]) }}"
                >
            @endif
        </div>
    </a>
    <div class="activityListItem__content">
        <span class="activityListItem__category">{{ $activity->category->label() }}</span>
        <h4 class="activityListItem__title">
            <a href="{{ $activity->url() }}" wire:navigate>{{ $activity->title }}</a>
        </h4>
        <ul class="meta">
            <li class="meta__item">
                @if ($activity->is_multi_day)
                    {{ $activity->starts_on->translatedFormat('j M') }} – {{ $activity->ends_on->translatedFormat('j M') }}
                @else
                    {{ $activity->starts_at_time }}@if ($activity->ends_at_time) – {{ $activity->ends_at_time }}@endif
                @endif
            </li>
            <li class="meta__item">{{ $activity->location_name }}</li>
            <li class="meta__item">{{ $activity->price_formatted }}</li>
        </ul>
    </div>
</article>
