<?php

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\User;

it('registers a customer and sends them to their orders', function () {
    $this->post('/register', [
        'role' => 'customer',
        'name' => 'Анна',
        'email' => 'anna@example.com',
        'password' => 'secret-password',
        'password_confirmation' => 'secret-password',
    ])->assertRedirect(route('customer.orders.index'));

    $user = User::query()->where('email', 'anna@example.com')->firstOrFail();

    expect($user->role)->toBe(UserRole::Customer)
        ->and($user->executorProfile()->exists())->toBeFalse();
    $this->assertAuthenticatedAs($user);
});

it('registers an executor with a profile and specialisation tags', function () {
    $laravel = tag('Laravel');
    $wordpress = tag('WordPress');

    $this->post('/register', [
        'role' => 'executor',
        'name' => 'Дмитрий',
        'email' => 'dima@example.com',
        'phone' => '+7 900 123-45-67',
        'password' => 'secret-password',
        'password_confirmation' => 'secret-password',
        'description' => 'Фулстек-разработчик, четыре года опыта на Laravel и WordPress.',
        'categories' => [$laravel->id, $wordpress->id],
    ])->assertRedirect(route('executor.feed'));

    $user = User::query()->where('email', 'dima@example.com')->firstOrFail();

    expect($user->role)->toBe(UserRole::Executor)
        ->and($user->phone)->toBe('+7 900 123-45-67')
        ->and($user->executorProfile->description)->toStartWith('Фулстек')
        ->and($user->categories->modelKeys())->toEqualCanonicalizing([$laravel->id, $wordpress->id]);
});

it('requires an executor to describe themselves and pick tags', function () {
    $this->post('/register', [
        'role' => 'executor',
        'name' => 'Дмитрий',
        'email' => 'dima@example.com',
        'password' => 'secret-password',
        'password_confirmation' => 'secret-password',
    ])->assertSessionHasErrors(['description', 'categories']);

    expect(User::query()->count())->toBe(0);
});

it('does not accept a top-level section as a tag', function () {
    $section = Category::factory()->create();

    $this->post('/register', [
        'role' => 'executor',
        'name' => 'Дмитрий',
        'email' => 'dima@example.com',
        'password' => 'secret-password',
        'password_confirmation' => 'secret-password',
        'description' => 'Фулстек-разработчик, четыре года опыта на Laravel и WordPress.',
        'categories' => [$section->id],
    ])->assertSessionHasErrors('categories.0');
});

it('does not let anyone register as an admin', function () {
    $this->post('/register', [
        'role' => 'admin',
        'name' => 'Хакер',
        'email' => 'hacker@example.com',
        'password' => 'secret-password',
        'password_confirmation' => 'secret-password',
    ])->assertSessionHasErrors('role');

    expect(User::query()->count())->toBe(0);
});

it('logs users in and redirects them to their own area', function (Closure $makeUser, string $expectedPath) {
    $user = $makeUser();

    $this->post('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect($expectedPath);
})->with([
    'customer' => [fn () => User::factory()->customer()->create(), '/my/orders'],
    'executor' => [fn () => User::factory()->executor()->create(), '/feed'],
    'admin' => [fn () => User::factory()->admin()->create(), '/admin'],
]);

it('rejects a wrong password', function () {
    $user = User::factory()->create();

    $this->post('/login', ['email' => $user->email, 'password' => 'wrong'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('keeps each role inside its own area', function () {
    $customer = User::factory()->customer()->create();
    $executor = User::factory()->executor()->create();

    $this->actingAs($customer)->get('/feed')->assertRedirect('/my/orders');
    $this->actingAs($executor)->get('/my/orders')->assertRedirect('/feed');
});

it('sends guests to the login page', function () {
    $this->get('/my/orders')->assertRedirect('/login');
    $this->get('/feed')->assertRedirect('/login');
});

it('shows the public pages', function () {
    tag('Laravel');

    $this->get('/')->assertOk()->assertSee('Laravel');
    $this->get('/login')->assertOk();
    $this->get('/register?role=executor')->assertOk()->assertSee('Специализации');
});
