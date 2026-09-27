<?php

use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Filament\Resources\Orders\RelationManagers\BidsRelationManager;
use App\Models\Bid;
use App\Models\Category;
use App\Models\Order;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

it('lets only admins into the panel', function () {
    $this->actingAs($this->admin)->get('/admin')->assertOk();
    $this->actingAs(User::factory()->customer()->create())->get('/admin')->assertForbidden();
    $this->actingAs(User::factory()->executor()->create())->get('/admin')->assertForbidden();
});

it('renders every admin page', function (string $path) {
    $order = Order::factory()->withTags(tag())->create();
    Bid::factory()->for($order)->create();

    $this->actingAs($this->admin)->get(str_replace('{order}', (string) $order->id, $path))->assertOk();
})->with([
    '/admin/categories',
    '/admin/categories/create',
    '/admin/users',
    '/admin/orders',
    '/admin/orders/{order}',
]);

it('creates a tag inside a section', function () {
    $section = Category::factory()->create(['name' => 'Веб-разработка']);

    Livewire::actingAs($this->admin)
        ->test(CreateCategory::class)
        ->fillForm(['name' => 'Symfony', 'slug' => 'symfony', 'parent_id' => $section->id, 'sort' => 60])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Category::query()->where('slug', 'symfony')->sole()->parent_id)->toBe($section->id);
});

it('lists sections followed by their tags', function () {
    $web = Category::factory()->create(['name' => 'Веб', 'sort' => 10]);
    $design = Category::factory()->create(['name' => 'Дизайн', 'sort' => 20]);
    $logo = Category::factory()->tag($design)->create(['name' => 'Логотипы']);
    $laravel = Category::factory()->tag($web)->create(['name' => 'Laravel']);

    Livewire::actingAs($this->admin)
        ->test(ListCategories::class)
        ->assertCanSeeTableRecords([$web, $laravel, $design, $logo], inOrder: true);
});

it('does not let a section with tags become a tag itself', function () {
    $section = Category::factory()->create();
    Category::factory()->tag($section)->create();

    Livewire::actingAs($this->admin)
        ->test(EditCategory::class, ['record' => $section->getRouteKey()])
        ->assertFormFieldDisabled('parent_id');
});

it('hides delete for a tag that is in use', function () {
    $tag = tag();
    User::factory()->executor($tag)->create();
    $unused = tag('Никому не нужный');

    Livewire::actingAs($this->admin)
        ->test(ListCategories::class)
        ->assertActionHidden(TestAction::make('delete')->table($tag))
        ->assertActionVisible(TestAction::make('delete')->table($unused));
});

it('shows the bids of an order', function () {
    $order = Order::factory()->withTags(tag())->create();
    $bid = Bid::factory()->for($order)->create();

    Livewire::actingAs($this->admin)
        ->test(ViewOrder::class, ['record' => $order->getRouteKey()])
        ->assertSee($order->title);

    Livewire::actingAs($this->admin)
        ->test(BidsRelationManager::class, ['ownerRecord' => $order, 'pageClass' => ViewOrder::class])
        ->assertCanSeeTableRecords([$bid])
        ->assertSee($bid->executor->name);
});
