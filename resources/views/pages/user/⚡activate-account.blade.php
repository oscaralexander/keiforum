<?php

use App\Models\User;
use Livewire\Component;

new class extends Component
{
    public bool $success = false;
    public bool $isValidToken = false;
    public string $token = '';

    public function mount(string $token): void
    {
        if (blank($token)) {
            return;
        }

        $this->token = $token;

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

        $this->isValidToken = true;
    }

    public function activate(): void
    {
        $user = User::query()
            ->where('email_verification_token', $this->token)
            ->first();

        if (!$user || $user->email_verified_at) {
            $this->success = true;
            $this->isValidToken = false;
            return;
        }

        $user->forceFill([
            'email_verified_at' => now(),
            'email_verification_token' => null,
        ])->save();

        auth()->login($user);

        $this->isValidToken = false;
        $this->success = true;
    }

    public function render()
    {
        return $this->view()
            ->layout('layouts.simple')
            ->title($this->success
                ? __('user/activate_account.success_title')
                : ($this->isValidToken
                    ? __('user/activate_account.confirm_title')
                    : __('user/activate_account.error_title'))
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
            <x-btn href="{{ route('home') }}" primary>@lang('user/activate_account.btn_home')</x-btn>
            <x-btn href="{{ route('profile') }}">@lang('user/activate_account.btn_profile')</x-btn>
        </div>
    @elseif ($isValidToken)
        <x-header
            center
            :intro="__('user/activate_account.confirm_text')"
            hide-path
            :title="__('user/activate_account.confirm_title')"
        />
        <div class="flex flex-gap-s flex-justify-center">
            <x-btn primary wire:click="activate">@lang('user/activate_account.btn_confirm')</x-btn>
        </div>
    @else
        <x-header
            center
            :intro="__('user/activate_account.error_text')"
            hide-path
            :title="__('user/activate_account.error_title')"
        />
        <div class="flex flex-gap-s flex-justify-center">
            <x-btn href="{{ route('login') }}" primary>@lang('user/activate_account.btn_login')</x-btn>
            <x-btn href="mailto:info@keiforum.nl" icon="mail" :navigate="false">@lang('user/activate_account.btn_help')</x-btn>
        </div>
    @endif
</div>
