@blaze

@props([
    'description' => null,
    'label' => null,
    'model' => null,
    'name' => null,
    'optionValue' => 'id',
    'optionLabel' => 'name',
    'options',
    'placeholder' => null,
])

@php
    $id = $model ? Str::of($model)->slug() : null;
    $optionsJson = collect($options)->map(function ($option) use ($optionValue, $optionLabel) {
        if (is_object($option)) {
            return [
                'value' => $option->{$optionValue},
                'label' => $option->{$optionLabel},
            ];
        }

        return [
            'value' => $option[$optionValue] ?? $option['value'] ?? null,
            'label' => $option[$optionLabel] ?? $option['label'] ?? '',
        ];
    })->toJson();
@endphp

<div
    class="multiSelect"
    :class="{ 'is-open': isOpen }"
    x-data="multiSelect(@entangle($model), {{ $optionsJson }})"
    x-on:click.outside="isOpen = false"
>
    <button class="multiSelect__label" type="button" x-on:click="isOpen = !isOpen">
        <span class="multiSelect__label-flex multiSelect__placeholder" x-show="model.length === 0">{{ $placeholder }}</span>
        <span class="multiSelect__label-flex" x-show="model.length > 0" x-text="label"></span>
        <span class="multiSelect__label-count" x-show="model.length > 0" x-text="model.length"></span>
    </button>
    <div
        class="multiSelect__dropdown"
        x-show="isOpen"
        x-transition:enter="multiSelect__dropdown--enter"
        x-transition:enter-end="multiSelect__dropdown--enterEnd"
        x-transition:enter-start="multiSelect__dropdown--enterStart"
        x-transition:leave="multiSelect__dropdown--leave"
        x-transition:leave-end="multiSelect__dropdown--leaveEnd"
        x-transition:leave-start="multiSelect__dropdown--leaveStart"
    >
        <template x-for="(option, index) in options" :key="option.value">
            <label class="multiSelect__option option option--checkbox" :title="option.label">
                <input type="checkbox" :value="option.value" x-model="model">
                <span x-text="option.label"></span>
            </label>
        </template>
    </div>
</div>