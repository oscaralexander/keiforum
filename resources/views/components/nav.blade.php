@blaze

@use('App\Enums\AvatarSize')

<nav
    class="nav"
    x-bind:class="{ 'is-menuOpen': isMenuOpen }"
    x-data="{ isMenuOpen: false }"
>
    <div
        class="nav__mobileMenuOverlay"
        x-cloak
        x-on:click="isMenuOpen = false"
        x-show="isMenuOpen"
        x-transition.opacity.duration.250ms
    ></div>
    <div class="nav__mobileMenu" id="navMobileMenu">
        <x-mobile-menu />
    </div>
    <div class="nav__menuToggleLogo">
        <button
            aria-controls="navMobileMenu"
            aria-expanded="false"
            class="nav__menuToggle"
            type="button"
            x-bind:aria-expanded="isMenuOpen"
            x-on:click="isMenuOpen = !isMenuOpen"
        >
            <div></div>
        </button>
        <a href="{{ route('home') }}" class="logo" wire:navigate>
            <img alt="Keiforum" class="logo__icon" src="{{ asset('assets/img/keiforum.svg') }}">
            <span class="logo__name">Keiforum</span>
        </a>
    </div>
    <div class="nav__menuSearch">
        <ul class="nav__menu">
            @php
                $isAgenda = request()->is('agenda*');
                $isConversations = request()->is('berichten*');
                $isUsers = request()->is('@*') || request()->is('leden*');
                $isForums = !$isAgenda && !$isUsers && !$isConversations;
            @endphp
            <li class="nav__menu-item">
                <a
                    @class([
                        'nav__menu-link',
                        'nav__menu-link--active' => $isForums,
                    ])
                    href="{{ route('home') }}"
                    wire:navigate
                >Forums</a>
            </li>
            <li class="nav__menu-item">
                <a
                    @class([
                        'nav__menu-link',
                        'nav__menu-link--active' => $isUsers,
                    ])
                    href="{{ route('members') }}"
                    wire:navigate
                >Leden</a>
            </li>
            <li class="nav__menu-item">
                <a
                    @class([
                        'nav__menu-link',
                        'nav__menu-link--active' => $isAgenda,
                    ])
                    href="{{ route('agenda') }}"
                    wire:navigate
                >Agenda</a>
            </li>
        </ul>
        <form
            class="nav__search"
            x-data="{
                onSubmit() {
                    const query = this.$refs.search.value;

                    if (query) {
                        window.location.href = `https://www.google.nl/search?q=site%3Akeiforum.nl+${encodeURIComponent(query)}`;
                    }
                }
            }"
            x-on:submit.prevent="onSubmit()">
            <x-input.text name="q" :placeholder="__('ui.search')" type="search" x-ref="search"><x-icon icon="search" /></x-input.text>
        </form>
    </div>
    <div class="nav__actions">
        @auth
            <a
                @class([
                    'nav__action',
                    'nav__action--active' => $isConversations,
                ])
                href="{{ route('conversations') }}"
            >
                <x-icon icon="inbox" />
                {{-- <span class="badge">8</span> --}}  
            </a>
            <div
                class="nav__user"
                x-data="{ isMenuOpen: false }"
                x-on:click.outside="isMenuOpen = false"
            >
                <button
                    aria-controls="navUserMenu"
                    class="avatar avatar--s"
                    type="button"
                    x-bind:aria-expanded="isMenuOpen"
                    x-on:click="isMenuOpen = !isMenuOpen"
                >
                    <img alt="" src="{{ auth()->user()->avatarUrl(size: AvatarSize::S->value) }}" />
                </button>
                <ul
                    class="nav__user-menu"
                    id="navUserMenu"
                    x-cloak
                    x-show="isMenuOpen"
                >
                    <li class="nav__user-menu-item">
                        <a class="nav__user-menu-link" href="{{ route('profile') }}">
                            <x-icon icon="user" />
                            Profiel
                        </a>
                    </li>
                    <li class="nav__user-menu-item">
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button class="nav__user-menu-link" type="submit">
                                <x-icon icon="log-out" />
                                @lang('ui.logout')
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        @else
            <x-btn class="m:hide" :href="route('login')" primary small>@lang('ui.login_alt')</x-btn>
            <x-btn class="m:show" :href="route('login')" primary>@lang('ui.login_alt')</x-btn>
        @endauth
    </div>
</nav>