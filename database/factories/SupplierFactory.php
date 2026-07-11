<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Supplier>
 */
class SupplierFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->unique()->phoneNumber(),
            'address' => fake()->address(),
            'rfc' => fake()->regexify('[A-Z]{4}[0-9]{6}[A-Z0-9]{3}'),
            'curp' => fake()->regexify('[A-Z]{4}[0-9]{6}[HM][A-Z]{5}[0-9]{2}'),
            'shopname' => fake()->company(),
        ];
    }
}
