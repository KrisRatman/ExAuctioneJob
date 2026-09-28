<?php

use App\Enums\OrderStatus;
use App\Livewire\Customer\CreateOrder;
use App\Models\Bid;
use App\Models\Category;
use App\Models\City;
use App\Models\Order;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->customer = User::factory()->customer()->create();
    $this->city = City::factory()->create(['name' => 'Екатеринбург']);
});

it('publishes an order with a city, tags and a starting price', function () {
    $tiles = tag('Укладка плитки');
    $plumbing = tag('Установка сантехники');

    Livewire::actingAs($this->customer)
        ->test(CreateOrder::class)
        ->set('title', 'Уложить плитку в ванной')
        ->set('description', 'Стены и пол в ванной, плитка уже куплена, нужна ещё установка ревизионного люка.')
        ->set('startingPrice', 40000)
        ->set('cityId', $this->city->id)
        ->set('categoryIds', [$tiles->id, $plumbing->id])
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('customer.orders.show', Order::query()->firstOrFail()));

    $order = Order::query()->with('categories')->firstOrFail();

    expect($order->customer_id)->toBe($this->customer->id)
        ->and($order->status)->toBe(OrderStatus::Open)
        ->and($order->city_id)->toBe($this->city->id)
        ->and($order->starting_price)->toBe(40000)
        ->and($order->categories->modelKeys())->toEqualCanonicalizing([$tiles->id, $plumbing->id]);
});

it('suggests the city of the previous order', function () {
    $other = City::factory()->create();
    Order::factory()->for($this->customer, 'customer')->create(['city_id' => $this->city->id]);
    Order::factory()->for($this->customer, 'customer')->create(['city_id' => $other->id]);

    Livewire::actingAs($this->customer)
        ->test(CreateOrder::class)
        ->assertSet('cityId', $other->id);
});

it('validates the order form', function (array $overrides, string $errorField) {
    $tags = Category::factory()->tag()->count(4)->create();

    Livewire::actingAs($this->customer)
        ->test(CreateOrder::class)
        ->set([
            'title' => 'Уложить плитку в ванной',
            'description' => 'Стены и пол в ванной, плитка уже куплена, нужна ещё установка ревизионного люка.',
            'startingPrice' => 40000,
            'cityId' => $this->city->id,
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
    'no city' => [['cityId' => null], 'cityId'],
    'unknown city' => [['cityId' => 999_999], 'cityId'],
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
