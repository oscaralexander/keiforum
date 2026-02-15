<?php

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

    public bool $isComposing = false;

    public string $body = '';

    public string $replyBody = '';

    public array $selectedMembers = [];

    public int $messagesLimit = 3;

    public function mount(?Conversation $conversation = null): void
    {
        $this->isComposing = request()->routeIs('messages.create');

        if (!$this->isComposing && !$conversation) {
            $latest = $this->latestConversation();

            if ($latest) {
                $this->redirect(route('messages', $latest), navigate: true);
                return;
            }

            $this->isComposing = true;
        }

        $this->conversation_id = $conversation?->id;

        if ($conversation && !$this->conversation) {
            abort(404);
        }

        $this->conversation?->users()->updateExistingPivot(auth()->id(), [
            'last_read_at' => now(),
        ]);
    }

    private function latestConversation(): ?Conversation
    {
        return auth()->user()->conversations()
            ->orderByDesc(
                DB::raw('(SELECT COALESCE(MAX(m.created_at), conversations.updated_at) FROM messages m WHERE m.conversation_id = conversations.id)')
            )
            ->first();
    }

    public function addMember(array $user): void
    {
        $id = (int) ($user['id'] ?? 0);
        if ($id && $id !== auth()->id()) {
            $exists = collect($this->selectedMembers)->contains('id', $id);
            if (!$exists) {
                $this->selectedMembers[] = [
                    'id' => $id,
                    'username' => $user['username'] ?? '',
                    'avatar' => $user['avatar'] ?? '/assets/img/avatar.png',
                ];
            }
        }
    }

    public function removeMember(int $id): void
    {
        $this->selectedMembers = array_values(
            array_filter($this->selectedMembers, fn ($m) => (int) ($m['id'] ?? 0) !== $id)
        );
    }

    public function submitNewConversation(): void
    {
        $this->validate([
            'selectedMembers' => 'required|array|min:1',
            'selectedMembers.*.id' => 'required|exists:users,id',
            'body' => 'required|string',
        ]);

        $participantIds = collect($this->selectedMembers)->pluck('id')->push(auth()->id())->unique()->values()->all();

        $conversation = Conversation::firstOrCreateForParticipants($participantIds, auth()->id());

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => auth()->id(),
            'body' => $this->body,
        ]);

        MessageCreated::dispatch($message);

        $this->redirect(route('messages', $conversation));
    }

    #[Computed]
    public function conversation(): ?Conversation
    {
        if (!$this->conversation_id) {
            return null;
        }

        $c = Conversation::with(['users'])
            ->find($this->conversation_id);

        if (!$c || !$c->users->contains(auth()->id())) {
            return null;
        }

        return $c;
    }

    #[Computed]
    public function conversationMessages(): \Illuminate\Support\Collection
    {
        if (!$this->conversation_id) {
            return collect();
        }

        return Message::query()
            ->where('conversation_id', $this->conversation_id)
            ->latest()
            ->limit(Message::PAGINATE_COUNT)
            ->get()
            ->reverse()
            ->values();
    }

    #[Computed]
    public function hasMoreMessages(): bool
    {
        if (!$this->conversation_id) {
            return false;
        }

        return Message::query()
            ->where('conversation_id', $this->conversation_id)
            ->count() > $this->messagesLimit;
    }

    public function loadMoreMessages(): void
    {
        $this->messagesLimit += 25;
    }

    public function submitReply(): void
    {
        $this->validate([
            'replyBody' => 'required|string',
        ]);

        $message = Message::create([
            'conversation_id' => $this->conversation_id,
            'user_id' => auth()->id(),
            'body' => $this->replyBody,
        ]);

        MessageCreated::dispatch($message);

        $this->replyBody = '';
        $this->dispatch('message-sent');
    }

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
            ->setPath(route('messages'));

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

    public function render()
    {
        return $this->view()
            ->layout('layouts.app', ['pageClass' => 'page--full'])
            ->title(__('messages/index.title'));
    }
};
?>

<div>
    <div class="flex flex-col flex-gap-l">
        <div class="conversations">
            <div class="conversations__sidebar">
                <div class="conversations__new">
                    <x-btn :href="route('messages.create')" icon="plus" primary wire:navigate>@lang('messages/index.btn_new')</x-btn>
                </div>
                <div class="conversationList">
                    @forelse ($this->conversations as $conversation)
                        <x-conversations.list-item :conversation="$conversation" :isActive="$conversation->id === $this->conversation_id" />
                    @empty
                        <div class="conversationList__empty">@lang('messages/index.empty')</div>
                    @endforelse
                </div>
            </div>
            <div class="conversations__main">
                @if ($this->isComposing)
                    <x-conversations.form :selectedMembers="$this->selectedMembers" />
                @elseif ($this->conversation)
                    <x-conversation :conversation="$this->conversation" :messages="$this->conversationMessages" :hasMoreMessages="$this->hasMoreMessages" />
                @else
                    <p class="conversations__empty">@lang('messages/index.not_found')</p>
                @endif
            </div>
        </div>
        {{ $this->conversations->links() }}
    </div>
</div>