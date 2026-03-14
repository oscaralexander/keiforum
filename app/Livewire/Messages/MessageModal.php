<?php

namespace App\Livewire\Messages;

use App\Events\MessageCreated;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\View\View;
use LivewireUI\Modal\ModalComponent;

class MessageModal extends ModalComponent
{
    public string $body;

    public bool $redirect = true;

    public User $user;

    public function mount(string $username): void
    {
        $this->user = User::where('username', $username)->firstOrFail();
    }

    public function render(): View
    {
        return view('livewire.messages.message-modal');
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string'],
        ];
    }

    public function submit()
    {
        $this->validate();

        $conversation = Conversation::firstOrCreateForParticipants([auth('web')->id(), $this->user->id]);
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => auth('web')->id(),
            'body' => $this->body,
        ]);

        MessageCreated::dispatch($message);

        if ($this->redirect) {
            $this->redirect(route('conversations', $conversation->id), navigate: true);
        }

        $this->closeModal();
    }
}
