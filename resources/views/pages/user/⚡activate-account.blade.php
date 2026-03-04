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
            ->layout('layouts.simple')
            ->title($this->success
                ? __('user/activate_account.success_title')
                : __('user/activate_account.error_title')
            );
    }
};
?>

<div>
    @if ($success)
        <x-header
            center
            :intro="__('user/activate_account.success_text')"
            hide-path
            :title="__('user/activate_account.success_title')"
        />
        <div class="flex flex-gap-s flex-justify-center">
            <x-btn primary href="{{ route('home') }}">@lang('user/activate_account.btn_home')</x-btn>
            <x-btn icon="mail" href="mailto:info@keiforum.nl">@lang('user/activate_account.btn_help')</x-btn>
        </div>
    @else
        <x-header
            center
            :intro="__('user/activate_account.error_text')"
            hide-path
            :title="__('user/activate_account.error_title')"
        />
        <div class="flex flex-gap-s flex-justify-center">
            <x-btn primary href="{{ route('home') }}">@lang('user/activate_account.btn_home')</x-btn>
            <x-btn icon="mail" href="mailto:info@keiforum.nl" :navigate="false">@lang('user/activate_account.btn_help')</x-btn>
        </div>
    @endif
</div>
