@blaze(memo: true)

@props([
    'icon' => null,
])

<svg {{ $attributes->class('icon') }} height="24" width="24" {{ $attributes }}>
    <use href="{{ versioned_asset('assets/img/icons.svg') }}#{{ $icon }}" />
</svg>