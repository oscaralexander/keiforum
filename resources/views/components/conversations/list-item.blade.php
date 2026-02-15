@use('App\Enums\AvatarSize')

@props([
    'conversation' => null,
    'isActive' => false,
])

<a
    @class([
        'conversationList__item',
        'is-active' => $isActive,
    ])
    href="{{ route('messages', $conversation) }}"
    wire:navigate
>
    <div class="conversationList__item-flex">
        <div class="conversationList__item-usernames">
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
        @php
            $lastMessage = $conversation->lastMessage;
        @endphp
        @if ($conversation->lastMessage)
            <p class="conversationList__item-lastMessage">
                {{ Str::limit($conversation->lastMessage->body_plain_text, 40, preserveWords: true) }}
            </p>
            <ul class="meta">
                @if ($conversation->unread_count > 0)
                    <li class="meta__item">
                        <span class="conversationList__item-unreadCount">{{ $conversation->unread_count }}</span>
                    </li>
                @endif
                <li class="meta__item">
                    <time
                        class="message__time"
                        datetime="{{ $lastMessage->created_at->toIso8601String() }}"
                        title="{{ $lastMessage->created_at->translatedFormat('j F Y, H:i') }}"
                    >{{ time_diff($lastMessage->created_at) }}</time>
                </li>
            </ul>
        @endif
    </div>
    @if ($conversation->lastMessage)
        <x-avatar class="conversationList__item-avatar" :size="AvatarSize::S" :user="$conversation->lastMessage->user" />
    @endif
</a>