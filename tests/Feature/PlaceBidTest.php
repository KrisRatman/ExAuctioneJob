<?php

use App\Actions\PlaceBid;
use App\Enums\BidStatus;
use App\Exceptions\AuctionException;
use App\Livewire\Executor\Feed;
use App\Models\Bid;
use App\Models\City;
use App\Models\Order;
use App\Models\User;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

beforeEach(function () {
    $this->tag = tag('Laravel');
    $this->executor = User::factory()->executor($this->tag)->create();
    $this->order = Order::factory()->withTags($this->tag)->create(['starting_price' => 15000]);
});

function bidForm(Order $order, int $price, int $days = 5): Testable
{
    return Livewire::actingAs(test()->executor)
        ->test(Feed::class)
        ->call('open', $order->id)
        ->set('offerPrice', $price)
        ->set('durationDays', $days)
        ->set('approach', 'Сделаю по шагам: разберу задачу, покажу прототип, потом сдам с инструкцией.');
}

it('places a bid from the order modal', function () {
    bidForm($this->order, 14000, 7)
        ->call('submitBid')
        ->assertHasNoErrors()
        ->assertSet('openOrderId', null)
        ->assertDispatched('toast', message: 'Предложение отправлено заказчику.');

    $bid = Bid::query()->sole();

    expect($bid->executor_id)->toBe($this->executor->id)
        ->and($bid->offer_price)->toBe(14000)
        ->and($bid->duration_days)->toBe(7)
        ->and($bid->status)->toBe(BidStatus::Pending);
});

it('allows a price exactly at the starting price plus 1000', function () {
    bidForm($this->order, 16000)->call('submitBid')->assertHasNoErrors();

    expect(Bid::query()->sole()->offer_price)->toBe(16000);
});

it('rejects a price above the starting price plus 1000', function () {
    bidForm($this->order, 16001)
        ->call('submitBid')
        ->assertHasErrors(['offerPrice' => 'max']);

    expect(Bid::query()->count())->toBe(0);
});

it('enforces the price cap in the action itself, not only in the form', function () {
    app(PlaceBid::class)->handle($this->executor, $this->order, [
        'offer_price' => 16001,
        'approach_description' => 'Подход',
        'duration_days' => 3,
    ]);
})->throws(AuctionException::class, 'Цена не может быть выше');

it('validates duration and approach', function () {
    bidForm($this->order, 14000, 0)
        ->set('approach', 'Сделаю')
        ->call('submitBid')
        ->assertHasErrors(['durationDays', 'approach']);
});

it('edits the existing bid instead of creating a second one', function () {
    $bid = Bid::factory()->for($this->order)->for($this->executor, 'executor')->create(['offer_price' => 15000, 'duration_days' => 10]);

    Livewire::actingAs($this->executor)
        ->test(Feed::class)
        ->call('open', $this->order->id)
        ->assertSet('offerPrice', 15000)
        ->assertSet('durationDays', 10)
        ->set('offerPrice', 13000)
        ->call('submitBid')
        ->assertHasNoErrors()
        ->assertDispatched('toast', message: 'Предложение обновлено.');

    expect(Bid::query()->count())->toBe(1)
        ->and($bid->fresh()->offer_price)->toBe(13000);
});

it('does not accept bids on orders that are no longer open', function () {
    $this->order->update(['status' => 'in_progress']);

    app(PlaceBid::class)->handle($this->executor, $this->order, [
        'offer_price' => 14000,
        'approach_description' => 'Подход',
        'duration_days' => 3,
    ]);
})->throws(AuctionException::class, 'Заказ уже не принимает предложения.');

it('does not accept bids from executors without a matching tag', function () {
    $stranger = User::factory()->executor(tag('Логотипы'))->create();

    app(PlaceBid::class)->handle($stranger, $this->order, [
        'offer_price' => 14000,
        'approach_description' => 'Подход',
        'duration_days' => 3,
    ]);
})->throws(AuctionException::class, 'не подходит под город или теги');

it('does not accept bids from executors in another city', function () {
    $stranger = User::factory()->executor($this->tag)->create();
    $stranger->executorProfile->update(['city_id' => City::factory()->create()->id]);

    app(PlaceBid::class)->handle($stranger, $this->order, [
        'offer_price' => 14000,
        'approach_description' => 'Подход',
        'duration_days' => 3,
    ]);
})->throws(AuctionException::class, 'не подходит под город или теги');

it('does not accept bids from customers', function () {
    $customer = User::factory()->customer()->create();
    $customer->categories()->attach($this->tag);

    app(PlaceBid::class)->handle($customer, $this->order, [
        'offer_price' => 14000,
        'approach_description' => 'Подход',
        'duration_days' => 3,
    ]);
})->throws(AuctionException::class);

it('lists the executor bids with their statuses', function () {
    Bid::factory()->for($this->order)->for($this->executor, 'executor')->create();

    $this->actingAs($this->executor)
        ->get('/my/bids')
        ->assertOk()
        ->assertSee($this->order->title)
        ->assertSee('Ждёт решения');
});
