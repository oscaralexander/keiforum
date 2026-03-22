<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Headline>
 */
class HeadlineFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'guid' => fake()->unique()->url(),
            'title' => fake()->sentence(),
            'link' => fake()->url(),
            'image_url' => fake()->imageUrl(),
            'pub_date' => fake()->dateTimeBetween('-1 week'),
        ];
    }
}
