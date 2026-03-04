@blaze

@props([
    'model' => null,
])

<div
    class="password"
    x-data="{
        isLetters() {
            return (this.value.length && /[a-z]/.test(this.value)) || !this.validate.letters;
        },
        isMin() {
            return this.value.length >= this.validate.min;
        },
        isMixedCase() {
            return (this.value.length && /[A-Z]/.test(this.value) && /[a-z]/.test(this.value)) || !this.validate.mixedCase;
        },
        isNumbers() {
            return (this.value.length && /[0-9]/.test(this.value)) || !this.validate.numbers;
        },
        isSymbols() {
            return (this.value.length && /[!@#$%^&*()_+\-=\[\]{};:\\|,.<>\/?]/.test(this.value)) || !this.validate.symbols;
        },
        onKeyUp($event) {
            this.value = $event.target.value;
        },
        value: '',
        validate: {
            min: 8,
            letters: true,
            mixedCase: true,
            numbers: true,
            symbols: false,
        },
    }"
    x-on:init-password="onKeyUp"
>
    <x-input.text {{ $attributes }} :$model type="password" x-on:keyup="onKeyUp" />
    <div class="password__validation">
        <div class="password__validation-item" x-bind:class="{ 'is-valid': isMin() }">
            @lang('validation.password.live.min', ['min' => 8])
        </div>
        <div class="password__validation-item" x-bind:class="{ 'is-valid': isLetters() }">
            @lang('validation.password.live.letters')
        </div>
        <div class="password__validation-item" x-bind:class="{ 'is-valid': isMixedCase() }">
            @lang('validation.password.live.mixed')
        </div>
        <div class="password__validation-item" x-bind:class="{ 'is-valid': isNumbers() }">
            @lang('validation.password.live.numbers')
        </div>
    </div>
</div>