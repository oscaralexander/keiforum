<div>
    <x-modal.header>@lang('messages/modal.title')</x-modal.header>
    <x-modal.body class="messageModal">
        <div class="messageModal__to">
            <div>@lang('messages/modal.to')</div>
            <x-avatar :user="$user" />
            <a class="messageModal__username" href="{{ route('member.show', $user) }}" wire:navigate>{{ $user->username }}</a>
        </div>
        <form wire:submit="submit">
            <div class="messageModal__input">
                <x-input.textarea
                    autofocus
                    max-rows="3"
                    model="body"
                    placeholder="{{ __('messages/index.form.reply') }}"
                    required
                    x-on:keydown.enter="if (!$event.shiftKey) { $event.preventDefault(); $wire.submit(); }"
                />
                <x-btn icon="send" primary submit />
            </div>
            <x-input.toggle
                model="redirect"
                :label="__('messages/modal.redirect')"
            />
        </form>
    </x-modal.body>
</div>