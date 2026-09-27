<?php

use App\Actions\SendMessage;
use App\Events\NewMessage;
use App\Exceptions\AuctionException;
use App\Livewire\Chat\Index;
use App\Livewire\Chat\Show;
use App\Livewire\ChatNavLink;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Notifications\NewMessageReceived;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

beforeEach(function () {
    $this->customer = User::factory()->customer()->create(['name' => 'Анна']);
    $this->executor = User::factory()->executor()->create(['name' => 'Дмитрий']);
    $this->conversation = Conversation::factory()->between($this->customer, $this->executor)->create();
});

it('opens the chat for both participants', function () {
    $this->actingAs($this->customer)->get(route('chats.show', $this->conversation))->assertOk()->assertSee('Дмитрий');
    $this->actingAs($this->executor)->get(route('chats.show', $this->conversation))->assertOk()->assertSee('Анна');
});

it('hides the chat from everyone else', function () {
    $this->actingAs(User::factory()->customer()->create())->get(route('chats.show', $this->conversation))->assertNotFound();
    $this->actingAs(User::factory()->executor()->create())->get(route('chats.show', $this->conversation))->assertNotFound();
});

it('sends guests to the login page from a chat', function () {
    $this->get(route('chats.show', $this->conversation))->assertRedirect('/login');
});

it('sends a message, broadcasts it and notifies the other participant', function () {
    Event::fake([NewMessage::class]);

    Livewire::actingAs($this->customer)
        ->test(Show::class, ['conversation' => $this->conversation])
        ->set('body', "Здравствуйте!\nКогда сможете начать?")
        ->call('send')
        ->assertHasNoErrors()
        ->assertSet('body', '')
        ->assertSee('Когда сможете начать?')
        ->assertDispatched('chat-scroll');

    $message = Message::query()->sole();

    expect($message->sender_id)->toBe($this->customer->id)
        ->and($this->conversation->fresh()->last_message_at)->not->toBeNull()
        ->and($this->executor->unreadNotifications()->sole()->type)->toBe(NewMessageReceived::class)
        ->and($this->customer->notifications()->count())->toBe(0);

    Event::assertDispatched(NewMessage::class, fn (NewMessage $event) => $event->messageId === $message->id);
});

it('does not send an empty message', function () {
    Livewire::actingAs($this->customer)
        ->test(Show::class, ['conversation' => $this->conversation])
        ->set('body', '')
        ->call('send')
        ->assertHasErrors(['body' => 'required']);

    expect(Message::query()->count())->toBe(0);
});

it('does not let a stranger write into the chat', function () {
    app(SendMessage::class)->handle(User::factory()->customer()->create(), $this->conversation, 'Привет');
})->throws(AuctionException::class);

it('broadcasts a new message to both participants', function () {
    $message = Message::factory()->for($this->conversation)->create(['sender_id' => $this->executor->id]);
    $event = new NewMessage($message->load('conversation'));

    expect(collect($event->broadcastOn())->map(fn (PrivateChannel $channel) => $channel->name)->all())
        ->toBe(['private-user.'.$this->customer->id, 'private-user.'.$this->executor->id])
        ->and($event->broadcastAs())->toBe('NewMessage')
        ->and($event->broadcastWith())->toBe([
            'conversation_id' => $this->conversation->id,
            'message_id' => $message->id,
            'sender_id' => $this->executor->id,
        ]);
});

it('keeps one bell notification per conversation', function () {
    $send = app(SendMessage::class);
    $send->handle($this->customer, $this->conversation, 'Первое');
    $send->handle($this->customer, $this->conversation, 'Второе');
    $send->handle($this->customer, $this->conversation, 'Третье');

    $notification = $this->executor->unreadNotifications()->sole();

    expect($notification->data['body'])->toBe('Анна: Третье')
        ->and($notification->data['url'])->toBe(route('chats.show', $this->conversation));
});

it('marks the other participant messages as read when the chat is opened', function () {
    $send = app(SendMessage::class);
    $send->handle($this->customer, $this->conversation, 'Вопрос от заказчика');
    $send->handle($this->executor, $this->conversation, 'Моё старое сообщение');

    expect($this->executor->unreadMessagesCount())->toBe(1);

    Livewire::actingAs($this->executor)->test(Show::class, ['conversation' => $this->conversation]);

    expect($this->executor->unreadMessagesCount())->toBe(0)
        ->and($this->executor->unreadNotifications()->count())->toBe(0)
        // Своё сообщение прочитанным не становится — его должен прочитать заказчик.
        ->and($this->customer->unreadMessagesCount())->toBe(1);
});

it('marks an incoming message read only in the open conversation', function () {
    $otherConversation = Conversation::factory()->between($this->customer, User::factory()->executor()->create())->create();

    $component = Livewire::actingAs($this->customer)->test(Show::class, ['conversation' => $this->conversation]);

    $incoming = app(SendMessage::class)->handle($this->executor, $this->conversation, 'Здесь');
    $elsewhere = app(SendMessage::class)->handle($otherConversation->executor()->first(), $otherConversation, 'Там');

    $component->call('onNewMessage', ['conversation_id' => $this->conversation->id, 'message_id' => $incoming->id, 'sender_id' => $this->executor->id])
        ->assertSee('Здесь')
        ->assertDispatched('chat-read');
    $component->call('onNewMessage', ['conversation_id' => $otherConversation->id, 'message_id' => $elsewhere->id, 'sender_id' => $otherConversation->executor_id]);

    expect($incoming->fresh()->read_at)->not->toBeNull()
        ->and($elsewhere->fresh()->read_at)->toBeNull();
});

it('shows unread counters in the menu and the chat list', function () {
    app(SendMessage::class)->handle($this->customer, $this->conversation, 'Раз');
    app(SendMessage::class)->handle($this->customer, $this->conversation, 'Два');

    Livewire::actingAs($this->executor)->test(ChatNavLink::class)->assertSeeHtml('data-unread-messages="2"');

    Livewire::actingAs($this->executor)
        ->test(Index::class)
        ->assertSee('Анна')
        ->assertSee('Два')
        ->assertSee('Непрочитанных: 2');
});

it('lists only the user own conversations', function () {
    $foreign = Conversation::factory()->create();

    Livewire::actingAs($this->customer)
        ->test(Index::class)
        ->assertSee('Дмитрий')
        ->assertDontSee($foreign->order->title);
});

it('links the executor from an accepted bid to the chat', function () {
    $this->conversation->order->bids()->create([
        'executor_id' => $this->executor->id,
        'offer_price' => 10000,
        'approach_description' => 'Подход',
        'duration_days' => 3,
        'status' => 'accepted',
    ]);

    $this->actingAs($this->executor)
        ->get('/my/bids')
        ->assertSee('Чат с заказчиком')
        ->assertSee(route('chats.show', $this->conversation));
});

it('still sends the message when the WebSocket server is down', function () {
    config([
        'broadcasting.default' => 'reverb',
        'broadcasting.connections.reverb.key' => 'test-key',
        'broadcasting.connections.reverb.secret' => 'test-secret',
        'broadcasting.connections.reverb.app_id' => '1',
        'broadcasting.connections.reverb.options.host' => '127.0.0.1',
        'broadcasting.connections.reverb.options.port' => 1,
        'broadcasting.connections.reverb.options.scheme' => 'http',
        'broadcasting.connections.reverb.options.useTLS' => false,
    ]);

    $message = app(SendMessage::class)->handle($this->customer, $this->conversation, 'Reverb выключен');

    expect($message->exists)->toBeTrue()
        ->and($this->executor->unreadMessagesCount())->toBe(1);
});
