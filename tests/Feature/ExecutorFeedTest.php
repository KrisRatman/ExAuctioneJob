<?php

use App\Livewire\Executor\Feed;
use App\Models\Order;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->laravel = tag('Laravel');
    $this->wordpress = tag('WordPress');
    $this->design = tag('Логотипы');

    $this->executor = User::factory()->executor([$this->laravel, $this->wordpress])->create();
});

it('shows only open orders sharing at least one tag with the executor', function () {
    Order::factory()->withTags($this->laravel)->create(['title' => 'Заказ на Laravel']);
    Order::factory()->withTags([$this->design, $this->wordpress])->create(['title' => 'Дизайн и WordPress']);
    Order::factory()->withTags($this->design)->create(['title' => 'Только логотип']);
    Order::factory()->withTags($this->laravel)->cancelled()->create(['title' => 'Отменённый']);
    Order::factory()->withTags($this->laravel)->inProgress()->create(['title' => 'Уже в работе']);

    Livewire::actingAs($this->executor)
        ->test(Feed::class)
        ->assertSee('Заказ на Laravel')
        ->assertSee('Дизайн и WordPress')
        ->assertDontSee('Только логотип')
        ->assertDontSee('Отменённый')
        ->assertDontSee('Уже в работе');
});

it('filters the feed by one of the executor tags', function () {
    Order::factory()->withTags($this->laravel)->create(['title' => 'Заказ на Laravel']);
    Order::factory()->withTags($this->wordpress)->create(['title' => 'Заказ на WordPress']);

    Livewire::actingAs($this->executor)
        ->test(Feed::class)
        ->set('tag', $this->wordpress->id)
        ->assertSee('Заказ на WordPress')
        ->assertDontSee('Заказ на Laravel');
});

it('searches the feed by title and description', function () {
    Order::factory()->withTags($this->laravel)->create(['title' => 'Сайт-меню для кофейни']);
    Order::factory()->withTags($this->laravel)->create(['title' => 'REST API для мобильного приложения']);

    Livewire::actingAs($this->executor)
        ->test(Feed::class)
        ->set('search', 'кофейни')
        ->assertSee('Сайт-меню для кофейни')
        ->assertDontSee('REST API');
});

it('does not open an order that does not match the executor tags', function () {
    $foreign = Order::factory()->withTags($this->design)->create();

    Livewire::actingAs($this->executor)
        ->test(Feed::class)
        ->call('open', $foreign->id)
        ->assertSet('openOrderId', null)
        ->assertDispatched('toast');
});

it('shows each order once even when several tags match', function () {
    Order::factory()->withTags([$this->laravel, $this->wordpress])->create(['title' => 'Два совпадения']);

    $component = Livewire::actingAs($this->executor)->test(Feed::class);

    expect($component->instance()->orders->total())->toBe(1);
});
