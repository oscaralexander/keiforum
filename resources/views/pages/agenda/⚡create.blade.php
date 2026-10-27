<?php

use App\Livewire\Forms\ActivityForm;
use App\Models\Activity;
use Illuminate\Support\Facades\Gate;
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
            'poster' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ];
    }

    public function submit()
    {
        Gate::authorize('create', Activity::class);

        $this->form->validateActivity();
        $this->validate();

        $activity = $this->form->store(
            auth()->user(),
            $this->poster ? $this->form->storePoster($this->poster) : null,
        );

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
        <x-activity-form
            :cancel-href="route('agenda')"
            :ends-on-min="$form->starts_on"
            :poster-url="$poster && ! $errors->has('poster') ? $poster->temporaryUrl() : null"
            :submit-label="__('agenda/create.submit')"
        />
    </div>
</div>
