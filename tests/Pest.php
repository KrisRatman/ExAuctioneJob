<?php

use App\Models\Category;
use App\Support\Ai\LanguageModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Fakes\FakeLanguageModel;
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

/** Включить ИИ с фейковым провайдером (без сети). */
function fakeAi(): FakeLanguageModel
{
    $fake = new FakeLanguageModel;
    app()->instance(LanguageModel::class, $fake);

    // В тестах пороги ниже: у фейковых векторов сходство грубее, чем у настоящих.
    config(['ideajob.ai.tag_min_similarity' => 0.2, 'ideajob.ai.search_min_similarity' => 0.2]);

    return $fake;
}

/** Тег (подкатегория) в новом разделе. */
function tag(string $name = 'Laravel'): Category
{
    return Category::factory()->tag()->create(['name' => $name]);
}
