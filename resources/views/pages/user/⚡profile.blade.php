<?php

use App\Enums\Gender;
use App\Enums\AvatarSize;
use App\Lib\Image;
use App\Mail\ActivateAccount;
use App\Models\Area;
use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public $avatar;

    public $area_id;

    public $bio;

    public $birthdate;

    public $gender;

    public $name;

    public User $user;

    #[Computed]
    public function areas()
    {
        return Area::all();
    }

    protected function deleteCachedAvatars(string $avatarPath)
    {
        foreach (AvatarSize::cases() as $size) {
            $cachePath = Image::cacheFilePath($avatarPath, $size->value, $size->value);

            if (Storage::disk('public')->exists($cachePath)) {
                Storage::disk('public')->delete($cachePath);
            }
        }
    }
    
    public function mount()
    {
        $this->user = auth()->user();

        $this->area_id = $this->user->area_id;
        $this->bio = $this->user->bio;
        $this->birthdate = $this->user->birthdate?->format('Y-m-d');
        $this->gender = $this->user->gender;
        $this->name = $this->user->name;
    }

    public function removeAvatar()
    {
        $this->avatar = null;

        if (Storage::disk('public')->exists($this->user->avatar)) {
            Storage::disk('public')->delete($this->user->avatar);
        }

        $this->deleteCachedAvatars($this->user->avatar);
        $this->user->has_avatar = false;
        $this->user->save();
    }

    public function render()
    {
        return $this->view()
            ->layout('layouts.simple')
            ->title(__('user/profile.title'));
    }

    public function rules()
    {
        return [
            'area_id' => ['nullable', 'exists:areas,id'],
            'avatar' => ['nullable', 'image', 'max:5120'],
            'bio' => ['nullable', 'string', 'max:255'],
            'birthdate' => ['nullable', 'date'],
            'gender' => ['nullable', Rule::enum(Gender::class)],
            'name' => ['required', 'string'],
        ];
    }

    public function submitRules(): array
    {
        return collect($this->rules())->except('avatar')->all();
    }

    public function submit()
    {
        $this->validate($this->submitRules());

        $this->user->area_id = empty($this->area_id) ? null : $this->area_id;
        $this->user->bio = empty($this->bio) ? null : $this->bio;
        $this->user->birthdate = empty($this->birthdate) ? null : $this->birthdate;
        $this->user->gender = empty($this->gender) ? null : $this->gender;
        $this->user->name = $this->name;
        $this->user->save();
    }

    public function updatedAvatar()
    {
        $this->validateOnly('avatar');

        if ($this->avatar && get_class($this->avatar) === TemporaryUploadedFile::class) {
            $avatar = new Image();
            $avatarContents = $avatar
                ->read($this->avatar->getRealPath())
                ->resize(1024)
                ->encode(80);
            $avatarPath = config('app.path_avatars') . DIRECTORY_SEPARATOR . $this->user->username . '.webp';

            if (Storage::disk('public')->put($avatarPath, $avatarContents)) {
                $this->deleteCachedAvatars($avatarPath);
                $this->user->has_avatar = true;
                $this->user->save();
            }

            $this->avatar = null;
        }
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
    <x-header center hide-path :title="__('user/profile.title')" />
    <form class="flex flex-col flex-gap-xl" wire:submit="submit">
        <fieldset class="flex flex-col flex-gap-l">
            <div class="flex flex-align-center flex-gap-l">
                <div class="flex-flex">
                    <x-field
                        :description="__('user/profile.form.avatar.description')"
                        :label="__('user/profile.form.avatar.label')"
                        model="avatar"
                    >
                        <x-input.upload model="avatar" />
                    </x-field>
                </div>
                <div class="avatar avatar--l" wire:loading.class="is-loading" wire:target="avatar">
                    @php
                        $avatarUrl = $user->has_avatar
                            ? $user->avatarUrl(size: AvatarSize::L->value) . '&t=' . $user->updated_at->timestamp
                            : $user->avatarUrl(size: AvatarSize::L->value);

                        if ($avatar && get_class($avatar) === TemporaryUploadedFile::class) {
                            $avatarUrl = $avatar->temporaryUrl();
                        }
                    @endphp
                    @if ($avatarUrl)
                        <figure class="avatar__clip">
                            <img alt="{{ $user->username }}" height="128" src="{{ $avatarUrl }}" width="128">
                        </figure>
                        @if($user->has_avatar || $avatar)
                            <button
                                aria-label="@lang('user/profile.form.avatar.delete')"
                                class="avatar__remove"
                                title="@lang('user/profile.form.avatar.delete')"
                                type="button"
                                wire:click="removeAvatar()"
                            ><x-icon icon="x" /></button>
                        @endif
                    @endif
                </div>
            </div>
            <x-field
                :description="__('user/profile.form.name.description')"
                :label="__('user/profile.form.name.label')"
                model="name"
            >
                <x-input.text autocomplete="name" model="name" required />
            </x-field>
            <x-field
                :description="__('user/profile.form.bio.description')"
                :label="__('user/profile.form.bio.label')"
                model="bio"
            >
                <x-input.textarea model="bio" rows="2" />
            </x-field>
            <x-field
                class="flex-flex"
                :description="__('user/profile.form.area_id.description')"
                :label="__('user/profile.form.area_id.label')"
                model="area_id"
            >
                <x-input.select
                    :empty="__('user/profile.form.area_id.empty')"
                    model="area_id"
                    :options="$this->areas->pluck('name', 'id')"
                />
            </x-field>
            <div class="flex flex-col flex-gap-m l:flex-gap-m l:flex-row">
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
                <x-field
                    class="flex-flex"
                    :description="__('user/profile.form.birthdate.description')"
                    :label="__('user/profile.form.birthdate.label')"
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
            </div>
        </fieldset>
        <div class="flex flex-gap-m">
            <x-btn primary submit>@lang('ui.save')</x-btn>
            <x-btn :href="route('member.show', $user)" text>@lang('ui.cancel')</x-btn>
        </div>
    </form>
</div>