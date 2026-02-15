<?php

use App\Enums\Gender;
use App\Mail\ActivateAccount;
use App\Models\Area;
use App\Models\User;
use App\Rules\AllowedUsername;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public string $email;

    public string $password_new;

    public string $password_current;

    public User $user;

    public string $username;

    public bool $usernameAvailable = false;

    protected function messages() 
    {
        return [
            'email.unique' => __('validation.email.unique'),
            'password.letters' => __('validation.password.letters'),
            'password.min' => __('validation.password.min'),
            'password.mixed' => __('validation.password.mixed'),
            'password.numbers' => __('validation.password.numbers'),
            'username.unique' => __('validation.username.unique'),
        ];
    }

    public function mount()
    {
        $this->user = auth()->user();

        $this->email = $this->user->email;
        $this->username = $this->user->username;
    }

    public function render()
    {
        return $this->view()
            ->title(__('user/settings.title'));
    }

    public function rules()
    {
        return [
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', Password::defaults()],
            'username' => ['required', 'unique:users,username', new AllowedUsername()],
        ];
    }

    public function submit()
    {
        $this->validate();

        // 
        // Mail::to('mail@keiforum.nl')->send(new VerifyEmail($user));
    }

    public function updatedUsername($value)
    {
        $this->usernameAvailable = false;
        $this->validateOnly('username');
        $this->usernameAvailable = true;
    }
};
?>

<div class="flex flex-col flex-gap-xl">
    <div class="panel">
        <form class="flex flex-col flex-gap-xl" wire:submit="submit">
            <h1>@lang('user/settings.title')</h1>
            <fieldset class="flex flex-col flex-gap-m">
                <x-field :label="__('user/settings.form.email.label')" model="email">
                    <x-input.text autocomplete="email" model="email" type="email" />
                </x-field>
                <x-field class="flex-flex" :label="__('user/settings.form.password_new.label')" model="password_new">
                    <x-input.password autocomplete="new-password" model="password_new" />
                </x-field>
                <x-field
                    class="flex-flex"
                    :label="__('user/settings.form.password_current.label')"
                    model="password_current"
                    x-show="$wire.password_new"
                >
                    <x-input.text model="password_current" type="password" />
                </x-field>
                <x-field
                    :label="__('user/register.form.username.label')"
                    model="username"
                >
                    <x-input.text autocomplete="username" required  style="text-transform: lowercase;" wire:model.blur="username" />
                    @if($usernameAvailable)
                        <div class="field__success">
                            <x-icon icon="check" />
                            @lang('validation.username.available')
                        </div>
                    @endif
                </x-field>
            </fieldset>
            <div class="flex flex-gap-m">
                <x-btn primary submit>@lang('ui.save')</x-btn>
                <x-btn text>@lang('ui.cancel')</x-btn>
            </div>
        </form>
    </div>
</div>