@use('App\Enums\AvatarSize')

@props([
    'conversation' => null,
    'messages' => collect(),
    'hasMoreMessages' => false,
])

<div class="conversation">
    <x-conversation.header :conversation="$conversation" />
    <div
        class="conversation__messages"
        x-data="conversationScroll"
        x-init="scrollToBottom()"
        x-on:message-sent.window="$nextTick(() => scrollToBottom())"
    >
        @if ($hasMoreMessages)
            <div
                class="conversation__loader"
                wire:key="load-more"
            >
                <button type="button" wire:click="loadMoreMessages" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="loadMoreMessages">@lang('messages/index.load_more')</span>
                    <span wire:loading wire:target="loadMoreMessages">@lang('messages/index.loading')</span>
                </button>
            </div>
        @endif
        @foreach ($messages as $message)
            @if ($loop->first || !$message->created_at->isSameDay($messages[$loop->index - 1]->created_at))
                <div class="conversation__date" wire:key="date-{{ $message->id }}">
                    <span>
                        @if ($message->created_at->isToday())
                            @lang('messages/index.today')
                        @elseif ($message->created_at->isYesterday())
                            @lang('messages/index.yesterday')
                        @elseif ($message->created_at->isCurrentWeek())
                            {{ $message->created_at->translatedFormat('l') }}
                        @else
                            {{ $message->created_at->translatedFormat('j F Y') }}
                        @endif
                    </span>
                </div>
            @endif
            <livewire:message
                :message="$message"
                :key="'message-' . $message->id"
            />
        @endforeach
    </div>
    <form class="conversation__reply" wire:submit="submitReply">
        <div class="conversation__reply-inner">
            <x-input.textarea
                max-rows="3"
                model="replyBody"
                placeholder="{{ __('messages/index.form.reply') }}"
                x-on:keydown.enter="if (!$event.shiftKey) { $event.preventDefault(); $wire.submitReply(); }"
            />
            <x-btn icon="send" primary submit />
        </div>
    </form>
</div>