<?php

use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;

new class extends Component
{
    public bool $isConfirmed = false;

    public function mount(User $user): void
    {
        $email = (string) request()->query('email');

        $validator = Validator::make(['email' => $email], [
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
        ]);

        if ($validator->fails()) {
            return;
        }

        $user->forceFill(['email' => $email, 'email_verified_at' => now()])->save();

        $this->isConfirmed = true;
    }

    public function render()
    {
        return $this->view()
            ->layout('layouts.simple')
            ->title(__('user/confirm_email_change.'.($this->isConfirmed ? 'success_title' : 'error_title')));
    }
};
?>

<div>
    @if ($isConfirmed)
        <x-header center hide-path :intro="__('user/confirm_email_change.success_text')" :title="__('user/confirm_email_change.success_title')" />
    @else
        <x-header center hide-path :intro="__('user/confirm_email_change.error_text')" :title="__('user/confirm_email_change.error_title')" />
    @endif
    <div class="flex flex-justify-center">
        <x-btn :href="route('home')" navigate primary>@lang('user/confirm_email_change.btn_home')</x-btn>
    </div>
</div>
