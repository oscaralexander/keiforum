@blaze

@props([
    'autocomplete' => 'off',
    'large' => false,
    'model' => null,
    'small' => false,
    'type' => 'text',
])

@php
    if ($model) {
        $attributes = $attributes->merge(['wire:model' => $model]);
    }

    $async = $attributes->has('wire:model.blur') || $attributes->whereStartsWith('wire:model.live')->isNotEmpty();
    $model ??= $attributes->whereStartsWith('wire:model')->first();
    $id = $id ?? ($model ? Str::of($model)->slug() : null);
@endphp

<div
    @class([
        'input',
        'input--small' => $small,
        'input--large' => $large,
    ])
    @if ($async)
        wire:loading.class="is-loading"
        wire:target="{{ $model }}"
    @endif
    @if ($type === 'password')
        x-data="{
            show: false,
            togglePassword() {
                this.show = !this.show;
                $refs.input.focus();
            }
        }"
    @endif
>
    {{ $slot }}
    <input
        @error ($model)
            aria-description="{{ $message }}"
            aria-invalid="true"
        @enderror
        autocomplete="{{ $autocomplete }}"
        id="{{ $id }}"
        type="{{ $type }}"
        {{ $attributes }}
        @if ($type === 'password')
            x-bind:type="show ? 'text' : 'password'"
            x-init="$dispatch('init-password', { $el: $refs.input })"
            x-ref="input"
        @endif
    >
    @if ($type === 'password')
        <button
            class="input__togglePassword"
            tabindex="-1"
            type="button"
            x-on:click="togglePassword()"
        >
            <x-icon icon="eye" />
            <x-icon icon="eye-off" />
        </button>
    @endif
</div>