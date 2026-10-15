@props([
    'minCharacters' => 3,
    'noResults' => null,
    'placeholder' => null,
])

<div
    class="autocomplete"
    wire:ignore
    x-bind:class="{ 'has-results': results.length > 0, 'is-empty': hasSearched && results.length === 0 }"
    x-data="placeAutocomplete({{ (int) $minCharacters }})"
>
    <label class="input input--spinner" x-bind:class="{ 'is-loading': isLoading }">
        <x-icon icon="search" />
        <input
            autocomplete="off"
            data-1p-ignore
            data-lpignore="true"
            placeholder="{{ $placeholder }}"
            type="text"
            x-on:focus="search"
            x-on:input.debounce.200ms="search"
            x-on:keydown.down.prevent="onDown"
            x-on:keydown.enter.prevent="onSelect"
            x-on:keydown.esc.prevent="onEscape"
            x-on:keydown.up.prevent="onUp"
            x-ref="input"
            {{ $attributes }}
        >
    </label>
    <div class="autocomplete__dropdown">
        <div class="autocomplete__results">
            <template x-for="(result, index) in results" x-bind:key="result.placeId">
                <button
                    class="autocomplete__result"
                    type="button"
                    x-bind:class="{ 'is-selected': selectedIndex === index }"
                    x-on:click="select(index)"
                    x-on:mousedown.prevent
                    x-on:mouseenter="selectedIndex = index"
                >
                    <span class="autocomplete__resultLabel" x-text="result.label"></span>
                    <span class="autocomplete__resultDesc" x-text="result.description"></span>
                </button>
            </template>
        </div>
        @if ($noResults)
            <div class="autocomplete__noResults">{{ $noResults }}</div>
        @endif
        <div class="autocomplete__attribution">Google Maps</div>
    </div>
</div>
