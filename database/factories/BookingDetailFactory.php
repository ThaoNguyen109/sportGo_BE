<?php

namespace Database\Factories;

use App\Models\BookingDetail;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BookingDetail>
 */
class BookingDetailFactory extends Factory
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
            'booking_id' => \App\Models\Booking::factory(),
            'field_id' => \App\Models\Field::factory(),
            'booking_date' => fake()->dateTimeBetween('+1 day', '+30 days')->format('Y-m-d'),
            'start_time' => sprintf('%02d:00:00', $startHour),
            'end_time' => sprintf('%02d:00:00', $startHour + 1),
            'price' => fake()->randomFloat(2, 50, 300),
        ];
    }
}
