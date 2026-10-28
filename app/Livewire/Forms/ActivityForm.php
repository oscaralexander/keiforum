<?php

namespace App\Livewire\Forms;

use App\Enums\ActivityCategory;
use App\Lib\Image;
use App\Models\Activity;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Form;

class ActivityForm extends Form
{
    public string $title = '';

    public string $description = '';

    public string $category = '';

    public string $starts_on = '';

    public string $ends_on = '';

    public string $starts_at = '';

    public string $ends_at = '';

    public string $location_name = '';

    public string $location_address = '';

    public string $location_place_id = '';

    public ?float $location_latitude = null;

    public ?float $location_longitude = null;

    /**
     * The entrance price in euros, as typed by the user.
     */
    public string $price = '0';

    public string $website_url = '';

    /**
     * The start date of the activity being edited, which may stay in the
     * past while the activity is running.
     */
    #[Locked]
    public ?string $originalStartsOn = null;

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            'description' => ['required', 'string', 'max:20000'],
            'category' => ['required', Rule::enum(ActivityCategory::class)],
            'starts_on' => array_filter([
                'required',
                'date_format:Y-m-d',
                $this->starts_on !== $this->originalStartsOn ? 'after_or_equal:today' : null,
            ]),
            'ends_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:starts_on'],
            'starts_at' => ['required', 'date_format:H:i'],
            'ends_at' => ['nullable', 'date_format:H:i'],
            'location_name' => ['required', 'string', 'max:255'],
            'location_address' => ['nullable', 'string', 'max:255'],
            'location_place_id' => ['nullable', 'string', 'max:255'],
            'location_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'location_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'price' => ['required', 'numeric', 'min:0', 'max:10000'],
            'website_url' => ['nullable', 'url:http,https', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'ends_on.after_or_equal' => __('agenda/form.ends_on.after_or_equal'),
            'starts_on.after_or_equal' => __('agenda/form.starts_on.after_or_equal'),
        ];
    }

    public function fillFromActivity(Activity $activity): void
    {
        $this->title = $activity->title;
        $this->description = $activity->description;
        $this->category = $activity->category->value;
        $this->starts_on = $activity->starts_on->format('Y-m-d');
        $this->ends_on = $activity->ends_on->format('Y-m-d');
        $this->starts_at = $activity->starts_at_time;
        $this->ends_at = $activity->ends_at_time ?? '';
        $this->location_name = $activity->location_name;
        $this->location_address = $activity->location_address ?? '';
        $this->location_place_id = $activity->location_place_id ?? '';
        $this->location_latitude = $activity->location_latitude;
        $this->location_longitude = $activity->location_longitude;
        $this->price = (string) ($activity->price / 100);
        $this->website_url = $activity->website_url ?? '';
        $this->originalStartsOn = $this->starts_on;
    }

    /**
     * Keep the end date on the start date unless the user chose a later one.
     */
    public function syncEndDate(string $previousStartsOn): void
    {
        if ($this->ends_on === '' || $this->ends_on === $previousStartsOn || $this->ends_on < $this->starts_on) {
            $this->ends_on = $this->starts_on;
        }
    }

    public function priceInCents(): int
    {
        return (int) round((float) str_replace(',', '.', $this->price) * 100);
    }

    /**
     * Validate, including that a same-day activity ends after it starts.
     */
    public function validateActivity(): void
    {
        $this->price = str_replace(',', '.', trim($this->price));

        $this->validate();

        if ($this->ends_at !== '' && $this->starts_on === $this->ends_on && $this->ends_at <= $this->starts_at) {
            throw ValidationException::withMessages([
                'form.ends_at' => __('agenda/form.ends_at.after'),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function attributes(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'category' => ActivityCategory::from($this->category),
            'starts_on' => $this->starts_on,
            'ends_on' => $this->ends_on,
            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at ?: null,
            'location_name' => $this->location_name,
            'location_address' => $this->location_address ?: null,
            'location_place_id' => $this->location_place_id ?: null,
            'location_latitude' => $this->location_latitude,
            'location_longitude' => $this->location_longitude,
            'price' => $this->priceInCents(),
            'website_url' => $this->website_url ?: null,
        ];
    }

    /**
     * Resize the uploaded poster to A4 proportions and store it as WebP.
     */
    public function storePoster(TemporaryUploadedFile $poster): string
    {
        $posterPath = Activity::POSTER_DIRECTORY.'/'.Str::random(40).'.webp';
        $posterContents = (new Image)
            ->read($poster->getRealPath())
            ->resize(Activity::POSTER_WIDTH, Activity::POSTER_HEIGHT)
            ->encode(80);

        Storage::disk('public')->put($posterPath, $posterContents);

        return $posterPath;
    }

    public function store(User $user, ?string $posterPath): Activity
    {
        return $user->activities()->create([
            ...$this->attributes(),
            'poster_path' => $posterPath,
            'published_at' => now(),
        ]);
    }

    /**
     * Save the changes, giving the activity a new slug when its title or
     * start date changed and replacing the poster when a new one is given.
     */
    public function update(Activity $activity, ?string $posterPath): Activity
    {
        $activity->fill($this->attributes());

        if ($activity->isDirty(['title', 'starts_on'])) {
            $activity->slug = $activity->uniqueSlug();
        }

        $previousPosterPath = $activity->poster_path;

        if ($posterPath) {
            $activity->poster_path = $posterPath;
        }

        $activity->save();

        if ($posterPath && $previousPosterPath) {
            Storage::disk('public')->delete($previousPosterPath);
        }

        return $activity;
    }
}
