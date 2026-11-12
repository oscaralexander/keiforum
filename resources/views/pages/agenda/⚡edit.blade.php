<?php

use App\Livewire\Forms\ActivityForm;
use App\Models\Activity;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public Activity $activity;

    public ActivityForm $form;

    public $poster;

    /**
     * Whether the current poster should be deleted when the changes are saved.
     */
    public bool $isPosterRemoved = false;

    public function mount(User $user, string $date, string $slug): void
    {
        $activity = $user->activities()
            ->whereDate('starts_on', $date)
            ->where('slug', $slug)
            ->firstOrFail();

        Gate::authorize('update', $activity);

        $this->activity = $activity->setRelation('user', $user);
        $this->form->fillFromActivity($activity);
    }

    public function render()
    {
        return $this->view()
            ->title(__('agenda/edit.title'));
    }

    public function removePoster(): void
    {
        $this->poster = null;
        $this->isPosterRemoved = true;
        $this->resetValidation('poster');
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
        Gate::authorize('update', $this->activity);

        $this->form->validateActivity();
        $this->validate();

        $activity = $this->form->update(
            $this->activity,
            $this->poster ? $this->form->storePoster($this->poster) : null,
            $this->isPosterRemoved,
        );

        $this->redirect($activity->url());
    }

    public function delete()
    {
        Gate::authorize('delete', $this->activity);

        if ($this->activity->poster_path) {
            Storage::disk('public')->delete($this->activity->poster_path);
        }

        $this->activity->delete();

        $this->redirect(route('agenda'));
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
        :path="[
            ['label' => __('agenda/index.title'), 'href' => route('agenda')],
            ['label' => $activity->title, 'href' => $activity->url()],
        ]"
        :title="__('agenda/edit.title')"
    />
    <div class="wrapper">
        <div class="panel panel--padded">
            <x-activity-form
                :cancel-href="$activity->url()"
                :poster-url="match (true) {
                    $poster && ! $errors->has('poster') => $poster->temporaryUrl(),
                    $activity->poster_path && ! $isPosterRemoved => route('img', ['src' => $activity->poster_path, 'w' => 620, 'h' => 877, 'q' => 85]),
                    default => null,
                }"
                :submit-label="__('ui.save')"
            >
                @can('delete', $activity)
                    <x-slot:secondary-actions>
                        <x-btn
                            danger
                            icon="trash"
                            wire:click="delete"
                            wire:confirm="{{ __('agenda/edit.confirm_delete') }}"
                        >@lang('ui.delete')</x-btn>
                    </x-slot:secondary-actions>
                @endcan
            </x-activity-form>
        </div>
    </div>
</div>
