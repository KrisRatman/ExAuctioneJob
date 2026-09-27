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
        'Веб-разработка' => ['Вёрстка (HTML/CSS/JS)', 'WordPress', 'WooCommerce', 'Laravel', 'Другие CMS'],
        'Дизайн' => ['Веб-дизайн (Figma)', 'Логотипы', 'Баннеры и полиграфия'],
        'Копирайтинг' => ['Тексты для сайтов', 'SEO-тексты', 'Посты для соцсетей'],
        'Маркетинг' => ['SMM', 'Контекстная реклама', 'SEO-продвижение'],
        'Видео и аудио' => ['Монтаж видео', 'Озвучка', 'Обработка звука'],
        'Разработка игр' => ['Unity', '2D/3D-графика', 'Геймдизайн-документы'],
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
