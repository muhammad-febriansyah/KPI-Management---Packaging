<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * Every foreign key stays null because each one is enforced by a composite
     * foreign key on (client_id, id); a caller that sets one must pass a record
     * belonging to the same client.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'sku' => fake()->unique()->bothify('SKU-####'),
            'name' => fake()->words(2, true),
            'unit_id' => null,
            'group_id' => null,
            'cost_center_id' => null,
            'po_price' => 0,
            'employee_rate' => 0,
            'estimated_output_per_hour' => null,
            'status' => 'active',
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'inactive',
        ]);
    }
}
