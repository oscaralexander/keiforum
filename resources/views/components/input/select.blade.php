@blaze

@props([
    'empty' => null,
    'model' => null,
    'options' => [],
])

@php
    if ($model) {
        $attributes = $attributes->merge(['wire:model' => $model]);
    }

    $async = $attributes->has('wire:model.blur') || $attributes->whereStartsWith('wire:model.live')->isNotEmpty();
    $model ??= $attributes->whereStartsWith('wire:model')->first();
    $id = $id ?? ($model ? Str::of($model)->slug() : null);
@endphp

<div class="input">
    <select
        @error($model)
            aria-description="{{ $message }}"
            aria-invalid="true"
        @enderror
        id="{{ $id }}"
        @if ($async)
            wire:loading.class="is-loading"
            wire:target="{{ $model }}"
        @endif
        {{ $attributes }}
    >
        @if ($empty)
            @if (is_array($empty))
                @foreach ($empty as $emptyItem)
                    <option>{{ $emptyItem }}</option>
                @endforeach
            @else
                <option>{{ $empty }}</option>
            @endif
            <option disabled>&mdash;</option>
        @endif
        @foreach ($options as $value => $label)
            <option value="{{ $value }}">{{ $label }}</option>
        @endforeach
    </select>
</div>