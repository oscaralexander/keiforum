@props([
    'form',
    'showToggle' => false,
])

@if ($showToggle)
    <x-input.toggle
        :label="__('topic/form.poll.label')"
        wire:model.live="poll.active"
    />
@endif

<div
    class="pollEditor"
    x-data="{
        focusLastOption() {
            this.$nextTick(() => {
                const inputs = this.$el.querySelectorAll('.pollEditor__option-label input:not([disabled])');
                if (inputs.length) inputs[inputs.length - 1].focus();
            });
        }
    }"
    x-on:poll-option-added.window="focusLastOption()"
    @if ($showToggle)
        x-cloak
        x-show="$wire.poll.active"
    @endif
>
    <x-field :label="__('topic/form.poll.question_label')" model="poll.question">
        <x-input.text
            model="poll.question"
            :placeholder="__('topic/form.poll.question_placeholder')"
        />
    </x-field>
    <ul class="pollEditor__options" wire:sort="reorderPollOptions">
        @foreach ($form->options as $index => $option)
            @php
                $isLocked = $option['isLocked'] ?? false;
            @endphp
            <li
                class="pollEditor__option"
                wire:key="{{ $option['id'] }}"
                wire:sort:item="{{ $option['id'] }}"
            >
                <div class="pollEditor__option-handle" wire:sort:handle>
                    <x-icon icon="grip-vertical" />
                </div>
                <div class="pollEditor__option-label">
                    <input
                        @disabled($isLocked)
                        placeholder="@lang('topic/form.poll.option_label', ['number' => $index + 1])"
                        type="text"
                        wire:key="option-label-{{ $option['id'] }}-{{ $index }}"
                        wire:model="poll.options.{{ $index }}.label"
                    >
                </div>
                <div class="pollEditor__option-actions" wire:sort:ignore>
                    @if ($isLocked)
                        <x-icon icon="lock" />
                    @else
                        <button
                            aria-label="@lang('topic/form.poll.delete_option')"
                            tabindex="-1"
                            type="button"
                            wire:click="removePollOption('{{ $option['id'] }}')"
                        ><x-icon icon="x" /></button>
                    @endif
                </div>
            </li>
        @endforeach
    </ul>
    @error('poll.options')
        <div class="field__error">{{ $message }}</div>
    @enderror
    <div>
        <x-btn icon="plus" wire:click="addPollOption">
            {{ __('topic/form.poll.add_option') }}
        </x-btn>
    </div>
</div>
