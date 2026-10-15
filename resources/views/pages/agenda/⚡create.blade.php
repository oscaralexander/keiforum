<?php

use App\Enums\ActivityCategory;
use App\Lib\Image;
use App\Livewire\Forms\ActivityForm;
use App\Models\Activity;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public ActivityForm $form;

    public $poster;

    /**
     * The start date before the user changed it, to tell whether the end
     * date still followed it.
     */
    protected ?string $previousStartsOn = null;

    public function mount(): void
    {
        Gate::authorize('create', Activity::class);

        $this->form->starts_on = today()->format('Y-m-d');
        $this->form->ends_on = $this->form->starts_on;
    }

    public function render()
    {
        return $this->view()
            ->title(__('agenda/create.title'));
    }

    public function updatingFormStartsOn(string $value): void
    {
        $this->previousStartsOn = $this->form->starts_on;
    }

    public function updatedFormStartsOn(): void
    {
        $this->form->syncEndDate($this->previousStartsOn ?? '');
    }

    public function updatedPoster(): void
    {
        $this->validateOnly('poster');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'poster' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ];
    }

    public function submit()
    {
        Gate::authorize('create', Activity::class);

        $this->form->validateActivity();
        $this->validate();

        $posterPath = Activity::POSTER_DIRECTORY.'/'.Str::random(40).'.webp';
        $posterContents = (new Image)
            ->read($this->poster->getRealPath())
            ->resize(Activity::POSTER_WIDTH, Activity::POSTER_HEIGHT)
            ->encode(80);

        Storage::disk('public')->put($posterPath, $posterContents);

        $activity = $this->form->store(auth()->user(), $posterPath);

        $this->redirect($activity->url());
    }
};
?>

@push('scripts.auth')
    @vite(['resources/js/auth.js'])
    @if (config('services.google.api_key'))
        <script async src="https://maps.googleapis.com/maps/api/js?key={{ urlencode(config('services.google.api_key')) }}&loading=async&language=nl&region=NL"></script>
    @endif
@endpush

<div>
    <x-header
        :path="[['label' => __('agenda/index.title'), 'href' => route('agenda')]]"
        :title="__('agenda/create.title')"
    />
    <div class="panel panel--padded">
        <form wire:submit="submit">
            <div class="activityForm">
                <div class="activityForm__poster">
                    <x-field
                        :description="__('agenda/form.poster.description', ['width' => Activity::POSTER_WIDTH, 'height' => Activity::POSTER_HEIGHT])"
                        :label="__('agenda/form.poster.label')"
                        model="poster"
                    >
                        <div class="activityPoster activityPoster--placeholder">
                            @if ($poster && ! $errors->has('poster'))
                                <img alt="" class="activityPoster__img" src="{{ $poster->temporaryUrl() }}">
                            @else
                                <x-icon icon="image" />
                            @endif
                        </div>
                        <x-input.upload accept="image/jpeg,image/png,image/webp" model="poster" />
                    </x-field>
                </div>
                <div class="activityForm__fields flex flex-col flex-gap-m">
                    <x-field :label="__('agenda/form.title.label')" model="form.title">
                        <x-input.text large maxlength="120" model="form.title" required />
                    </x-field>
                    <x-field :label="__('agenda/form.category.label')" model="form.category">
                        <x-input.select
                            :empty="__('agenda/form.category.placeholder')"
                            model="form.category"
                            :options="ActivityCategory::options()"
                            required
                        />
                    </x-field>
                    <div class="activityForm__row">
                        <x-field :label="__('agenda/form.starts_on.label')" model="form.starts_on">
                            <x-input.text required type="date" wire:model.live="form.starts_on" />
                        </x-field>
                        <x-field :label="__('agenda/form.starts_at.label')" model="form.starts_at">
                            <x-input.text model="form.starts_at" required type="time" />
                        </x-field>
                    </div>
                    <div class="activityForm__row">
                        <x-field :label="__('agenda/form.ends_on.label')" model="form.ends_on">
                            <x-input.text :min="$form->starts_on" model="form.ends_on" required type="date" />
                        </x-field>
                        <x-field :label="__('agenda/form.ends_at.label')" model="form.ends_at">
                            <x-input.text model="form.ends_at" type="time" />
                        </x-field>
                    </div>
                    <x-field :label="__('agenda/form.description.label')" model="form.description">
                        <x-editor model="form.description" required />
                    </x-field>
                    <x-field
                        :description="config('services.google.api_key') ? __('agenda/form.location.description') : null"
                        :label="__('agenda/form.location.label')"
                        model="form.location_name"
                    >
                        @if (config('services.google.api_key'))
                            <x-input.place-autocomplete
                                :no-results="__('agenda/form.location.no_results')"
                                :placeholder="__('agenda/form.location.search_placeholder')"
                            />
                        @endif
                        <x-input.text
                            model="form.location_name"
                            :placeholder="__('agenda/form.location_name.placeholder')"
                            required
                        />
                        <x-input.text
                            model="form.location_address"
                            :placeholder="__('agenda/form.location_address.placeholder')"
                        />
                    </x-field>
                    <x-field
                        :description="__('agenda/form.price.description')"
                        :label="__('agenda/form.price.label')"
                        model="form.price"
                    >
                        <x-input.text inputmode="decimal" min="0" model="form.price" required step="0.01" type="number">
                            <span class="input__unit">€</span>
                        </x-input.text>
                    </x-field>
                    <div class="flex flex-align-center flex-gap-m">
                        <x-btn primary submit>@lang('agenda/create.submit')</x-btn>
                        <x-btn text href="{{ route('agenda') }}">@lang('ui.cancel')</x-btn>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
