<?php

use App\Livewire\Customer\CreateOrder;
use App\Livewire\Executor\Feed;
use App\Models\Category;
use App\Models\Order;
use App\Models\User;
use Livewire\Livewire;

describe('tag suggestions in the order form', function () {
    beforeEach(function () {
        $this->customer = User::factory()->customer()->create();
    });

    it('suggests tags close in meaning to the order text', function () {
        fakeAi();
        $fridges = Category::factory()->tag()->create(['name' => 'Холодильники', 'keywords' => 'холодильник не морозит, течёт вода']);
        Category::factory()->tag()->create(['name' => 'Уборка квартир', 'keywords' => 'клининг, генеральная уборка']);

        $component = Livewire::actingAs($this->customer)
            ->test(CreateOrder::class)
            ->set('title', 'Холодильник перестал морозить');

        expect($component->instance()->suggestedTags->pluck('name')->all())->toBe(['Холодильники']);

        $component->call('addTag', $fridges->id)
            ->assertSet('categoryIds', [$fridges->id]);

        // Выбранный тег больше не предлагается.
        expect($component->instance()->suggestedTags)->toBeEmpty();
    });

    it('does not add a suggested tag over the limit', function () {
        fakeAi();
        $tags = Category::factory()->tag()->count(4)->create();

        Livewire::actingAs($this->customer)
            ->test(CreateOrder::class)
            ->set('categoryIds', $tags->take(3)->pluck('id')->map(fn ($id) => (string) $id)->all())
            ->call('addTag', $tags[3]->id)
            ->call('addTag', $tags[0]->id)
            ->assertCount('categoryIds', 3);
    });

    it('shows no suggestions when ai is disabled or failing', function () {
        Category::factory()->tag()->create(['name' => 'Холодильники']);

        $component = Livewire::actingAs($this->customer)
            ->test(CreateOrder::class)
            ->set('title', 'Холодильник перестал морозить')
            ->assertDontSee('По описанию подходят');
        expect($component->instance()->suggestedTags)->toBeEmpty();

        fakeAi()->failing = true;
        $component = Livewire::actingAs($this->customer)
            ->test(CreateOrder::class)
            ->set('title', 'Холодильник перестал морозить');
        expect($component->instance()->suggestedTags)->toBeEmpty();
    });

    it('fills the form from the assistant draft', function () {
        $tag = tag('Холодильники');

        session()->put('order_draft', [
            'title' => 'Холодильник не морозит',
            'description' => 'Холодильник не морозит, внизу лужа.',
            'city_id' => null,
            'category_ids' => [$tag->id],
        ]);

        Livewire::actingAs($this->customer)
            ->test(CreateOrder::class)
            ->assertSet('title', 'Холодильник не морозит')
            ->assertSet('description', 'Холодильник не морозит, внизу лужа.')
            ->assertSet('categoryIds', [$tag->id]);

        expect(session()->has('order_draft'))->toBeFalse();
    });
});

describe('semantic search in the executor feed', function () {
    beforeEach(function () {
        $this->fridges = tag('Холодильники');
        $this->executor = User::factory()->executor($this->fridges)->create();
    });

    it('finds orders by meaning, not only by exact words', function () {
        fakeAi();
        Order::factory()->withTags($this->fridges)->create(['title' => 'Холодильник течёт', 'description' => 'Под холодильником лужа, морозилка в инее.']);
        Order::factory()->withTags($this->fridges)->create(['title' => 'Шумит компрессор', 'description' => 'Гудит по ночам, Bosch.']);

        Livewire::actingAs($this->executor)
            ->test(Feed::class)
            ->set('search', 'холодильника лужа')
            ->assertSee('Холодильник течёт')
            ->assertDontSee('Шумит компрессор');
    });

    it('puts the closest orders first', function () {
        fakeAi();
        $far = Order::factory()->withTags($this->fridges)->create(['title' => 'Ремонт холодильника', 'description' => 'Не включается свет.']);
        $close = Order::factory()->withTags($this->fridges)->create(['title' => 'Холодильник не морозит', 'description' => 'Морозилка тёплая, ремонт срочно.']);
        $far->forceFill(['created_at' => now()->addMinute()])->save();

        $component = Livewire::actingAs($this->executor)
            ->test(Feed::class)
            ->set('search', 'холодильник не морозит');

        expect($component->instance()->orders->pluck('id')->all())->toBe([$close->id, $far->id]);
    });

    it('does not leak orders from outside the executor feed', function () {
        fakeAi();
        Order::factory()->withTags(tag('Уборка квартир'))->create(['title' => 'Холодильник помыть после переезда']);

        Livewire::actingAs($this->executor)
            ->test(Feed::class)
            ->set('search', 'холодильник')
            ->assertDontSee('Холодильник помыть');
    });

    it('falls back to word search when the provider is down', function () {
        Order::factory()->withTags($this->fridges)->create(['title' => 'Холодильник течёт']);
        fakeAi()->failing = true;

        Livewire::actingAs($this->executor)
            ->test(Feed::class)
            ->set('search', 'течёт')
            ->assertSee('Холодильник течёт');
    });
});
