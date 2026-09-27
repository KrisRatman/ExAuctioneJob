<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Review;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory()->completed(),
            'customer_id' => fn (array $attributes) => Order::query()->findOrFail($attributes['order_id'])->customer_id,
            'executor_id' => fn (array $attributes) => Order::query()->findOrFail($attributes['order_id'])->executor_id,
            'rating' => fake()->numberBetween(3, 5),
            'comment' => fake()->realText(120),
        ];
    }
}
