@props([
    'model' => null,
    'options' => [],
    'optionValue' => 'id',
    'optionLabel' => 'name',
])

@php
    $id = $model ? Str::of($model)->slug() : null;
    $optionsJson = collect($options)->map(function ($option) use ($optionValue, $optionLabel) {
        if (is_object($option)) {
            return [
                'id' => $option->{$optionValue},
                'name' => $option->{$optionLabel},
            ];
        }

        return [
            'id' => $option[$optionValue] ?? $option['id'] ?? null,
            'name' => $option[$optionLabel] ?? $option['name'] ?? '',
        ];
    })->toJson();
@endphp

<div
    x-data="selectMany(@entangle($model), {{ $optionsJson }}, '{{ $model }}')"
    class="selectMany"
    x-on:click.away="closeDropdown()"
>
    <!-- Selected Tags -->
    <template x-for="(item, index) in selected" :key="item.id">
        <div class="selectMany__chip">
            <span x-text="item.name"></span>
            <button class="selectMany__chip-remove" tabindex="-1" type="button"
                x-on:click.stop="removeItem(item.id)"
            ><x-icon icon="x" /></button>
        </div>
    </template>

    <div class="selectMany__inputBox">
        <input
            autocomplete="off"
            class="selectMany__input"
            type="text"
            id="{{ $id }}"
            placeholder="Zoek..."
            x-model="searchQuery"
            x-on:focus="openDropdown()"
            x-on:keydown.escape="closeDropdown()"
            x-on:keydown.arrow-down.prevent="navigateDown()"
            x-on:keydown.arrow-up.prevent="navigateUp()"
            x-on:keydown.enter.prevent="selectHighlighted()"
            x-ref="input"
        />
        <div
            class="selectMany__dropdown"
            x-show="isOpen && filteredOptions.length > 0"
            x-transition:enter="input-select-many__dropdown--enter"
            x-transition:enter-start="input-select-many__dropdown--enter-start"
            x-transition:enter-end="input-select-many__dropdown--enter-end"
            x-transition:leave="input-select-many__dropdown--leave"
            x-transition:leave-start="input-select-many__dropdown--leave-start"
            x-transition:leave-end="input-select-many__dropdown--leave-end"
            x-ref="dropdown"
        >
            <template x-for="(option, index) in filteredOptions" :key="option.id">
                <button
                    type="button"
                    class="input-select-many__option"
                    :class="{ 'is-highlighted': index === highlightedIndex }"
                    x-on:click="selectOption(option)"
                    x-on:mouseenter="highlightedIndex = index"
                >
                    <span x-text="option.name"></span>
                </button>
            </template>
        </div>
    </div>
</div>
