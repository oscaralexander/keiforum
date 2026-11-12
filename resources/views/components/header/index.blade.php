@blaze

@props([
    'agenda' => false,
    'areas' => collect(),
    'hidePath' => false,
    'home' => 'Home',
    'intro' => null,
    'path' => null,
    'title' => null,
])

@php
    $hasCta = isset($cta) && $cta->hasActualContent();
    $hasPoster = isset($poster) && $poster->hasActualContent();
@endphp

<header
    @class([
        'contrast',
        'header',
        'header--agenda' => $agenda,
        'header--cta' => $hasCta,
    ])
>
    <div class="wrapper">
        @unless ($hidePath)
            <div class="header__path">
                <x-path :home="$home" :items="$path" />
            </div>
        @endunless
        <div class="header__text">
            <div class="header__text-flex">
                @if (isset($date) && $date->hasActualContent())
                    {{ $date }}
                @endif
                @if ($hasPoster)
                    {{-- Between the date and the title on mobile; next to the text from the desktop layout up. --}}
                    <div class="header__poster header__poster--mobile m:hide">
                        {{ $poster }}
                    </div>
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
        @if ($hasPoster)
            <div class="header__poster m:show">
                {{ $poster }}
            </div>
        @endif
    </div>
</header>