<?php

use App\Enums\Gender;
use App\Lib\Image;
use App\Models\Area;
use App\Models\User;
use App\Rules\AllowedUsername;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public ?int $area_id = null;

    public ?string $birthdate = null;

    public ?Gender $gender = null;

    public ?string $name = null;

    public string $username = '';

    public bool $usernameAvailable = false;

    /** @var array{id: string, email: string, name: string, avatar: ?string} */
    protected array $oauthData = [];

    #[Computed]
    public function areas()
    {
        return Area::all();
    }

    public function mount(): void
    {
        $oauthData = session('oauth.google');

        if (!$oauthData) {
            $this->redirect(route('login'), navigate: true);
            return;
        }

        $this->name = $oauthData['name'] ?? '';
        $this->oauthData = $oauthData;
    }

    public function render()
    {
        return $this->view()
            ->layout('layouts.simple')
            ->title(__('user/register-oauth.title'));
    }

    protected function messages(): array
    {
        return [
            'username.unique' => __('validation.username.unique'),
        ];
    }

    public function rules(): array
    {
        return [
            'area_id' => ['nullable', 'exists:areas,id'],
            'birthdate' => ['nullable', 'date'],
            'gender' => ['nullable', Rule::enum(Gender::class)],
            'name' => ['nullable', 'string', 'max:255'],
            'username' => [
                'required',
                'max:20',
                'unique:users,username',
                new AllowedUsername(),
            ],
        ];
    }

    public function submit(): void
    {
        $oauthData = session('oauth.google');

        if (!$oauthData) {
            $this->redirect(route('login'), navigate: true);
            return;
        }

        $this->validate();

        $user = User::create([
            'area_id' => $this->area_id ?: null,
            'birthdate' => $this->birthdate ?: null,
            'email' => $oauthData['email'],
            'email_verified_at' => now(),
            'gender' => $this->gender ?: null,
            'google_id' => $oauthData['id'],
            'name' => $oauthData['name'],
            'username' => strtolower($this->username),
        ]);

        $this->downloadAvatar($user, $oauthData['avatar']);

        session()->forget('oauth.google');

        Auth::login($user, remember: true);

        $this->redirect(route('home'), navigate: true);
    }

    public function updatedUsername(): void
    {
        $this->usernameAvailable = false;
        $this->validateOnly('username');
        $this->usernameAvailable = true;
    }

    private function downloadAvatar(User $user, ?string $avatarUrl): void
    {
        if (! $avatarUrl) {
            return;
        }

        // Get 512px version of the avatar
        $avatarUrl = preg_replace('/=s\d+(-c)?$/', "=s512$1", $avatarUrl);

        try {
            $image = new Image;
            $contents = $image->read($avatarUrl)->encode(80);
            $avatarPath = env('APP_PATH_AVATARS') . DIRECTORY_SEPARATOR . $user->username . '.webp';

            if (Storage::disk('public')->put($avatarPath, $contents)) {
                $user->has_avatar = true;
                $user->save();
            }
        } catch (\Throwable) {
            // Avatar download failure is non-fatal
        }
    }
};
?>

<div>
    <x-header
        center
        hide-path
        :intro="__('user/register-oauth.text')"
        :title="__('user/register-oauth.title')"
    />
    <form class="flex flex-col flex-gap-xl" wire:submit="submit">
        <fieldset class="flex flex-col flex-gap-m">
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
            <x-btn primary submit>@lang('user/register-oauth.form.submit')</x-btn>
        </div>
    </form>
</div>
