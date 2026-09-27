<?php

namespace Database\Factories;

use App\Enums\BidStatus;
use App\Models\Bid;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Bid>
 */
class BidFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'executor_id' => User::factory()->executor(),
            'offer_price' => fake()->numberBetween(10, 300) * 100,
            'approach_description' => fake()->realText(250),
            'duration_days' => fake()->numberBetween(1, 30),
            'status' => BidStatus::Pending,
        ];
    }
}
