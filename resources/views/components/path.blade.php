@props([
    'home' => 'Home',
    'items' => [],
])

<ul class="path">
    <li class="path__item">
        <a class="path__link" href="{{ route('home') }}" wire:navigate>{{ $home }}</a>
    </li>
    @foreach ($items as $item)
        <li class="path__item">
            <a class="path__link" href="{{ $item['href'] }}" wire:navigate>{{ $item['label'] }}</a>
        </li>
    @endforeach
</ul>