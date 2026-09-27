<?php

use App\Actions\SendMessage;
use App\Enums\BidStatus;
use App\Livewire\Chat\Show;
use App\Livewire\Executor\Feed;
use App\Livewire\Executor\MyBids;
use App\Models\Bid;
use App\Models\Conversation;
use App\Models\Order;
use App\Models\User;
use Livewire\Livewire;

it('shows the freshest open orders on the home page', function () {
    Order::factory()->withTags(tag())->create(['title' => 'Открытый заказ', 'starting_price' => 12000]);
    Order::factory()->cancelled()->create(['title' => 'Отменённый заказ']);

    $this->get('/')
        ->assertOk()
        ->assertSee('Свежие заказы')
        ->assertSee('Открытый заказ')
        ->assertDontSee('Отменённый заказ');
});

it('splits executor bids into active and archive tabs', function () {
    $executor = User::factory()->executor()->create();
    Bid::factory()->for($executor, 'executor')->for(Order::factory()->create(['title' => 'Ждёт решения заказчика']))->create();
    Bid::factory()->for($executor, 'executor')->for(Order::factory()->inProgress($executor)->create(['title' => 'Заказ в работе']))->create(['status' => BidStatus::Accepted]);
    Bid::factory()->for($executor, 'executor')->for(Order::factory()->create(['title' => 'Отдан другому']))->create(['status' => BidStatus::Rejected]);
    Bid::factory()->for($executor, 'executor')->for(Order::factory()->completed($executor)->create(['title' => 'Давно выполнен']))->create(['status' => BidStatus::Accepted]);

    Livewire::actingAs($executor)
        ->test(MyBids::class)
        ->assertSee('Ждёт решения заказчика')
        ->assertSee('Заказ в работе')
        ->assertDontSee('Отдан другому')
        ->assertDontSee('Давно выполнен')
        ->set('tab', 'archive')
        ->assertSee('Отдан другому')
        ->assertSee('Давно выполнен')
        ->assertDontSee('Заказ в работе');
});

it('counts open orders for every executor tag in the feed', function () {
    $laravel = tag('Laravel');
    $wordpress = tag('WordPress');
    $executor = User::factory()->executor([$laravel, $wordpress])->create();
    Order::factory()->count(2)->withTags($laravel)->create();
    Order::factory()->withTags($laravel)->cancelled()->create();

    $component = Livewire::actingAs($executor)->test(Feed::class);

    expect($component->instance()->totalCount)->toBe(2)
        ->and($component->instance()->myTags->firstWhere('id', $laravel->id)->open_orders_count)->toBe(2)
        ->and($component->instance()->myTags->firstWhere('id', $wordpress->id)->open_orders_count)->toBe(0);
});

it('shows the mobile tab bar only to customers and executors', function () {
    $this->actingAs(User::factory()->executor()->create())->get('/feed')->assertSee('aria-label="Разделы"', false);
    $this->actingAs(User::factory()->customer()->create())->get('/my/orders')->assertSee('aria-label="Разделы"', false);
});

it('does not show the mobile tab bar to guests', function () {
    $this->get('/')->assertDontSee('aria-label="Разделы"', false);
});

it('logs into demo accounts with one click on the demo stand', function (string $role, string $email, string $home) {
    config(['ideajob.demo' => true]);
    $user = User::factory()->create(['email' => $email, 'role' => $role]);

    $this->get('/login')->assertSee('Демо-стенд');
    $this->post("/demo/login/{$role}")->assertRedirect($home);

    $this->assertAuthenticatedAs($user);
})->with([
    'customer' => ['customer', 'customer@example.com', '/my/orders'],
    'executor' => ['executor', 'executor@example.com', '/feed'],
]);

it('has no one-click login outside the demo stand', function () {
    config(['ideajob.demo' => false]);
    User::factory()->customer()->create(['email' => 'customer@example.com']);

    $this->get('/login')->assertDontSee('Демо-стенд');
    $this->post('/demo/login/customer')->assertNotFound();
    $this->assertGuest();
});

it('never logs into the admin with one click', function () {
    config(['ideajob.demo' => true]);
    User::factory()->admin()->create(['email' => 'admin@example.com']);

    $this->post('/demo/login/admin')->assertNotFound();
    $this->assertGuest();
});

it('picks up new chat messages by polling when WebSocket is unavailable', function () {
    $customer = User::factory()->customer()->create();
    $executor = User::factory()->executor()->create();
    $conversation = Conversation::factory()->between($customer, $executor)->create();

    $component = Livewire::actingAs($customer)->test(Show::class, ['conversation' => $conversation]);

    $component->call('poll')->assertNotDispatched('chat-scroll');

    app(SendMessage::class)->handle($executor, $conversation, 'Пришло без WebSocket');

    $component->call('poll')
        ->assertSee('Пришло без WebSocket')
        ->assertDispatched('chat-scroll');

    expect($customer->unreadMessagesCount())->toBe(0);
});
