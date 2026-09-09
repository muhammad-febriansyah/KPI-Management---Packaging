<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Shift;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Shift>
 */
class ShiftFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(), 'code' => fake()->unique()->bothify('SHIFT-##'), 'name' => 'Shift '.fake()->randomElement(['Pagi', 'Siang', 'Malam']), 'start_time' => '07:00', 'end_time' => '15:00', 'status' => 'active',
        ];
    }
}
