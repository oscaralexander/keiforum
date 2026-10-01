<?php

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Livewire\Component;

new class extends Component
{
    public string $email = '';

    public bool $isValidToken = false;

    public string $password = '';

    public string $token = '';

    protected function messages()
    {
        return [
            'password.letters' => __('validation.password.letters'),
            'password.min' => __('validation.password.min'),
            'password.mixed' => __('validation.password.mixed'),
            'password.numbers' => __('validation.password.numbers'),
        ];
    }

    public function mount(string $token): void
    {
        $this->token = $token;
        $this->email = (string) request()->query('email', '');

        $user = User::query()
            ->where('email', $this->email)
            ->first();

        if (! $user) {
            return;
        }

        $this->isValidToken = Password::getRepository()->exists($user, $token);
    }

    public function render()
    {
        return $this->view()
            ->layout('layouts.simple')
            ->title($this->isValidToken
                ? __('user/reset_password.title')
                : __('user/reset_password.error_title')
            );
    }

    public function rules()
    {
        return [
            'password' => ['required', PasswordRule::defaults()],
        ];
    }

    public function submit()
    {
        $this->validate();

        $status = Password::reset([
            'email' => $this->email,
            'password' => $this->password,
            'token' => $this->token,
        ], function (User $user, string $password): void {
            $user->forceFill([
                'password' => Hash::make($password),
                'remember_token' => Str::random(60),
            ])->save();

            event(new PasswordReset($user));
        });

        if ($status !== Password::PASSWORD_RESET) {
            $this->isValidToken = false;

            return;
        }

        auth()->login(User::query()->where('email', $this->email)->first());

        return $this->redirect(route('home'), navigate: true);
    }
};
?>

<div>
    @if ($isValidToken)
        <x-header
            center
            hide-path
            :intro="__('user/reset_password.text')"
            :title="__('user/reset_password.title')"
        />
        <div class="flex flex-col flex-gap-l" style="margin: 0 auto; max-width: 400px;">
            <form class="flex flex-col flex-gap-m" wire:submit="submit">
                <x-field :label="__('user/reset_password.form.password.label')" model="password">
                    <x-input.password autocomplete="new-password" model="password" required />
                </x-field>
                <div class="flex flex-justify-end">
                    <x-btn primary submit>@lang('user/reset_password.form.submit')</x-btn>
                </div>
            </form>
        </div>
    @else
        <x-header
            center
            :intro="__('user/reset_password.error_text')"
            hide-path
            :title="__('user/reset_password.error_title')"
        />
        <div class="flex flex-gap-s flex-justify-center">
            <x-btn href="{{ route('forgot-password') }}" primary>@lang('user/reset_password.btn_forgot_password')</x-btn>
        </div>
    @endif
</div>
