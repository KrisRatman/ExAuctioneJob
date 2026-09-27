<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\ExecutorProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => null,
            'role' => UserRole::Customer,
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function customer(): static
    {
        return $this->state(['role' => UserRole::Customer]);
    }

    public function admin(): static
    {
        return $this->state(['role' => UserRole::Admin]);
    }

    /**
     * Исполнитель с анкетой и тегами.
     *
     * @param  iterable<Category>|Category|null  $tags  без тегов — один случайный новый тег
     */
    public function executor(iterable|Category|null $tags = null): static
    {
        return $this->state(['role' => UserRole::Executor])
            ->has(ExecutorProfile::factory(), 'executorProfile')
            ->afterCreating(function (User $user) use ($tags) {
                $tags ??= Category::factory()->tag()->create();
                $user->categories()->attach(collect($tags instanceof Category ? [$tags] : $tags)->pluck('id'));
            });
    }
}
