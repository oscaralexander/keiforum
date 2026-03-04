@blaze

@props([
    'areas' => collect(),
    'center' => false,
    'hidePath' => false,
    'intro' => null,
    'path' => null,
    'title' => null,
])

<header
    @class([
        'header',
        'header--center' => $center,
    ])
>
    <div class="header__text">
        @unless ($hidePath)
            <x-path :items="$path" />
        @endunless
        <div class="header__titleIntro">
            <h1>{{ $title }}</h1>
            @if (!empty($intro))
                <p class="page__intro">{!! $intro !!}</p>
            @endif
            @if ($areas->isNotEmpty())
                <x-header.area-list :areas="$areas" />
            @endif
        </div>
    </div>
    @if (isset($actions) && $actions->hasActualContent())
        <div class="header__actions">
            {{ $actions }}
        </div>
    @endif
</header>