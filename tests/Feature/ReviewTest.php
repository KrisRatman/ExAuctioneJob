<?php

use App\Actions\LeaveReview;
use App\Exceptions\AuctionException;
use App\Filament\Resources\Reviews\Pages\ListReviews;
use App\Livewire\Customer\OrderShow;
use App\Livewire\OrderWorkflow;
use App\Models\Bid;
use App\Models\Order;
use App\Models\Review;
use App\Models\User;
use App\Notifications\ReviewReceived;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    $this->customer = User::factory()->customer()->create();
    $this->executor = User::factory()->executor()->create();
    $this->order = Order::factory()->for($this->customer, 'customer')->completed($this->executor)->create();
});

it('lets the customer rate the executor after completion', function () {
    Notification::fake();

    Livewire::actingAs($this->customer)
        ->test(OrderWorkflow::class, ['order' => $this->order, 'returnUrl' => '/my/orders'])
        ->assertSee('Оцените работу исполнителя')
        ->set('rating', 4)
        ->set('comment', 'Сделано хорошо, но с небольшой задержкой.')
        ->call('review')
        ->assertHasNoErrors()
        ->assertRedirect('/my/orders');

    $review = Review::query()->sole();

    expect($review->rating)->toBe(4)
        ->and($review->executor_id)->toBe($this->executor->id)
        ->and($review->customer_id)->toBe($this->customer->id);

    Notification::assertSentTo($this->executor, ReviewReceived::class);
});

it('requires a rating between 1 and 5 but not a comment', function () {
    $component = Livewire::actingAs($this->customer)
        ->test(OrderWorkflow::class, ['order' => $this->order, 'returnUrl' => '/']);

    $component->call('review')->assertHasErrors(['rating' => 'required']);
    $component->set('rating', 6)->call('review')->assertHasErrors(['rating' => 'between']);
    $component->set('rating', 5)->set('comment', '')->call('review')->assertHasNoErrors();

    expect(Review::query()->sole()->comment)->toBeNull();
});

it('caches the average rating and counters in the executor profile', function () {
    $leave = app(LeaveReview::class);
    $leave->handle($this->customer, $this->order, 5, null);

    $second = Order::factory()->for($this->customer, 'customer')->completed($this->executor)->create();
    $leave->handle($this->customer, $second, 4, 'Хорошо');

    // Заказ в работе не считается выполненным.
    Order::factory()->for($this->customer, 'customer')->inProgress($this->executor)->create();

    $profile = $this->executor->executorProfile->fresh();

    expect($profile->rating_avg)->toBe(4.5)
        ->and($profile->reviews_count)->toBe(2)
        ->and($profile->completed_orders_count)->toBe(2);
});

it('allows only one review per order', function () {
    app(LeaveReview::class)->handle($this->customer, $this->order, 5, null);
    app(LeaveReview::class)->handle($this->customer, $this->order->fresh(), 1, 'Передумал');
})->throws(AuctionException::class, 'уже оценили');

it('does not accept reviews for orders that are not completed', function (Closure $makeOrder) {
    app(LeaveReview::class)->handle($this->customer, $makeOrder($this), 5, null);
})->throws(AuctionException::class, 'только выполненный')->with([
    'in progress' => [fn ($test) => Order::factory()->for($test->customer, 'customer')->inProgress($test->executor)->create()],
    'closed through a dispute' => [fn ($test) => Order::factory()->for($test->customer, 'customer')->inProgress($test->executor)->create(['status' => 'resolved'])],
]);

it('does not let another customer review the order', function () {
    app(LeaveReview::class)->handle(User::factory()->customer()->create(), $this->order, 1, null);
})->throws(AuctionException::class, 'только автор заказа');

it('shows the left review instead of the form', function () {
    app(LeaveReview::class)->handle($this->customer, $this->order, 5, 'Лучший исполнитель');

    Livewire::actingAs($this->customer)
        ->test(OrderWorkflow::class, ['order' => $this->order->fresh(), 'returnUrl' => '/'])
        ->assertDontSee('Оцените работу исполнителя')
        ->assertSee('Лучший исполнитель');

    Livewire::actingAs($this->executor)
        ->test(OrderWorkflow::class, ['order' => $this->order->fresh(), 'returnUrl' => '/'])
        ->assertSee('Оценка заказчика')
        ->assertSee('Лучший исполнитель');
});

it('shows the latest reviews in the executor card of a bid', function () {
    app(LeaveReview::class)->handle($this->customer, $this->order, 5, 'Отзыв о прошлом заказе');

    $newOrder = Order::factory()->for($this->customer, 'customer')->create();
    $bid = Bid::factory()->for($newOrder)->for($this->executor, 'executor')->create();

    Livewire::actingAs($this->customer)
        ->test(OrderShow::class, ['order' => $newOrder])
        ->call('selectBid', $bid->id)
        ->assertSee('Отзывы заказчиков')
        ->assertSee('Отзыв о прошлом заказе');
});

it('shows the reviews on the executor profile', function () {
    app(LeaveReview::class)->handle($this->customer, $this->order, 4, 'Спасибо за работу');

    $this->actingAs($this->executor)
        ->get('/profile')
        ->assertOk()
        ->assertSee('Спасибо за работу');
});

it('recalculates the rating when an admin deletes a review', function () {
    $review = app(LeaveReview::class)->handle($this->customer, $this->order, 1, 'Грубый отзыв');

    Livewire::actingAs(User::factory()->admin()->create())
        ->test(ListReviews::class)
        ->assertCanSeeTableRecords([$review])
        ->callAction(TestAction::make('delete')->table($review));

    $profile = $this->executor->executorProfile->fresh();

    expect(Review::query()->count())->toBe(0)
        ->and($profile->rating_avg)->toBeNull()
        ->and($profile->reviews_count)->toBe(0)
        ->and($profile->completed_orders_count)->toBe(1);
});
