<?php

use App\Actions\AcceptBid;
use App\Enums\BidStatus;
use App\Enums\OrderStatus;
use App\Exceptions\AuctionException;
use App\Livewire\Customer\OrderShow;
use App\Models\Bid;
use App\Models\Order;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->customer = User::factory()->customer()->create();
    $this->order = Order::factory()->for($this->customer, 'customer')->withTags(tag())->create();
    [$this->chosen, $this->other, $this->third] = Bid::factory()->count(3)->for($this->order)->create()->all();
});

it('shows the order and its bids with executor rating', function () {
    $this->chosen->executor->executorProfile->update(['rating_avg' => 4.8, 'reviews_count' => 5, 'completed_orders_count' => 7]);

    $this->actingAs($this->customer)
        ->get(route('customer.orders.show', $this->order))
        ->assertOk()
        ->assertSee($this->order->title)
        ->assertSee($this->chosen->executor->name)
        ->assertSee('4,8')
        ->assertSee('7 заказов выполнено');
});

it('expands the executor card with the approach and profile', function () {
    Livewire::actingAs($this->customer)
        ->test(OrderShow::class, ['order' => $this->order])
        ->assertDontSee('Принять исполнителя')
        ->call('selectBid', $this->chosen->id)
        ->assertSee($this->chosen->approach_description)
        ->assertSee($this->chosen->executor->executorProfile->description)
        ->assertSee('Принять исполнителя');
});

it('accepts an executor and rejects the other bids', function () {
    Livewire::actingAs($this->customer)
        ->test(OrderShow::class, ['order' => $this->order])
        ->call('accept', $this->chosen->id)
        ->assertRedirect(route('customer.orders.show', $this->order));

    $order = $this->order->fresh();

    expect($order->status)->toBe(OrderStatus::InProgress)
        ->and($order->executor_id)->toBe($this->chosen->executor_id)
        ->and($order->accepted_at)->not->toBeNull()
        ->and($this->chosen->fresh()->status)->toBe(BidStatus::Accepted)
        ->and($this->other->fresh()->status)->toBe(BidStatus::Rejected)
        ->and($this->third->fresh()->status)->toBe(BidStatus::Rejected);
});

it('does not accept a second executor after one was chosen', function () {
    app(AcceptBid::class)->handle($this->customer, $this->chosen);

    Livewire::actingAs($this->customer)
        ->test(OrderShow::class, ['order' => $this->order->fresh()])
        ->call('accept', $this->other->id)
        ->assertDispatched('toast', type: 'error');

    expect($this->order->fresh()->executor_id)->toBe($this->chosen->executor_id)
        ->and($this->other->fresh()->status)->toBe(BidStatus::Rejected);
});

it('does not let another customer accept a bid', function () {
    $stranger = User::factory()->customer()->create();

    app(AcceptBid::class)->handle($stranger, $this->chosen);
})->throws(AuctionException::class, 'только автор заказа');

it('hides another customer order', function () {
    $stranger = User::factory()->customer()->create();

    $this->actingAs($stranger)->get(route('customer.orders.show', $this->order))->assertNotFound();
});

it('cannot accept a bid that belongs to another order', function () {
    $otherOrder = Order::factory()->for($this->customer, 'customer')->create();
    $foreignBid = Bid::factory()->for($otherOrder)->create();

    Livewire::actingAs($this->customer)
        ->test(OrderShow::class, ['order' => $this->order])
        ->call('accept', $foreignBid->id)
        ->assertNotFound();

    expect($otherOrder->fresh()->status)->toBe(OrderStatus::Open);
});

it('cancels an open order and rejects pending bids', function () {
    Livewire::actingAs($this->customer)
        ->test(OrderShow::class, ['order' => $this->order])
        ->call('cancel')
        ->assertRedirect(route('customer.orders.show', $this->order));

    expect($this->order->fresh()->status)->toBe(OrderStatus::Cancelled)
        ->and($this->order->fresh()->cancelled_at)->not->toBeNull()
        ->and($this->order->bids()->where('status', BidStatus::Pending)->count())->toBe(0);
});

it('cannot cancel an order that is already in progress', function () {
    app(AcceptBid::class)->handle($this->customer, $this->chosen);

    Livewire::actingAs($this->customer)
        ->test(OrderShow::class, ['order' => $this->order->fresh()])
        ->call('cancel')
        ->assertDispatched('toast', type: 'error');

    expect($this->order->fresh()->status)->toBe(OrderStatus::InProgress);
});

it('removes the order from the feed of other executors once accepted', function () {
    app(AcceptBid::class)->handle($this->customer, $this->chosen);

    $this->actingAs($this->other->executor)
        ->get('/feed')
        ->assertOk()
        ->assertDontSee($this->order->title);
});
