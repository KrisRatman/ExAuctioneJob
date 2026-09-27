<?php

use App\Models\Category;
use App\Models\Order;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

it('seeds the demo exchange and can be run twice', function () {
    $this->seed(DatabaseSeeder::class);
    $counts = [Category::query()->count(), User::query()->count(), Order::query()->count()];

    $this->seed(DatabaseSeeder::class);

    expect([Category::query()->count(), User::query()->count(), Order::query()->count()])->toBe($counts)
        ->and(Category::query()->roots()->count())->toBe(7)
        ->and(User::query()->where('email', 'executor@example.com')->sole()->isExecutor())->toBeTrue();
});
