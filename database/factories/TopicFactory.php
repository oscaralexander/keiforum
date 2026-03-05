<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Topic>
 */
class TopicFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'forum_id' => \App\Models\Forum::factory(),
            'user_id' => \App\Models\User::factory(),
            'title' => fake()->sentence(),
            'is_locked' => false,
            'is_pinned' => false,
        ];
    }
}
