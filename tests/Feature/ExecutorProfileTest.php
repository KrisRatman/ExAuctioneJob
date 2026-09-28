<?php

use App\Livewire\Executor\EditProfile;
use App\Models\City;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->tag = tag('Укладка плитки');
    $this->executor = User::factory()->executor($this->tag)->create();
});

it('moves the executor to another city', function () {
    $moscow = City::factory()->create(['name' => 'Москва']);

    Livewire::actingAs($this->executor)
        ->test(EditProfile::class)
        ->assertSet('cityId', $this->executor->executorProfile->city_id)
        ->set('cityId', $moscow->id)
        ->call('save')
        ->assertHasNoErrors();

    expect($this->executor->executorProfile->fresh()->city_id)->toBe($moscow->id);
});

it('requires a city in the profile', function () {
    Livewire::actingAs($this->executor)
        ->test(EditProfile::class)
        ->set('cityId', null)
        ->call('save')
        ->assertHasErrors('cityId');
});
