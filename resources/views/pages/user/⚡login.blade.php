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
    public string $identifier;

    public string $password;

    public bool $remember = false;

    protected function messages() 
    {
        return [
            'email.unique' => __('validation.email.unique'),
            'password.letters' => __('validation.password.letters'),
            'password.min' => __('validation.password.min'),
            'password.mixed' => __('validation.password.mixed'),
            'password.numbers' => __('validation.password.numbers'),
            'terms.accepted' => __('validation.terms.accepted'),
            'username.unique' => __('validation.username.unique'),
        ];
    }

    public function render()
    {
        return $this->view()
            ->title(__('user/login.title'));
    }

    public function rules()
    {
        return [
            'identifier' => ['required'],
            'password' => ['required'],
        ];
    }

    public function submit()
    {
        $this->validate();

        $key = filter_var($this->identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        if (Auth::attempt([$key => $this->identifier, 'password' => $this->password], $this->remember)) {
            return $this->redirect(route('home'), navigate: true);
        }

        $this->addError('password', __('validation.failed'));
    }
};
?>

<div>
    <div class="panel flex flex-col flex-gap-xl"">
        <h1>@lang('user/login.title')</h1>
        <form class="flex flex-col flex-gap-m" wire:submit="submit">
            <fieldset class="login">
                <x-input.text autocomplete="username" model="identifier" :placeholder="__('user/login.form.identifier.placeholder')" required />
                <x-input.text model="password" :placeholder="__('user/login.form.password.placeholder')" required type="password" />
            </fieldset>
            <x-field model="terms">
                <x-input.toggle :label="__('user/login.form.remember.label')" model="remember" />
            </x-field>
            @error('password')
                <div class="alert alert--bad">
                    <x-icon icon="circle-alert" />
                    <div class="alert__content">
                        @lang('user/login.attempt_failed')
                    </div>
                </div>
            @enderror
            <div class="flex flex-gap-m flex-justify-spaceBetween">
                <x-btn primary submit>@lang('user/login.form.submit')</x-btn>
                <x-btn href="#" text>@lang('user/login.forgot_password')</x-btn>
            </div>
        </form>
    </div>
</div>