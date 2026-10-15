<?php

namespace App\Livewire\Forms;

use App\Enums\ActivityCategory;
use App\Models\Activity;
use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            'description' => ['required', 'string', 'max:20000'],
            'category' => ['required', Rule::enum(ActivityCategory::class)],
            'starts_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'ends_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:starts_on'],
            'starts_at' => ['required', 'date_format:H:i'],
            'ends_at' => ['nullable', 'date_format:H:i'],
            'location_name' => ['required', 'string', 'max:255'],
            'location_address' => ['nullable', 'string', 'max:255'],
            'location_place_id' => ['nullable', 'string', 'max:255'],
            'location_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'location_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'price' => ['required', 'numeric', 'min:0', 'max:10000'],
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
        ];
    }

    public function store(User $user, ?string $posterPath): Activity
    {
        return $user->activities()->create([
            ...$this->attributes(),
            'poster_path' => $posterPath,
            'published_at' => now(),
        ]);
    }
}
