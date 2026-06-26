<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create a deterministic test user
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Create additional random users
        User::factory(5)->create();

        // Call other seeders (if present)
        $this->call([
            CourtSeeder::class,
            CourtImageSeeder::class,
            FieldSeeder::class,
            FieldPriceSeeder::class,
            BookingSeeder::class,
            BookingDetailSeeder::class,
            NotificationSeeder::class,
        ]);
    }
}
