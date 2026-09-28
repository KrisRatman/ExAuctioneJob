<?php

namespace Database\Factories;

use App\Models\City;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<City>
 */
class CityFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->city();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
        ];
    }

    /**
     * Город «по умолчанию» для фабрик заказов и анкет: первый в базе или новый.
     * Так заказ и исполнитель из фабрик по умолчанию оказываются в одном городе.
     */
    public static function defaultId(): int
    {
        return City::query()->orderBy('id')->value('id') ?? City::factory()->create()->id;
    }
}
