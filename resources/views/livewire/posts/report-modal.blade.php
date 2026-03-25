<div>
    <x-modal.header>@lang('post/report.title')</x-modal.header>
    <x-modal.body class="reportModal">
        @if ($isSubmitted)
            <x-callout
                icon="shield-check"
                success
                :title="__('post/report.confirmation_title')"
                :text="__('post/report.confirmation_text')"
            />
        @else
            <form class="flex flex-col flex-gap-l" wire:submit="submit">
                <div class="reportModal__types">
                    @foreach ($reportTypes as $reportType)
                        <label
                            @class([
                                'reportModal__type',
                                'reportModal__type--selected' => $type === $reportType->value
                            ])
                        >
                            <input type="radio" value="{{ $reportType->value }}" wire:model="type">
                            <div class="reportModal__type-content">
                                <div class="reportModal__type-label">{{ $reportType->label() }}</div>
                                <div class="reportModal__type-description">{{ $reportType->description() }}</div>
                            </div>
                        </label>
                    @endforeach
                    @error ('type')
                        <span class="input__error">{{ $message }}</span>
                    @enderror
                </div>
                <x-actions>
                    <x-btn primary submit>@lang('post/report.submit')</x-btn>
                    <x-btn text wire:click="$dispatch('closeModal')">@lang('ui.cancel')</x-btn>
                </x-actions>
            </form>
        @endif
    </x-modal.body>
</div>
