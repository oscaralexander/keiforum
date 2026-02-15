<?php

use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

new class extends Component
{
    public bool $success = false;

    public function mount(string $token): void
    {
        if (blank($token)) {
            return;
        }

        $user = User::query()
            ->where('email_verification_token', $token)
            ->first();

        if (!$user) {
            return;
        }

        if ($user->email_verified_at) {
            $this->success = true;
            return;
        }

        $user->forceFill([
            'email_verified_at' => now(),
            'email_verification_token' => null,
        ])->save();

        $this->success = true;
    }

    public function render()
    {
        return $this->view()
            ->title($this->success
                ? __('user/activate_account.success_title')
                : __('user/activate_account.error_title')
            );
    }
};
?>

<div class="flex flex-col flex-gap-xl">
    <div class="panel flex flex-col flex-gap-m">
        @if ($success)
            <h1>@lang('user/activate_account.success_title')</h1>
            <p class="formatted">
                @lang('user/activate_account.success_text')
            </p>
        @else
            <h1>@lang('user/activate_account.error_title')</h1>
            <p class="formatted">
                @lang('user/activate_account.error_text')
            </p>
        @endif
    </div>
</div>
