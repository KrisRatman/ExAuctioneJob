<?php

use App\Enums\OrderStatus;
use App\Livewire\Customer\CreateOrder;
use App\Models\Bid;
use App\Models\Category;
use App\Models\Order;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->customer = User::factory()->customer()->create();
});

it('publishes an order with tags and a starting price', function () {
    $laravel = tag('Laravel');
    $markup = tag('Вёрстка');

    Livewire::actingAs($this->customer)
        ->test(CreateOrder::class)
        ->set('title', 'Сайт-меню для кофейни')
        ->set('description', 'Нужен простой сайт с меню, ценами и админкой для изменения позиций.')
        ->set('startingPrice', 40000)
        ->set('categoryIds', [$laravel->id, $markup->id])
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('customer.orders.show', Order::query()->firstOrFail()));

    $order = Order::query()->with('categories')->firstOrFail();

    expect($order->customer_id)->toBe($this->customer->id)
        ->and($order->status)->toBe(OrderStatus::Open)
        ->and($order->starting_price)->toBe(40000)
        ->and($order->categories->modelKeys())->toEqualCanonicalizing([$laravel->id, $markup->id]);
});

it('validates the order form', function (array $overrides, string $errorField) {
    $tags = Category::factory()->tag()->count(4)->create();

    Livewire::actingAs($this->customer)
        ->test(CreateOrder::class)
        ->set([
            'title' => 'Сайт-меню для кофейни',
            'description' => 'Нужен простой сайт с меню, ценами и админкой для изменения позиций.',
            'startingPrice' => 40000,
            'categoryIds' => [$tags[0]->id],
            ...array_map(fn ($value) => $value instanceof Closure ? $value($tags) : $value, $overrides),
        ])
        ->call('save')
        ->assertHasErrors($errorField);

    expect(Order::query()->count())->toBe(0);
})->with([
    'no tags' => [['categoryIds' => []], 'categoryIds'],
    'more than three tags' => [['categoryIds' => fn ($tags) => $tags->modelKeys()], 'categoryIds'],
    'a section instead of a tag' => [['categoryIds' => fn ($tags) => [$tags[0]->parent_id]], 'categoryIds.0'],
    'price below minimum' => [['startingPrice' => 50], 'startingPrice'],
    'no price' => [['startingPrice' => null], 'startingPrice'],
    'short description' => [['description' => 'Коротко'], 'description'],
]);

it('lists the customer orders with the number of pending bids', function () {
    $order = Order::factory()->for($this->customer, 'customer')->withTags(tag())->create(['title' => 'Мой заказ']);
    Bid::factory()->count(5)->for($order)->create();
    Order::factory()->create(['title' => 'Чужой заказ']);

    $this->actingAs($this->customer)
        ->get('/my/orders')
        ->assertOk()
        ->assertSee('Мой заказ')
        ->assertSee('5 исполнителей готовы взяться')
        ->assertDontSee('Чужой заказ');
});

it('moves cancelled orders to the archive tab', function () {
    Order::factory()->for($this->customer, 'customer')->cancelled()->create(['title' => 'Отменённый заказ']);

    $this->actingAs($this->customer)->get('/my/orders')->assertDontSee('Отменённый заказ');
    $this->actingAs($this->customer)->get('/my/orders?tab=archive')->assertSee('Отменённый заказ');
});
