@blaze(memo: true)

@props([
    'reportedPostsCount' => 0,
])

<div class="mobileMenu">
    <div class="mobileMenu__main">
        <form
            class="mobileMenu__search"
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
        <ul class="mobileMenu__menu">
            @php
                $isAgenda = request()->is('agenda*');
                $isAdmin = request()->is('admin*');
                $isConversations = request()->is('berichten*');
                $isUsers = request()->is('@*') || request()->is('leden*');
                $isForums = !$isAgenda && !$isAdmin && !$isUsers && !$isConversations;
            @endphp
            <li class="mobileMenu__menu-item">
                <a
                    @class([
                        'mobileMenu__menu-link',
                        'mobileMenu__menu-link--active' => $isForums,
                    ])
                    href="{{ route('home') }}"
                    wire:navigate
                >@lang('nav.forums')</a>
            </li>
            <li class="mobileMenu__menu-item">
                <a
                    @class([
                        'mobileMenu__menu-link',
                        'mobileMenu__menu-link--active' => $isUsers,
                    ])
                    href="{{ route('members') }}"
                    wire:navigate
                >@lang('nav.members')</a>
            </li>
            <li class="mobileMenu__menu-item">
                <a
                    @class([
                        'mobileMenu__menu-link',
                        'mobileMenu__menu-link--active' => $isAgenda,
                    ])
                    href="{{ route('agenda') }}"
                    wire:navigate
                >@lang('nav.agenda')</a>
            </li>
            @if (auth()->check() && auth()->user()->is_admin)
                <li class="mobileMenu__menu-item">
                    <a
                        @class([
                            'mobileMenu__menu-link',
                            'mobileMenu__menu-link--active' => $isAdmin,
                        ])
                        href="{{ route('admin') }}"
                        wire:navigate
                    >
                        @lang('nav.admin')
                        @if ($reportedPostsCount > 0)
                            <span class="mobileMenu__menu-badge">{{ $reportedPostsCount }}</span>
                        @endif
                    </a>
                </li>
            @endif
        </ul>
    </div>
    <footer class="mobileMenu__footer">
        <div class="flex flex-gap-s">
            <x-btn class="flex-flex" :href="route('login')" primary>@lang('ui.login')</x-btn>
            <x-btn class="flex-flex" :href="route('register')" primary>@lang('ui.register')</x-btn>
        </div>
    </footer>
</div>