<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Group;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Group>
 */
class GroupFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'code' => fake()->unique()->bothify('GRP-##??'),
            'name' => fake()->words(2, true),
            'status' => 'active',
        ];
    }
}
