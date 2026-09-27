<?php

use App\Actions\RecalculateExecutorStats;
use App\Actions\ResolveDispute;
use App\Enums\DisputeResolution;
use App\Enums\DisputeStatus;
use App\Enums\OrderStatus;
use App\Exceptions\AuctionException;
use App\Filament\Resources\Disputes\DisputeResource;
use App\Filament\Resources\Disputes\Pages\ListDisputes;
use App\Filament\Resources\Disputes\Pages\ViewDispute;
use App\Livewire\OrderWorkflow;
use App\Models\Conversation;
use App\Models\Dispute;
use App\Models\Message;
use App\Models\Order;
use App\Models\User;
use App\Notifications\DisputeResolved;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->customer = User::factory()->customer()->create(['name' => 'Анна']);
    $this->executor = User::factory()->executor()->create(['name' => 'Сергей']);
    $this->conversation = Conversation::factory()->between($this->customer, $this->executor)->create();
    $this->order = $this->conversation->order;
    $this->order->update(['status' => OrderStatus::Disputed, 'delivered_at' => now()]);
    $this->dispute = Dispute::factory()->for($this->order)->create([
        'opened_by' => $this->customer->id,
        'reason' => 'Нет админки из задачи',
    ]);
});

it('lists open disputes with a navigation badge', function () {
    $resolved = Dispute::factory()->create(['status' => DisputeStatus::Resolved, 'resolution' => DisputeResolution::Completed]);

    Livewire::actingAs($this->admin)
        ->test(ListDisputes::class)
        ->assertCanSeeTableRecords([$this->dispute, $resolved], inOrder: true);

    expect(DisputeResource::getNavigationBadge())->toBe('1');
});

it('shows the admin the reason, the order and the whole chat', function () {
    Message::factory()->for($this->conversation)->create(['sender_id' => $this->customer->id, 'body' => 'Где админка?']);
    Message::factory()->for($this->conversation)->create(['sender_id' => $this->executor->id, 'body' => 'Админка не входила в сумму.']);

    $this->actingAs($this->admin)
        ->get(DisputeResource::getUrl('view', ['record' => $this->dispute]))
        ->assertOk()
        ->assertSee('Нет админки из задачи')
        ->assertSee($this->order->title)
        ->assertSee('Где админка?')
        ->assertSee('Админка не входила в сумму.');
});

it('resolves a dispute from the admin panel', function (DisputeResolution $resolution, OrderStatus $orderStatus) {
    Notification::fake();

    Livewire::actingAs($this->admin)
        ->test(ViewDispute::class, ['record' => $this->dispute->getRouteKey()])
        ->callAction($resolution->value, data: ['comment' => 'Решение по итогам переписки в чате.'])
        ->assertHasNoFormErrors();

    $dispute = $this->dispute->fresh();

    expect($dispute->status)->toBe(DisputeStatus::Resolved)
        ->and($dispute->resolution)->toBe($resolution)
        ->and($dispute->resolution_comment)->toBe('Решение по итогам переписки в чате.')
        ->and($dispute->resolved_by)->toBe($this->admin->id)
        ->and($this->order->fresh()->status)->toBe($orderStatus);

    Notification::assertSentTo([$this->customer, $this->executor], DisputeResolved::class);
})->with([
    'return to work' => [DisputeResolution::ReturnToWork, OrderStatus::InProgress],
    'count as completed' => [DisputeResolution::Completed, OrderStatus::Resolved],
    'cancel the order' => [DisputeResolution::Cancelled, OrderStatus::Resolved],
]);

it('requires a comment for the decision', function () {
    Livewire::actingAs($this->admin)
        ->test(ViewDispute::class, ['record' => $this->dispute->getRouteKey()])
        ->callAction(DisputeResolution::Completed->value, data: ['comment' => ''])
        ->assertHasFormErrors(['comment' => 'required']);

    expect($this->dispute->fresh()->status)->toBe(DisputeStatus::Open);
});

it('hides the decision buttons once the dispute is resolved', function () {
    app(ResolveDispute::class)->handle($this->admin, $this->dispute, DisputeResolution::Completed, 'Работа принята.');

    Livewire::actingAs($this->admin)
        ->test(ViewDispute::class, ['record' => $this->dispute->getRouteKey()])
        ->assertActionHidden(DisputeResolution::ReturnToWork->value)
        ->assertActionHidden(DisputeResolution::Completed->value);
});

it('does not resolve the same dispute twice', function () {
    app(ResolveDispute::class)->handle($this->admin, $this->dispute, DisputeResolution::Completed, 'Работа принята.');
    app(ResolveDispute::class)->handle($this->admin, $this->dispute->fresh(), DisputeResolution::Cancelled, 'Передумал.');
})->throws(AuctionException::class, 'уже решён');

it('lets only admins resolve disputes', function () {
    app(ResolveDispute::class)->handle($this->customer, $this->dispute, DisputeResolution::Cancelled, 'Отменяю сам.');
})->throws(AuctionException::class);

it('does not count orders closed through a dispute in the executor stats', function () {
    app(ResolveDispute::class)->handle($this->admin, $this->dispute, DisputeResolution::Completed, 'Засчитано.');
    Order::factory()->for($this->customer, 'customer')->completed($this->executor)->create();

    app(RecalculateExecutorStats::class)->handle($this->executor->id);

    // Считается только заказ, принятый заказчиком, а не засчитанный через спор.
    expect($this->order->fresh()->status)->toBe(OrderStatus::Resolved)
        ->and($this->executor->executorProfile->fresh()->completed_orders_count)->toBe(1);
});

it('lets the executor deliver again after the order is returned to work', function () {
    app(ResolveDispute::class)->handle($this->admin, $this->dispute, DisputeResolution::ReturnToWork, 'Доделайте админку.');

    expect($this->order->fresh()->delivered_at)->toBeNull();

    Livewire::actingAs($this->executor)
        ->test(OrderWorkflow::class, ['order' => $this->order->fresh(), 'returnUrl' => '/'])
        ->assertSee('возвращён на доработку')
        ->assertSee('Доделайте админку.')
        ->call('deliver');

    expect($this->order->fresh()->status)->toBe(OrderStatus::Delivered);
});

it('shows the decision to the customer', function () {
    app(ResolveDispute::class)->handle($this->admin, $this->dispute, DisputeResolution::Cancelled, 'Исполнитель не выполнил условия задачи.');

    Livewire::actingAs($this->customer)
        ->test(OrderWorkflow::class, ['order' => $this->order->fresh(), 'returnUrl' => '/'])
        ->assertSee('Спор решён: заказ отменён')
        ->assertSee('Исполнитель не выполнил условия задачи.');
});
