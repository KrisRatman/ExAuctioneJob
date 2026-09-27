<?php

namespace Database\Factories;

use App\Enums\DisputeStatus;
use App\Models\Dispute;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Dispute>
 */
class DisputeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory()->disputed(),
            'opened_by' => fn (array $attributes) => Order::query()->findOrFail($attributes['order_id'])->customer_id,
            'reason' => fake()->realText(200),
            'status' => DisputeStatus::Open,
        ];
    }
}
