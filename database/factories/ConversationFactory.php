<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Conversation>
 */
class ConversationFactory extends Factory
{
    /**
     * Диалог по заказу в работе: участники берутся из заказа.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory()->inProgress(),
            'customer_id' => fn (array $attributes) => Order::query()->findOrFail($attributes['order_id'])->customer_id,
            'executor_id' => fn (array $attributes) => Order::query()->findOrFail($attributes['order_id'])->executor_id,
            'last_message_at' => null,
        ];
    }

    public function between(User $customer, User $executor): static
    {
        return $this->state(fn () => [
            'order_id' => Order::factory()->for($customer, 'customer')->inProgress($executor),
            'customer_id' => $customer->id,
            'executor_id' => $executor->id,
        ]);
    }
}
