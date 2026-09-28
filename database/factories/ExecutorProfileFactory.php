<?php

namespace Database\Factories;

use App\Models\ExecutorProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExecutorProfile>
 */
class ExecutorProfileFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'city_id' => fn () => CityFactory::defaultId(),
            'description' => fake()->realText(300),
            'rating_avg' => null,
            'reviews_count' => 0,
            'completed_orders_count' => 0,
        ];
    }
}
