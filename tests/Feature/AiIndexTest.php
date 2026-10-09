<?php

use App\Models\Category;
use App\Models\Embedding;
use App\Models\Order;
use Database\Seeders\CategorySeeder;

it('does not index anything while ai is disabled', function () {
    Order::factory()->withTags(tag('Холодильники'))->create();

    expect(Embedding::query()->count())->toBe(0);

    $this->artisan('ideajob:ai-index')->assertFailed();
});

it('indexes a new order and re-embeds it only when its text changes', function () {
    $ai = fakeAi();
    $order = Order::factory()->create(['title' => 'Холодильник не морозит']);

    expect($order->embedding()->exists())->toBeTrue()
        ->and($ai->embeddedDocuments)->toBe(1);

    $order->update(['starting_price' => 5000]);
    expect($ai->embeddedDocuments)->toBe(1);

    $order->update(['title' => 'Холодильник течёт']);
    expect($ai->embeddedDocuments)->toBe(2)
        ->and($order->embedding()->value('content_hash'))->toBe(sha1("Холодильник течёт\n".$order->description));
});

it('indexes tags with their section and keywords, but not sections', function () {
    fakeAi();
    $fridges = Category::factory()->tag()->create(['name' => 'Холодильники', 'keywords' => 'не морозит']);

    expect($fridges->embeddingText())->toBe($fridges->parent->name.': Холодильники. не морозит')
        ->and($fridges->embedding()->exists())->toBeTrue()
        ->and($fridges->parent->embedding()->exists())->toBeFalse();
});

it('removes the vector together with the order', function () {
    fakeAi();
    $order = Order::factory()->create();

    $order->delete();

    expect(Embedding::query()->count())->toBe(0);
});

it('builds the whole index with the artisan command and skips unchanged records', function () {
    $this->seed(CategorySeeder::class);
    Order::factory()->count(2)->create();
    $ai = fakeAi();

    $this->artisan('ideajob:ai-index')->assertSuccessful();

    $tags = Category::query()->tags()->count();
    expect(Embedding::query()->count())->toBe($tags + 2)
        ->and($ai->embeddedDocuments)->toBe($tags + 2);

    $this->artisan('ideajob:ai-index')->assertSuccessful();
    expect($ai->embeddedDocuments)->toBe($tags + 2);
});

it('fails the command without breaking the index when the provider is down', function () {
    Order::factory()->create();
    fakeAi()->failing = true;

    $this->artisan('ideajob:ai-index')->assertFailed();

    expect(Embedding::query()->count())->toBe(0);
});
