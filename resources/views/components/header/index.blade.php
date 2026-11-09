@blaze

@props([
    'agenda' => false,
    'areas' => collect(),
    'center' => false,
    'contrast' => false,
    'hidePath' => false,
    'home' => 'Home',
    'intro' => null,
    'path' => null,
    'title' => null,
])

@php
    $hasCta = isset($cta) && $cta->hasActualContent();
@endphp

<header
    @class([
        'contrast' => $contrast,
        'header',
        'header--agenda' => $agenda,
        'header--center' => $center,
        'header--cta' => $hasCta,
    ])
>
    <div class="wrapper">
        <div class="header__text">
            <div class="header__text-flex">
                @unless ($hidePath)
                    <x-path :home="$home" :items="$path" />
                @endunless
                @if (isset($date) && $date->hasActualContent())
                    {{ $date }}
                @endif
                <div class="header__titleIntro">
                    <h1>{{ $title }}</h1>
                    @if (!empty($intro))
                        <p class="header__intro">{!! $intro !!}</p>
                    @endif
                    @if ($areas->isNotEmpty() || (isset($meta) && $meta->hasActualContent()))
                        <div class="headerMeta">
                            @if ($areas->isNotEmpty())
                                <x-header.area-list :areas="$areas" />
                            @endif
                            {{ $meta ?? '' }}
                        </div>
                    @endif
                </div>
            </div>
            @if ($slot->isNotEmpty() || $slot->hasActualContent())
                {{ $slot }}
            @endif
        </div>
        @if (isset($actions) && $actions->hasActualContent())
            <div class="header__actions">
                {{ $actions }}
            </div>
        @endif
        @if ($hasCta)
            <div class="header__cta">
                {{ $cta }}
            </div>
        @endif
        @if (isset($poster) && $poster->hasActualContent())
            <div class="header__poster">
                {{ $poster }}
            </div>
        @endif
    </div>
</header>