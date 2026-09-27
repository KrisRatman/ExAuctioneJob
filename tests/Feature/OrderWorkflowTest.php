<?php

use App\Actions\ConfirmCompletion;
use App\Actions\DeliverOrder;
use App\Actions\OpenDispute;
use App\Enums\DisputeStatus;
use App\Enums\OrderStatus;
use App\Exceptions\AuctionException;
use App\Livewire\OrderWorkflow;
use App\Models\Conversation;
use App\Models\Order;
use App\Models\User;
use App\Notifications\DisputeOpened;
use App\Notifications\OrderCompleted;
use App\Notifications\OrderDelivered;
use Illuminate\Support\Facades\Notification;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

beforeEach(function () {
    $this->customer = User::factory()->customer()->create();
    $this->executor = User::factory()->executor()->create();
    $this->conversation = Conversation::factory()->between($this->customer, $this->executor)->create();
    $this->order = $this->conversation->order;
    $this->returnUrl = route('chats.show', $this->conversation);
});

function workflow(User $user, Order $order): Testable
{
    return Livewire::actingAs($user)->test(OrderWorkflow::class, ['order' => $order->fresh(), 'returnUrl' => test()->returnUrl]);
}

it('lets the executor deliver the work and notifies the customer', function () {
    Notification::fake();

    workflow($this->executor, $this->order)
        ->assertSee('Сдать работу')
        ->call('deliver')
        ->assertRedirect($this->returnUrl);

    expect($this->order->fresh()->status)->toBe(OrderStatus::Delivered)
        ->and($this->order->fresh()->delivered_at)->not->toBeNull();

    Notification::assertSentTo($this->customer, OrderDelivered::class);
});

it('shows the customer no deliver button', function () {
    workflow($this->customer, $this->order)
        ->assertSee('Исполнитель работает над заказом')
        ->assertDontSee('Сдать работу');
});

it('does not let the customer or a stranger deliver', function (Closure $who) {
    app(DeliverOrder::class)->handle($who($this), $this->order);
})->throws(AuctionException::class)->with([
    'customer' => [fn ($test) => $test->customer],
    'another executor' => [fn () => User::factory()->executor()->create()],
]);

it('lets the customer confirm the delivered work', function () {
    Notification::fake();
    app(DeliverOrder::class)->handle($this->executor, $this->order);

    workflow($this->customer, $this->order)
        ->assertSee('Подтвердить выполнение')
        ->call('confirm')
        ->assertRedirect($this->returnUrl);

    $order = $this->order->fresh();

    expect($order->status)->toBe(OrderStatus::Completed)
        ->and($order->completed_at)->not->toBeNull()
        ->and($this->executor->executorProfile->fresh()->completed_orders_count)->toBe(1);

    Notification::assertSentTo($this->executor, OrderCompleted::class);
});

it('cannot confirm work that was not delivered', function () {
    app(ConfirmCompletion::class)->handle($this->customer, $this->order);
})->throws(AuctionException::class, 'только сданную работу');

it('opens a dispute with a reason instead of confirming', function () {
    Notification::fake();
    app(DeliverOrder::class)->handle($this->executor, $this->order);

    workflow($this->customer, $this->order)
        ->set('showDisputeForm', true)
        ->assertSee('Что не так с результатом')
        ->set('disputeReason', 'Нет админки, которая была в задаче, и не хватает двух QR-кодов.')
        ->call('openDispute')
        ->assertHasNoErrors()
        ->assertRedirect($this->returnUrl);

    $dispute = $this->order->disputes()->sole();

    expect($this->order->fresh()->status)->toBe(OrderStatus::Disputed)
        ->and($dispute->status)->toBe(DisputeStatus::Open)
        ->and($dispute->opened_by)->toBe($this->customer->id);

    Notification::assertSentTo($this->executor, DisputeOpened::class);
});

it('requires a meaningful dispute reason', function () {
    app(DeliverOrder::class)->handle($this->executor, $this->order);

    workflow($this->customer, $this->order)
        ->set('disputeReason', 'Плохо')
        ->call('openDispute')
        ->assertHasErrors(['disputeReason' => 'min']);

    expect($this->order->fresh()->status)->toBe(OrderStatus::Delivered);
});

it('cannot open a dispute before the work is delivered', function () {
    app(OpenDispute::class)->handle($this->customer, $this->order, 'Причина спора достаточно длинная.');
})->throws(AuctionException::class, 'только по сданной работе');

it('shows the dispute state to both sides', function () {
    app(DeliverOrder::class)->handle($this->executor, $this->order);
    app(OpenDispute::class)->handle($this->customer, $this->order, 'Нет админки из задачи.');

    workflow($this->executor, $this->order)->assertSee('Спор на рассмотрении')->assertSee('Нет админки из задачи.');
    workflow($this->customer, $this->order)->assertSee('Спор на рассмотрении');
});

it('reports rule violations as a toast instead of an error page', function () {
    $component = workflow($this->customer, $this->order);
    // Исполнитель сдал работу, пока у заказчика открыта старая страница, — а потом заказ уже подтверждён.
    app(DeliverOrder::class)->handle($this->executor, $this->order);
    app(ConfirmCompletion::class)->handle($this->customer, $this->order);

    $component->call('confirm')->assertDispatched('toast', type: 'error')->assertNoRedirect();
});

it('refreshes the panel when the other side changes the order', function () {
    $component = workflow($this->customer, $this->order)->assertSee('Исполнитель работает над заказом');

    app(DeliverOrder::class)->handle($this->executor, $this->order);

    $component->call('onNotification', ['order_id' => $this->order->id])
        ->assertSee('Исполнитель сдал работу');
});

it('hides the panel from people outside the order', function () {
    Livewire::actingAs(User::factory()->customer()->create())
        ->test(OrderWorkflow::class, ['order' => $this->order, 'returnUrl' => '/'])
        ->assertNotFound();
});

it('shows the panel in the chat and on the customer order page', function () {
    $this->actingAs($this->executor)->get(route('chats.show', $this->conversation))->assertSee('Сдать работу');
    $this->actingAs($this->customer)->get(route('customer.orders.show', $this->order))->assertSee('Исполнитель работает над заказом');
});
