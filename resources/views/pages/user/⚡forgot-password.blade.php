<?php

use Illuminate\Support\Facades\Password;
use Livewire\Component;

new class extends Component
{
    public string $email = '';

    public bool $resetEmailSent = false;

    protected function messages()
    {
        return [
            'email.email' => __('validation.email.invalid'),
        ];
    }

    public function render()
    {
        return $this->view()
            ->layout('layouts.simple')
            ->title(__('user/forgot_password.title'));
    }

    public function rules()
    {
        return [
            'email' => ['required', 'email'],
        ];
    }

    public function submit(): void
    {
        $this->validate();

        Password::sendResetLink(['email' => $this->email]);

        $this->resetEmailSent = true;
    }
};
?>

<div>
    @if ($resetEmailSent)
        <x-header center hide-path :title="__('user/forgot_password.reset_email_sent_title')" />
        <x-callout
            icon="mail"
            success
            :text="__('user/forgot_password.reset_email_sent_text')"
        />
    @else
        <x-header
            center
            hide-path
            :intro="__('user/forgot_password.text', ['login_url' => route('login')])"
            :title="__('user/forgot_password.title')"
        />
        <div class="flex flex-col flex-gap-l" style="margin: 0 auto; max-width: 400px;">
            <form class="flex flex-col flex-gap-m" wire:submit="submit">
                <x-field model="email">
                    <x-input.text
                        autocomplete="email"
                        large
                        model="email"
                        :placeholder="__('user/forgot_password.form.email.placeholder')"
                        required
                        type="email"
                    />
                </x-field>
                <div class="actions">
                    <x-btn primary submit>@lang('user/forgot_password.form.submit')</x-btn>
                </div>
            </form>
        </div>
    @endif
</div>
