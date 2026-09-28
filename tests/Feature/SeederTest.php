<?php

use App\Models\Category;
use App\Models\City;
use App\Models\Order;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

it('seeds the demo exchange and can be run twice', function () {
    $this->seed(DatabaseSeeder::class);
    $counts = [City::query()->count(), Category::query()->count(), User::query()->count(), Order::query()->count()];

    $this->seed(DatabaseSeeder::class);

    expect([City::query()->count(), Category::query()->count(), User::query()->count(), Order::query()->count()])->toBe($counts)
        ->and(Category::query()->roots()->count())->toBe(9)
        ->and(City::query()->where('name', 'Екатеринбург')->exists())->toBeTrue()
        ->and(User::query()->where('email', 'executor@example.com')->sole()->isExecutor())->toBeTrue();
});
