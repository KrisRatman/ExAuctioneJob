<?php

namespace Database\Seeders;

use App\Models\City;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Стартовый список городов. Повторный запуск ничего не дублирует: запись ищется по slug.
 */
class CitySeeder extends Seeder
{
    /** @var list<string> */
    public const CITIES = [
        'Москва', 'Санкт-Петербург', 'Новосибирск', 'Екатеринбург', 'Казань',
        'Нижний Новгород', 'Челябинск', 'Красноярск', 'Самара', 'Уфа',
        'Ростов-на-Дону', 'Омск', 'Краснодар', 'Воронеж', 'Пермь',
        'Волгоград', 'Тюмень',
    ];

    public function run(): void
    {
        foreach (self::CITIES as $name) {
            City::query()->updateOrCreate(['slug' => Str::slug($name)], ['name' => $name]);
        }
    }
}
