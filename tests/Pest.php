<?php

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Feature-тесты работают с приложением и базой, Unit — чистый PHP.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/** Тег (подкатегория) в новом разделе. */
function tag(string $name = 'Laravel'): Category
{
    return Category::factory()->tag()->create(['name' => $name]);
}
