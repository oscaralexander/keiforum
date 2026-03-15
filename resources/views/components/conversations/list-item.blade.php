@use('App\Enums\AvatarSize')

@props([
    'conversation' => null,
    'isActive' => false,
])

<a
    @class([
        'conversationListItem',
        'conversationListItem--active' => $isActive,
    ])
    href="{{ route('conversations', $conversation) }}"
    wire:navigate
>
    @if ($conversation->lastMessage)
        <x-avatar class="conversationListItem__avatar" :size="AvatarSize::S" :user="$conversation->lastMessage->user" />
    @endif
    <div class="conversationListItem__content">
        <header class="conversationListItem__flex">
            <div class="conversationListItem__usernames">
                @php
                    $usernames = $conversation->otherParticipants()->pluck('username');
                    $displayed = $usernames->take(3);
                    $more = $usernames->count() - 3;

                    if ($more > 0) {
                        $displayed->push('+' . $more);
                    }
                @endphp
                {{ $displayed->join(', ') }}
            </div>
            <time
                class="conversationListItem__time"
                datetime="{{ $conversation->lastMessage->created_at->toIso8601String() }}"
                title="{{ $conversation->lastMessage->created_at->translatedFormat('j F Y, H:i') }}"
            >{{ time_diff($conversation->lastMessage->created_at) }}</time>
        </header>
        <div class="conversationListItem__flex">
            <div class="conversationListItem__lastMessage">
                <p class="conversationListItem__lastMessageText">
                    {{ Str::limit($conversation->lastMessage->body_plain_text, 100, preserveWords: true) }}
                </p>
            </div>
            @if ($conversation->unread_count > 0)
                <div class="conversationListItem__unreadCount">{{ $conversation->unread_count }}</div>
            @endif
        </div>
    </div>
</a>