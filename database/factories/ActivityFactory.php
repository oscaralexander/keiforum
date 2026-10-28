<?php

namespace Database\Factories;

use App\Enums\ActivityCategory;
use App\Models\Activity;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Activity>
 */
class ActivityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsOn = fake()->dateTimeBetween('+1 day', '+6 days');

        return [
            'user_id' => User::factory(),
            'category' => fake()->randomElement(ActivityCategory::cases()),
            'title' => fake()->sentence(4),
            'description' => '<p>'.fake()->paragraph().'</p>',
            'starts_on' => $startsOn,
            'ends_on' => $startsOn,
            'starts_at' => '20:00',
            'ends_at' => '23:00',
            'location_name' => fake()->company(),
            'location_address' => fake()->streetAddress().', Amersfoort',
            'price' => 0,
            'published_at' => now(),
        ];
    }

    public function unpublished(): static
    {
        return $this->state(fn (array $attributes) => [
            'published_at' => null,
        ]);
    }

    public function past(): static
    {
        return $this->state(fn (array $attributes) => [
            'starts_on' => now()->subWeek(),
            'ends_on' => now()->subWeek(),
        ]);
    }
}
