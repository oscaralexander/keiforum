@props([
    'model' => null,
    'options' => [],
])

@php
    if ($model) {
        $attributes = $attributes->merge(['wire:model' => $model]);
    }

    $model ??= $attributes->whereStartsWith('wire:model')->first()?->value();
    $id = $id ?? ($model ? Str::of($model)->slug() : null);
@endphp

<div class="input">
    <select
        @error($model)
            aria-description="{{ $message }}"
            aria-invalid="true"
        @enderror
        id="{{ $id }}"
        {{ $attributes }}
    >
        @foreach ($options as $value => $label)
            <option value="{{ $value }}">{{ $label }}</option>
        @endforeach
    </select>
</div>