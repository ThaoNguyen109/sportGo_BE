<?php

namespace Database\Factories;

use App\Models\CourtImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CourtImage>
 */
class CourtImageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'court_id' => \App\Models\Court::factory(),
            'image_url' => fake()->imageUrl(400, 300, 'sports'),
        ];
    }
}
