<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'user_name' => fake()->name(),
            'destination' => fake()->city(),
            'departure_date' => fake()->dateTimeBetween('now', '+2 weeks'),
            'return_date' => fake()->dateTimeBetween('+2 weeks', '+1 month'),
            'status' => 'requested',
        ];
    }
}
