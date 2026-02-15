<?php

use App\Enums\AvatarSize;
use App\Models\Message;
use Livewire\Component;

new class extends Component
{
    public string $body = '';

    public bool $isEditing = false;

    public Message $message;

    public function edit(): void
    {
        $this->isEditing = true;
    }

    public function mount(Message $message): void
    {
        $this->message = $message;
        $this->body = $message->body;
    }

    public function rules(): array
    {
        return [
            'body' => 'required|string',
        ];
    }

    public function submit(): void
    {
        $this->validate();

        $this->message->update(['body' => $this->body]);
        $this->isEditing = false;
    }
};
?>

<div
    @class([
        'message',
        'message--own' => $message->user_id === auth()->id(),
    ])
    id="message-{{ $message->id }}"
>
    <x-avatar class="message__avatar" :size="AvatarSize::S" :user="$message->user" />
    <div class="message__main">
        <header class="message__header">
            <ul class="meta">
                <li class="meta__item">
                    <a class="message__author" href="{{ route('user.show', $message->user) }}" wire:navigate>{{ $message->user->username }}</a>
                </li>
                <li class="meta__item">
                    <time
                        class="message__time"
                        datetime="{{ $message->created_at->toIso8601String() }}"
                        title="{{ $message->created_at->translatedFormat('j F Y, H:i') }}"
                    >
                        {{ $message->created_at->format('H:i') }}
                    </time>
                </li>
            </ul>
            <div class="message__actions">
                @auth
                    @if ($message->user_id === auth()->id())
                        <x-popout small>
                            <x-popout.item icon="pencil" :label="__('ui.edit')" />
                            <x-popout.item icon="trash" :label="__('ui.delete')" />
                        </x-popout>
                    @endif
                @endauth
            </div>
        </header>
        <div class="message__body">
            @if ($isEditing)
                <form wire:submit="submit">
                    <x-editor model="body" />
                    <x-btn primary submit>@lang('ui.save')</x-btn>
                </form>
            @else
                <div class="formatted">{!! $message->body_transformed !!}</div>
            @endif
        </div>
    </div>
</div>
