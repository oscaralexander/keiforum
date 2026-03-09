@blaze

@props([
    'value' => null,
    'label' => null,
    'model' => null,
    'type' => 'radio',
])

@php
    if ($model) {
        $attributes = $attributes->merge(['wire:model' => $model]);
    }

    $model ??= $attributes->whereStartsWith('wire:model')->first();
    $id = $id ?? ($model ? Str::of($model)->slug() : null);
@endphp

<label class="option">
    <input
        id="{{ $id }}-{{ Str::of($value)->slug() }}"
        type="{{ $type }}"
        value="{{ $value }}"
        {{ $attributes }}
    >
    {{ $label }}
</label>