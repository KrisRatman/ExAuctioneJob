<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Livewire\Customer\CreateOrder;
use App\Livewire\Customer\OrderList;
use App\Livewire\Customer\OrderShow;
use App\Livewire\Executor\EditProfile;
use App\Livewire\Executor\Feed;
use App\Livewire\Executor\MyBids;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Заказчик: свои заказы и предложения по ним
    Route::middleware('role:customer')->prefix('my/orders')->name('customer.orders.')->group(function () {
        Route::livewire('/', OrderList::class)->name('index');
        Route::livewire('/create', CreateOrder::class)->name('create');
        Route::livewire('/{order}', OrderShow::class)->name('show');
    });

    // Исполнитель: лента подходящих заказов, свои предложения, анкета
    Route::middleware('role:executor')->group(function () {
        Route::livewire('/feed', Feed::class)->name('executor.feed');
        Route::livewire('/my/bids', MyBids::class)->name('executor.bids');
        Route::livewire('/profile', EditProfile::class)->name('executor.profile');
    });
});
