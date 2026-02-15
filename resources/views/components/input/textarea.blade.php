@props([
    'maxRows' => null,
    'model' => null,
    'rows' => 1,
])

@php
    if ($model) {
        $attributes = $attributes->merge(['wire:model' => $model]);
    }

    $model ??= $attributes->whereStartsWith('wire:model')->first();
    $id = $id ?? ($model ? Str::of($model)->slug() : null);
@endphp

<div
    class="textarea"
    style="--max-rows: {{ $maxRows ?? 10 }};"
    x-bind:data-replicated-value="$wire.{{ $model }}"
>
    <textarea
        @error ($model)
            aria-invalid="true"
            aria-description="{{ $message }}"
        @enderror
        id="{{ $id }}"
        rows="{{ $rows }}"
        {{ $attributes }}
    ></textarea>
</div>