<?php

namespace Database\Factories;

use App\Models\FieldPrice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FieldPrice>
 */
class FieldPriceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startHour = fake()->randomElement([6, 7, 8, 9, 10, 14, 15, 16, 17, 18]);
        return [
            'field_id' => \App\Models\Field::factory(),
            'start_time' => sprintf('%02d:00:00', $startHour),
            'end_time' => sprintf('%02d:00:00', $startHour + 1),
            'price' => fake()->randomFloat(2, 50, 300),
        ];
    }
}
