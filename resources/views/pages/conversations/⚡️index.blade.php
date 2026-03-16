<?php

use App\Constants\Event;
use App\Enums\AvatarSize;
use App\Events\MessageCreated;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public ?int $conversation_id = null;

    #[Computed]
    public function conversations(): LengthAwarePaginator
    {
        $userId = auth()->id();

        $paginator = auth()->user()->conversations()
            ->with(['users'])
            ->withCount('messages')
            ->orderByDesc(
                DB::raw('(SELECT COALESCE(MAX(m.created_at), conversations.updated_at) FROM messages m WHERE m.conversation_id = conversations.id)')
            )
            ->paginate(Conversation::PAGINATE_COUNT, pageName: 'p')
            ->setPath(route('conversations'));

        $conversationIds = $paginator->getCollection()->pluck('id');

        $unreadCounts = Message::query()
            ->whereIn('messages.conversation_id', $conversationIds)
            ->where('messages.user_id', '!=', $userId)
            ->join('conversation_user', function ($join) use ($userId) {
                $join->on('conversation_user.conversation_id', '=', 'messages.conversation_id')
                    ->where('conversation_user.user_id', $userId);
            })
            ->where(function ($q) {
                $q->whereNull('conversation_user.last_read_at')
                    ->orWhereColumn('messages.created_at', '>', 'conversation_user.last_read_at');
            })
            ->selectRaw('messages.conversation_id, count(*) as unread')
            ->groupBy('messages.conversation_id')
            ->pluck('unread', 'conversation_id');

        $paginator->getCollection()->transform(function (Conversation $conversation) use ($unreadCounts) {
            $conversation->setAttribute('unread_count', (int) ($unreadCounts[$conversation->id] ?? 0));
            return $conversation;
        });

        return $paginator;
    }

    private function latestConversation(): ?Conversation
    {
        return auth()->user()->conversations()
            ->orderByDesc(
                DB::raw('(SELECT COALESCE(MAX(m.created_at), conversations.updated_at) FROM messages m WHERE m.conversation_id = conversations.id)')
            )
            ->first();
    }

    public function mount(?int $conversation_id = null): void
    {
        $this->conversation_id = $conversation_id;

        if (!$conversation_id) {
            $this->conversation_id = $this->latestConversation()->id;
            // $latest = $this->latestConversation();

            // if ($latest) {
            //     $this->redirect(route('conversations', $latest), navigate: true);
            //     return;
            // }
        }
    }

    #[On(Event::CONVERSATION_OPENED)]
    public function render()
    {
        return $this->view()
            ->layout('layouts.app', ['pageClass' => 'page--full'])
            ->title(__('conversations/index.title'));
    }

    public function setConversationId(int $conversation_id): void
    {
        $this->conversation_id = $conversation_id;
        $this->dispatch(Event::CONVERSATION_OPENED);
    }
};
?>

<div
    class="conversations"
    x-data="{ openConversation: true }"
    x-on:conversation-opened.window="openConversation = true"
    x-on:conversation-closed.window="openConversation = false"
    x-bind:class="{ 'is-openConversation': openConversation }"
>
    @if ($this->conversations->isNotEmpty())
        <div class="conversations__sidebar">
            {{-- <div class="conversations__new">
                <x-btn :href="route('messages.create')" icon="plus" primary wire:navigate>@lang('conversations/index.btn_new')</x-btn>
            </div> --}}
            <div class="conversationList">
                @forelse ($this->conversations as $conversation)
                    <x-conversations.list-item
                        :conversation="$conversation"
                        :isActive="$conversation->id === $this->conversation_id"
                    />
                @empty
                    <div class="conversationList__empty">@lang('conversations/index.empty')</div>
                @endforelse
            </div>
        </div>
        <div class="conversations__main">
            @if ($conversation_id)
                <livewire:conversation :conversation-id="$conversation_id" :wire:key="'conversation-' . $conversation_id" />
            @else
                <p class="conversations__empty">@lang('conversations/index.not_found')</p>
            @endif
        </div>
    @else
        <div class="conversations__empty">@lang('conversations/index.empty')</div>
    @endif
</div>