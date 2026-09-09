<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Unit>
 */
class UnitFactory extends Factory
{
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'code' => fake()->unique()->bothify('UNIT-##??'),
            'name' => fake()->randomElement(['PCS', 'Karton', 'Kg', 'Box']),
            'status' => 'active',
        ];
    }
}
