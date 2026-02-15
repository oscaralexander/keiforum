<?php

use App\Enums\Gender;
use App\Enums\AvatarSize;
use App\Lib\Image;
use App\Mail\ActivateAccount;
use App\Models\Area;
use App\Models\User;
use App\Rules\AllowedUsername;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

new class extends Component
{
    public User $user;
    
    public function mount(User $user)
    {
        $this->user = auth()->user();
    }

    public function render()
    {
        return $this->view()
            ->title($this->user->username);
    }
};
?>

<div class="flex flex-col flex-gap-xl">
    <div class="panel">
        <h1>{{ $user->username }}</h1>
    </div>
</div>