@props([
    'areas' => [],
])

<div class="areaList">
    <div class="areaList__icon"><x-icon icon="map-pin" /></div>
    <div class="areaList__list">
        @foreach ($areas as $area)
            <a class="areaList__list-item" href="{{ route('area.show', $area) }}" wire:navigate>{{ $area->name }}</a>@if(!$loop->last), @endif
        @endforeach
    </div>
</div>