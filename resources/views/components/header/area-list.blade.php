@props([
    'areas' => [],
])

<div class="areaList">
    <x-icon class="areaList__icon" icon="map-pin" />
    <div class="areaList__list">
        @foreach ($areas as $area)
            <a class="areaList__list-item" href="{{ route('area.show', $area) }}" wire:navigate>{{ $area->name }}</a>@if(!$loop->last), @endif
        @endforeach
    </div>
</div>