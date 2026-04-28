<?php

namespace Database\Factories;

use App\Models\Booking;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'total_price' => fake()->randomFloat(2, 50, 500),
            'payment_method' => fake()->randomElement(['credit_card', 'debit_card', 'bank_transfer', 'wallet']),
            'status' => fake()->randomElement(['pending', 'paid', 'cancelled']),
        ];
    }
}
