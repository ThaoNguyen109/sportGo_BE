<?php

namespace Database\Factories;

use App\Models\Field;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Field>
 */
class FieldFactory extends Factory
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
            'name' => fake()->word() . ' ' . fake()->randomNumber(2),
            'is_active' => true,
        ];
    }
}
