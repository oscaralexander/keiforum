<?php

use App\Mail\ConfirmEmailChange;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component
{
    public string $email;

    #[Locked]
    public ?string $emailChangeSentTo = null;

    public bool $isSubscribedToDigest;

    public string $password_current = '';

    public string $password_new = '';

    #[Locked]
    public User $user;

    protected function messages(): array
    {
        return [
            'email.email' => __('validation.email.invalid'),
            'email.unique' => __('validation.email.unique'),
            'password_current.current_password' => __('validation.password.incorrect'),
            'password_new.letters' => __('validation.password.letters'),
            'password_new.min' => __('validation.password.min'),
            'password_new.mixed' => __('validation.password.mixed'),
            'password_new.numbers' => __('validation.password.numbers'),
        ];
    }

    public function mount(): void
    {
        $this->user = auth()->user();

        $this->email = $this->user->email;
        $this->isSubscribedToDigest = $this->user->is_subscribed_to_digest ?? true;
    }

    public function render()
    {
        return $this->view()
            ->layout('layouts.simple')
            ->title(__('user/settings.title'));
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user->id)],
            'isSubscribedToDigest' => ['boolean'],
            'password_current' => [Rule::requiredIf($this->requiresCurrentPassword()), 'nullable', 'current_password'],
            'password_new' => ['nullable', Password::defaults()],
        ];
    }

    /**
     * Changing the email address or password requires the current password,
     * unless the member only signs in with Google and has none yet.
     */
    public function requiresCurrentPassword(): bool
    {
        return $this->user->password !== null
            && ($this->isChangingEmail() || $this->password_new !== '');
    }

    public function isChangingEmail(): bool
    {
        return strtolower($this->email) !== strtolower($this->user->email);
    }

    public function submit(): void
    {
        $this->validate();

        $this->user->is_subscribed_to_digest = $this->isSubscribedToDigest;

        if ($this->password_new !== '') {
            $this->user->password = $this->password_new;
        }

        $this->user->save();

        if ($this->isChangingEmail()) {
            Mail::to($this->email)->send(new ConfirmEmailChange($this->user, $this->email));
            $this->emailChangeSentTo = $this->email;
            $this->email = $this->user->email;
        }

        $this->reset('password_current', 'password_new');

        $this->dispatch('toast', message: __('user/settings.saved'), type: 'success');
    }
};
?>

<div>
    <x-header center hide-path :title="__('user/settings.title')" />
    <form class="flex flex-col flex-gap-xl" wire:submit="submit">
        @if ($emailChangeSentTo)
            <x-callout
                icon="mail"
                success
                :text="__('user/settings.email_change_sent', ['email' => e($emailChangeSentTo)])"
            />
        @endif
        <fieldset class="flex flex-col flex-gap-l">
            <x-field
                :description="__('user/settings.form.email.description')"
                :label="__('user/settings.form.email.label')"
                model="email"
            >
                <x-input.text autocomplete="email" model="email" type="email" />
            </x-field>
            <x-field :label="__('user/settings.form.password_new.label')" model="password_new">
                <x-input.password autocomplete="new-password" model="password_new" />
            </x-field>
            @if ($user->password !== null)
                <x-field
                    :label="__('user/settings.form.password_current.label')"
                    model="password_current"
                    x-cloak
                    x-show="$wire.password_new || $wire.email.toLowerCase() !== {{ Js::from(strtolower($user->email)) }}"
                >
                    <x-input.text autocomplete="current-password" model="password_current" type="password" />
                </x-field>
            @endif
            <x-input.toggle id="isSubscribedToDigest" :label="__('user/settings.notifications.digest')" model="isSubscribedToDigest" />
        </fieldset>
        <div class="flex flex-gap-m">
            <x-btn primary submit>@lang('ui.save')</x-btn>
            <x-btn :href="route('member.show', $user)" text>@lang('ui.cancel')</x-btn>
        </div>
    </form>
</div>
