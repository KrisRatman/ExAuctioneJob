<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Message>
 */
class MessageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'conversation_id' => Conversation::factory(),
            'sender_id' => fn (array $attributes) => Conversation::query()->findOrFail($attributes['conversation_id'])->customer_id,
            'body' => fake()->realText(120),
            'read_at' => null,
        ];
    }

    public function read(): static
    {
        return $this->state(['read_at' => now()]);
    }
}
