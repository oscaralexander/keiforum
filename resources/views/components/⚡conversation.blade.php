<?php

use App\Models\Conversation;
use App\Models\Message;
use App\Enums\AvatarSize;
use App\Events\MessageCreated;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Illuminate\Support\Collection;

new class extends Component
{
    #[Locked]
    public int $conversation_id;

    public int $limit = Message::PAGINATE_COUNT;

    public string $body;

    #[Computed]
    public function conversation(): Conversation
    {
        if (!$this->conversation_id) {
            abort(404);
        }

        $conversation = Conversation::with(['users'])
            ->findOrFail($this->conversation_id);

        if (!$conversation->users->contains(auth()->id())) {
            abort(403);
        }

        return $conversation;
    }

    #[Computed]
    public function hasMoreMessages(): bool
    {
        if (!$this->conversation) {
            return false;
        }

        return Message::query()
            ->where('conversation_id', $this->conversation->id)
            ->count() > $this->limit;
    }

    public function loadMoreMessages(): void
    {
        $this->limit += Message::PAGINATE_COUNT;
    }

    public function mount(int $conversationId): void
    {
        $this->conversation_id = $conversationId;
    }

    #[Computed]
    public function conversationMessages(): Collection
    {
        if (!$this->conversation) {
            return collect();
        }

        return Message::query()
            ->where('conversation_id', $this->conversation->id)
            ->latest()
            ->limit(Message::PAGINATE_COUNT)
            ->get()
            ->reverse()
            ->values();
    }

    public function rules()
    {
        return [
            'body' => 'required|string',
        ];
    }

    public function submit()
    {
        $this->validate();

        $message = Message::create([
            'conversation_id' => $this->conversation_id,
            'user_id' => auth()->id(),
            'body' => $this->body,
        ]);

        MessageCreated::dispatch($message);

        $this->body = '';
        $this->dispatch('message-sent');
    }
};
?>

<div class="conversation">
    <header class="conversation__header">
        <button class="conversation__back" x-on:click="$dispatch('conversation-closed')">
            <x-icon icon="chevron-left" />
        </button>
        <div class="conversation__participants">
            @foreach ($this->conversation->otherParticipants() as $participant)
                <a class="conversation__participant" href="{{ route('member.show', $participant) }}" wire:navigate>
                    <x-avatar class="conversation__participant-avatar" :size="AvatarSize::S" :user="$participant" />
                    <div class="conversation__participant-text">
                        <div class="conversation__participant-username">{{ $participant->username }}</div>
                        <div class="conversation__participant-lastSeen">{{ __('conversations/show.active_ago', ['time' => time_diff($participant->last_seen_at)]) }}</div>
                    </div>
                </a>
            @endforeach
        </div>
        <div class="conversation__header-actions">
            <x-popout>
                <x-popout.item icon="trash" danger :label="__('ui.delete')" />
            </x-popout>
        </div>
    </header>
    <div
        class="conversation__messages"
        x-data="conversationScroll"
        x-init="scrollToBottom()"
        x-on:message-sent.window="$nextTick(() => scrollToBottom())"
    >
        @if ($this->hasMoreMessages)
            <div
                class="conversation__loader"
                wire:key="load-more"
            >
                <button type="button" wire:click="loadMoreMessages" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="loadMoreMessages">@lang('conversations/show.load_more')</span>
                    <span wire:loading wire:target="loadMoreMessages">@lang('conversations/show.loading')</span>
                </button>
            </div>
        @endif
        @foreach ($this->conversationMessages as $message)
            @if ($loop->first || !$message->created_at->isSameDay($this->conversationMessages[$loop->index - 1]->created_at))
                <div class="conversation__date" wire:key="date-{{ $message->id }}">
                    <span>
                        @if ($message->created_at->isToday())
                            @lang('conversations/show.today')
                        @elseif ($message->created_at->isYesterday())
                            @lang('conversations/show.yesterday')
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
    <form class="conversation__reply" wire:submit="submit">
        <div class="conversation__reply-inner">
            <x-input.textarea
                autofocus
                max-rows="3"
                model="body"
                placeholder="{{ __('conversations/show.reply') }}"
                required
                x-on:keydown.enter="if (!$event.shiftKey) { $event.preventDefault(); $wire.submit(); }"
            />
            <x-btn icon="send" primary submit />
        </div>
    </form>
</div>