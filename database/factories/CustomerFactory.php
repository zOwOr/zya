<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tit_name' => fake()->name(),
            'tit_email' => fake()->unique()->safeEmail(),
            'tit_phone' => fake()->unique()->phoneNumber(),
            'tit_address' => fake()->address(),
            'tit_city' => fake()->city(),
            'tit_status' => 'active',
        ];
    }
}
