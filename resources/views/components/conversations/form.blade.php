@props([
    'selectedMembers' => [],
])

<form class="conversations__form" wire:submit="submitNewConversation">
    <x-field :label="__('messages/index.form.recipients')" model="selectedMembers">
        <div
            class="conversations__recipients"
            x-data="{
                query: '',
                results: [],
                open: false,
                async search() {
                    if (this.query.length < 2) { this.results = []; return; }
                    const res = await fetch(`{{ url('api/users/search') }}?q=${encodeURIComponent(this.query)}`);
                    this.results = await res.json();
                    this.open = true;
                },
                add(user) {
                    $wire.addMember(user);
                    this.query = '';
                    this.results = [];
                    this.open = false;
                }
            }"
            x-on:click.outside="open = false"
        >
            <div class="conversations__chips">
                @foreach ($selectedMembers as $member)
                    <span class="conversations__chip">
                        <img alt="" class="conversations__chip-avatar" src="{{ $member['avatar'] ?? '/assets/img/avatar.png' }}" width="24" height="24">
                        {{ $member['username'] ?? '' }}
                        <button type="button" class="conversations__chip-remove" wire:click="removeMember({{ (int) ($member['id'] ?? 0) }})" aria-label="@lang('ui.delete')">&times;</button>
                    </span>
                @endforeach
                <input
                    class="conversations__search"
                    placeholder="@lang('messages/index.form.search_placeholder')"
                    type="text"
                    x-model="query"
                    x-on:input.debounce.200ms="search()"
                    x-on:focus="query && search()"
                >
            </div>
            <div class="conversations__dropdown" x-show="open && results.length" x-transition x-cloak>
                <template x-for="user in results" :key="user.id">
                    <button
                        type="button"
                        class="conversations__dropdown-item"
                        x-on:click="add(user)"
                    >
                        <img :alt="user.username" :src="user.avatar" class="conversations__dropdown-avatar" height="32" width="32">
                        <span x-text="user.username"></span>
                    </button>
                </template>
            </div>
        </div>
    </x-field>
    <x-field :label="__('messages/index.form.body')" model="body">
        <x-editor model="body" />
    </x-field>
    <x-btn primary submit>@lang('messages/index.form.submit')</x-btn>
</form>