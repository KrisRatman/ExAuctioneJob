<?php

use App\Actions\AcceptBid;
use App\Actions\CancelOrder;
use App\Actions\PlaceBid;
use App\Enums\BidStatus;
use App\Livewire\Customer\OrderShow;
use App\Livewire\NotificationBell;
use App\Models\Bid;
use App\Models\Conversation;
use App\Models\Order;
use App\Models\User;
use App\Notifications\BidRejected;
use App\Notifications\Channels\SafeBroadcastChannel;
use App\Notifications\ExecutorAccepted;
use App\Notifications\NewBidPlaced;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    $this->tag = tag();
    $this->customer = User::factory()->customer()->create();
    $this->order = Order::factory()->for($this->customer, 'customer')->withTags($this->tag)->create(['title' => 'Лендинг для кофейни', 'starting_price' => 15000]);
});

function placeBid(User $executor, Order $order, int $price = 10000): Bid
{
    return app(PlaceBid::class)->handle($executor, $order, [
        'offer_price' => $price,
        'approach_description' => 'Сделаю аккуратно и в срок.',
        'duration_days' => 5,
    ]);
}

it('notifies the customer about a new bid but not about its edits', function () {
    Notification::fake();
    $executor = User::factory()->executor($this->tag)->create();

    placeBid($executor, $this->order);
    placeBid($executor, $this->order, 9000);

    Notification::assertSentToTimes($this->customer, NewBidPlaced::class, 1);
});

it('notifies the accepted executor and the rejected ones', function () {
    Notification::fake();
    [$chosen, $other, $third] = Bid::factory()->count(3)->for($this->order)->create()->all();
    $third->update(['status' => BidStatus::Rejected]);

    $conversation = app(AcceptBid::class)->handle($this->customer, $chosen);

    Notification::assertSentTo($chosen->executor, ExecutorAccepted::class, fn (ExecutorAccepted $n) => $n->conversation->is($conversation));
    Notification::assertSentTo($other->executor, BidRejected::class, fn (BidRejected $n) => $n->reason === BidRejected::ReasonOtherExecutor);
    // Отклонённый раньше исполнитель второй раз уведомление не получает.
    Notification::assertNotSentTo($third->executor, BidRejected::class);
    Notification::assertNotSentTo($chosen->executor, BidRejected::class);
});

it('opens exactly one conversation between the customer and the chosen executor', function () {
    $bid = Bid::factory()->for($this->order)->create();

    $conversation = app(AcceptBid::class)->handle($this->customer, $bid);

    expect(Conversation::query()->sole()->is($conversation))->toBeTrue()
        ->and($conversation->order_id)->toBe($this->order->id)
        ->and($conversation->customer_id)->toBe($this->customer->id)
        ->and($conversation->executor_id)->toBe($bid->executor_id);
});

it('notifies bidders when the order is cancelled', function () {
    Notification::fake();
    $bid = Bid::factory()->for($this->order)->create();

    app(CancelOrder::class)->handle($this->customer, $this->order);

    Notification::assertSentTo($bid->executor, BidRejected::class, fn (BidRejected $n) => $n->reason === BidRejected::ReasonCancelled);
});

it('stores notifications for the bell with a link to the chat', function () {
    $bid = Bid::factory()->for($this->order)->create();

    $conversation = app(AcceptBid::class)->handle($this->customer, $bid);

    $data = $bid->executor->notifications()->sole()->data;

    expect($data['title'])->toBe('Вас приняли на заказ')
        ->and($data['body'])->toContain('Лендинг для кофейни')
        ->and($data['url'])->toBe(route('chats.show', $conversation))
        ->and($data['icon'])->toStartWith('heroicon-');
});

it('broadcasts notifications to the private user channel', function () {
    $bid = Bid::factory()->for($this->order)->create();
    $notification = new NewBidPlaced($bid->load(['order', 'executor']));

    expect($this->customer->receivesBroadcastNotificationsOn())->toBe('user.'.$this->customer->id)
        ->and($notification->via($this->customer))->toBe(['database', SafeBroadcastChannel::class])
        ->and($notification->broadcastType())->toBe('NewBidPlaced')
        ->and($notification->toBroadcast($this->customer)->data['order_id'])->toBe($this->order->id);
});

it('authorizes only the owner to listen to a private user channel', function () {
    config([
        'broadcasting.default' => 'reverb',
        'broadcasting.connections.reverb.key' => 'test-key',
        'broadcasting.connections.reverb.secret' => 'test-secret',
        'broadcasting.connections.reverb.app_id' => '1',
    ]);
    // Каналы регистрируются на драйвере при загрузке — пересоздать драйвер и загрузить их заново.
    Broadcast::purge();
    require base_path('routes/channels.php');

    $this->actingAs($this->customer)
        ->post('/broadcasting/auth', ['socket_id' => '1234.5678', 'channel_name' => 'private-user.'.$this->customer->id])
        ->assertOk()
        ->assertJsonStructure(['auth']);

    $this->actingAs($this->customer)
        ->post('/broadcasting/auth', ['socket_id' => '1234.5678', 'channel_name' => 'private-user.'.($this->customer->id + 1)])
        ->assertForbidden();
});

it('shows unread notifications in the bell and opens them', function () {
    $bid = Bid::factory()->for($this->order)->create();
    $conversation = app(AcceptBid::class)->handle($this->customer, $bid);
    $executor = $bid->executor;

    $component = Livewire::actingAs($executor)
        ->test(NotificationBell::class)
        ->assertSee('Вас приняли на заказ')
        ->assertSeeHtml('data-unread="1"');

    $component->call('open', $executor->notifications()->sole()->id)
        ->assertRedirect(route('chats.show', $conversation));

    expect($executor->unreadNotifications()->count())->toBe(0);
});

it('marks all notifications as read', function () {
    Bid::factory()->count(2)->for($this->order)->create();
    Order::factory()->for($this->customer, 'customer')->create();
    placeBid(User::factory()->executor($this->tag)->create(), $this->order);

    Livewire::actingAs($this->customer)
        ->test(NotificationBell::class)
        ->call('markAllRead')
        ->assertDontSeeHtml('data-unread=');

    expect($this->customer->unreadNotifications()->count())->toBe(0);
});

it('does not open someone else notification', function () {
    $bid = Bid::factory()->for($this->order)->create();
    app(AcceptBid::class)->handle($this->customer, $bid);

    Livewire::actingAs($this->customer)
        ->test(NotificationBell::class)
        ->call('open', $bid->executor->notifications()->sole()->id)
        ->assertNotFound();
});

it('refreshes the bids of the open order when a bid notification arrives', function () {
    $component = Livewire::actingAs($this->customer)->test(OrderShow::class, ['order' => $this->order]);

    $bid = placeBid(User::factory()->executor($this->tag)->create(['name' => 'Новый Исполнитель']), $this->order);

    $component->call('onNotification', ['order_id' => $this->order->id, 'title' => 'x'])
        ->assertSee('Новый Исполнитель');
});
