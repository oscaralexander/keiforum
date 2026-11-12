@blaze

@props([
    'intro' => null,
    'title' => null,
])

{{-- The header of the simple layout (login, registration, settings, …). --}}
<header class="headerSimple">
    <h1>{{ $title }}</h1>
    @if (!empty($intro))
        <p class="headerSimple__intro">{!! $intro !!}</p>
    @endif
</header>
