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
    public int $area_id;

    public string $birthdate;

    public string $email;

    public bool $activationEmailSent = false;

    public Gender $gender;

    public string $name;

    public string $password;

    public bool $terms = false;

    public string $username;

    public bool $usernameAvailable = false;

    #[Computed]
    public function areas()
    {
        return Area::all();
    }

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
            ->layout('layouts.simple')
            ->title(__('user/register.title'));
    }

    public function rules()
    {
        return [
            'area_id' => ['nullable', 'exists:areas,id'],
            'birthdate' => ['nullable', 'date'],
            'email' => ['required', 'email', 'unique:users,email'],
            'gender' => ['nullable', Rule::enum(Gender::class)],
            'name' => ['required'],
            'password' => ['required', Password::defaults()],
            'terms' => ['required', 'accepted'],
            'username' => [
                'required',
                'max:20',
                'unique:users,username',
                new AllowedUsername()
            ],
        ];
    }

    public function submit()
    {
        $this->validate();

        $user = User::create([
            'area_id' => $this->area_id ?? null,
            'birthdate' => $this->birthdate ?? null,
            'email' => $this->email,
            'email_verification_token' => Str::random(32),
            'gender' => $this->gender ?? null,
            'name' => $this->name,
            'password' => Hash::make($this->password),
            'username' => strtolower($this->username),
        ]);

        Mail::to($this->email)->send(new ActivateAccount($user));

        $this->activationEmailSent = true;
    }

    public function updatedUsername($value)
    {
        $this->usernameAvailable = false;
        $this->validateOnly('username');
        $this->usernameAvailable = true;
    }
};
?>

<div>
    @if ($activationEmailSent)
        <x-header center hide-path :title="__('user/register.activation_email_sent_title')" />
        <div class="callout callout--success">
            <x-icon class="callout__icon" icon="mail" />
            <div class="callout__content">
                @lang('user/register.activation_email_sent_callout')
            </div>
        </div>
    @else
        <x-header
            center
            hide-path
            :intro="__('user/register.text', ['login_url' => route('login')])"
            :title="__('user/register.title')"
        />
        <form class="flex flex-col flex-gap-xl" wire:submit="submit">
            <fieldset class="flex flex-col flex-gap-m">
                <x-field
                    :description="__('user/register.form.email.description')"
                    :label="__('user/register.form.email.label')"
                    model="email"
                >
                    <x-input.text
                        autocomplete="email"
                        model="email"
                        required
                        type="email"
                    />
                </x-field>
                <x-field
                    :description="__('user/register.form.username.description')"
                    :label="__('user/register.form.username.label')"
                    model="username"
                >
                    <x-input.text
                        autocomplete="username"
                        maxlength="16"
                        required
                        style="text-transform: lowercase;"
                        wire:model.live.debounce.250ms="username"
                    />
                    @if ($usernameAvailable)
                        <div class="field__success">
                            <x-icon icon="check" />
                            @lang('validation.username.available')
                        </div>
                    @endif
                </x-field>
                <x-field class="flex-flex" :label="__('user/register.form.password.label')" model="password">
                    <x-input.password autocomplete="new-password" model="password" required />
                </x-field>
                <x-field
                    :description="__('user/register.form.name.description')"
                    :label="__('user/register.form.name.label')"
                    model="name"
                >
                    <x-input.text autocomplete="name" model="name" required />
                </x-field>
                <x-field
                    :description="__('user/register.form.area_id.description')"
                    :label="__('user/register.form.area_id.label')"
                    model="area_id"
                >
                    <x-input.select
                        :empty="__('user/register.form.area_id.empty')"
                        model="area_id"
                        :options="$this->areas->pluck('name', 'id')"
                    />
                </x-field>
                <div class="flex flex-col flex-gap-m l:flex-gap-m l:flex-row">
                    <x-field
                        class="flex-flex"
                        :description="__('user/register.form.birthdate.description')"
                        :label="__('user/register.form.birthdate.label')"
                        model="birthdate"
                    >
                        <x-input.text
                            autocomplete="bday"
                            max="{{ now()->subYears(12)->format('Y-m-d') }}"
                            min="{{ now()->subYears(100)->format('Y-m-d') }}"
                            model="birthdate"
                            type="date"
                        />
                    </x-field>
                    <x-field
                        class="flex-flex"
                        :description="__('user/register.form.gender.description')"
                        :label="__('user/register.form.gender.label')"
                        model="gender"
                    >
                        <x-input.select
                            :empty="__('user/register.form.gender.empty')"
                            model="gender"
                            :options="Gender::options()"
                        />
                    </x-field>
                </div>
                <x-field model="terms">
                    <x-input.toggle :label="__('user/register.form.terms.label', ['terms_url' => route('terms')])" model="terms" />
                </x-field>
            </fieldset>
            <div class="flex flex-gap-m">
                <x-btn primary submit>@lang('user/register.form.submit')</x-btn>
            </div>
        </form>
    @endif
</div>