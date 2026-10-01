<?php

namespace Database\Factories;

use App\Enums\HeadlineVerdict;
use App\Models\Headline;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Headline>
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
        $articleId = fake()->unique()->numberBetween(1000000, 9999999);

        return [
            'guid' => "https://www.nieuwsplein33.nl/nieuws/{$articleId}/-",
            'article_id' => $articleId,
            'title' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'link' => "https://www.nieuwsplein33.nl/nieuws/{$articleId}/".fake()->slug(),
            'image_url' => fake()->imageUrl(),
            'pub_date' => fake()->dateTimeBetween('-1 week'),
        ];
    }

    public function verdict(HeadlineVerdict $verdict): static
    {
        return $this->state(fn () => ['verdict' => $verdict, 'verdict_reason' => fake()->sentence()]);
    }
}
