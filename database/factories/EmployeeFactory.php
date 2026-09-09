<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
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
            'user_id' => null,
            'employee_no' => fake()->unique()->numerify('#########'),
            'sim_id' => null,
            'full_name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->numerify('08##########'),
            'join_date' => fake()->date(),
            'gender' => fake()->randomElement(['male', 'female']),
            'employee_status' => 'permanent',
            'marital_status' => 'single',
            'group_id' => null,
            'rate_category' => 'baru',
            'status' => 'active',
        ];
    }
}
