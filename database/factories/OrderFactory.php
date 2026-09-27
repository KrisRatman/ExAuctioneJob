<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => User::factory()->customer(),
            'executor_id' => null,
            'title' => rtrim(fake()->sentence(5), '.'),
            'description' => fake()->realText(400),
            'starting_price' => fake()->numberBetween(10, 300) * 100,
            'status' => OrderStatus::Open,
        ];
    }

    /**
     * Теги заказа.
     *
     * @param  iterable<Category>|Category  $tags
     */
    public function withTags(iterable|Category $tags): static
    {
        return $this->afterCreating(fn (Order $order) => $order->categories()->attach(
            collect($tags instanceof Category ? [$tags] : $tags)->pluck('id'),
        ));
    }

    public function inProgress(?User $executor = null): static
    {
        return $this->state(fn () => [
            'status' => OrderStatus::InProgress,
            'executor_id' => $executor?->id ?? User::factory()->executor(),
            'accepted_at' => now(),
        ]);
    }

    /** Исполнитель сдал работу, заказчик ещё не проверил. */
    public function delivered(?User $executor = null): static
    {
        return $this->inProgress($executor)->state(['status' => OrderStatus::Delivered, 'delivered_at' => now()]);
    }

    public function completed(?User $executor = null): static
    {
        return $this->delivered($executor)->state(['status' => OrderStatus::Completed, 'completed_at' => now()]);
    }

    public function disputed(?User $executor = null): static
    {
        return $this->delivered($executor)->state(['status' => OrderStatus::Disputed]);
    }

    public function cancelled(): static
    {
        return $this->state(['status' => OrderStatus::Cancelled, 'cancelled_at' => now()]);
    }
}
