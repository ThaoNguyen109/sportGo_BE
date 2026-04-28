<?php

namespace Database\Factories;

use App\Models\Court;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Court>
 */
class CourtFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'owner_id' => \App\Models\User::factory(),
            'name' => fake()->company(),
            'address' => fake()->address(),
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'phone' => fake()->phoneNumber(),
            'image' => fake()->imageUrl(300, 300, 'sports'),
            'description' => fake()->text(200),
            'open_time' => '06:00:00',
            'close_time' => '23:00:00',
            'status' => fake()->randomElement(['pending', 'approved', 'rejected']),
            'is_active' => true,
        ];
    }
}
