<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Стартовое дерево тегов. Повторный запуск ничего не дублирует: запись ищется по slug.
 */
class CategorySeeder extends Seeder
{
    /** @var array<string, list<string>> */
    public const TREE = [
        'Ремонт и отделка' => ['Укладка плитки', 'Малярные работы', 'Штукатурка и шпаклёвка', 'Напольные покрытия', 'Натяжные потолки', 'Ремонт под ключ'],
        'Сантехника' => ['Установка сантехники', 'Засоры и протечки', 'Отопление и водонагреватели'],
        'Электрика' => ['Электромонтаж', 'Розетки и светильники'],
        'Ремонт техники' => ['Холодильники', 'Стиральные машины', 'Посудомоечные машины', 'Плиты и духовки', 'Кондиционеры'],
        'Мебель' => ['Сборка мебели', 'Ремонт мебели', 'Мебель на заказ'],
        'Мастер на час' => ['Мелкий бытовой ремонт', 'Навеска полок и карнизов', 'Замки и двери'],
        'Уборка' => ['Уборка квартир', 'Уборка после ремонта', 'Химчистка мебели и ковров'],
        'Переезды' => ['Грузчики', 'Грузоперевозки', 'Вывоз мусора'],
        'Прочее' => ['Другое'],
    ];

    public function run(): void
    {
        $rootSort = 0;

        foreach (self::TREE as $rootName => $tags) {
            $root = Category::query()->updateOrCreate(
                ['slug' => Str::slug($rootName)],
                ['name' => $rootName, 'parent_id' => null, 'sort' => $rootSort += 10],
            );

            foreach ($tags as $index => $tagName) {
                Category::query()->updateOrCreate(
                    ['slug' => Str::slug($tagName)],
                    ['name' => $tagName, 'parent_id' => $root->id, 'sort' => ($index + 1) * 10],
                );
            }
        }
    }
}
