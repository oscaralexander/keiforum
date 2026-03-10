<div>
    <x-modal.header>@lang('conversations/modal.title')</x-modal.header>
    <x-modal.body class="messageModal">
        <div class="messageModal__to">
            <div>@lang('conversations/modal.to')</div>
            <x-avatar :user="$user" />
            <a class="messageModal__username" href="{{ route('member.show', $user) }}" wire:navigate>{{ $user->username }}</a>
        </div>
        <form wire:submit="submit">
            <div class="messageModal__input">
                <x-input.textarea
                    autofocus
                    max-rows="3"
                    model="body"
                    placeholder="{{ __('conversations/modal.reply') }}"
                    required
                    x-on:keydown.enter="if (!$event.shiftKey) { $event.preventDefault(); $wire.submit(); }"
                />
                <x-btn icon="send" primary submit />
            </div>
            <x-input.toggle
                model="redirect"
                :label="__('conversations/modal.redirect')"
            />
        </form>
    </x-modal.body>
</div>