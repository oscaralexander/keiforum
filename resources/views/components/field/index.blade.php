@props([
    'description' => null,
    'id' => null,
    'label' => null,
    'model' => null,
])

@php
    $id = $id ?? ($model ? Str::of($model)->slug() : null);
@endphp

<div {{ $attributes->class(['field']) }}>
    @if($label || $description)
        <div>
            @if($label)
                <label class="field__label" for="{{ $id }}">{{ $label }}</label>
            @endif
            @if($description)
                <div class="field__description">{{ $description }}</div>
            @endif
        </div>
    @endif
    {{ $slot }}
    @error($model)
        <div class="field__error">{{ $message }}</div>
    @enderror
</div>