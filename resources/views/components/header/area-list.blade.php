@props([
    'areas' => [],
])

<div class="headerMeta__item">
    <x-icon class="headerMeta__icon" icon="map-pin" />
    <div>
        @foreach ($areas as $area)
            <a href="{{ route('area.show', $area) }}" wire:navigate>{{ $area->name }}</a>@if(!$loop->last), @endif
        @endforeach
    </div>
</div>