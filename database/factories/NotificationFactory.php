<?php

namespace Database\Factories;

use App\Models\Notification;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Notification>
 */
class NotificationFactory extends Factory
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
            'title' => fake()->sentence(3),
            'content' => fake()->text(150),
            'type' => fake()->randomElement(['booking', 'payment', 'system', 'promotion']),
            'is_read' => fake()->boolean(30),
            'created_at' => now(),
        ];
    }
}
