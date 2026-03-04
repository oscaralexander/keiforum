@blaze

@props([
    'description' => null,
    'model' => null,
    'label' => null,
])

@php
    if ($model) {
        $attributes = $attributes->merge(['wire:model' => $model]);
    }

    $model ??= $attributes->whereStartsWith('wire:model')->first()?->value();
    $id = $id ?? ($model ? Str::of($model)->slug() : null);
@endphp

<div class="toggle">
    <input
        @error($model)
            aria-description="{{ $message }}"
            aria-invalid="true"
        @enderror
        class="toggle__input"
        id="{{ $id }}"
        type="checkbox"
        {{ $attributes }}
    >
    <label class="toggle__label" for="{{ $id }}">
        {!! $label !!}
        @if($description)
            <div class="toggle__description">{{ $description }}</div>
        @endif
    </label>
</div>