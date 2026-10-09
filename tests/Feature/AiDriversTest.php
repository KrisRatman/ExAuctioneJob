<?php

use App\Exceptions\AiException;
use App\Support\Ai\DisabledLanguageModel;
use App\Support\Ai\GigaChat;
use App\Support\Ai\LanguageModel;
use App\Support\Ai\YandexGpt;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

function yandexGpt(array $overrides = []): YandexGpt
{
    return new YandexGpt([...config('services.yandexgpt'), 'api_key' => 'key', 'folder_id' => 'b1gfolder', ...$overrides]);
}

function gigaChat(array $overrides = []): GigaChat
{
    return new GigaChat([...config('services.gigachat'), 'auth_key' => 'basic-key', ...$overrides]);
}

it('picks the driver from config', function (?string $driver, string $class) {
    config(['ideajob.ai.driver' => $driver]);
    app()->forgetInstance(LanguageModel::class);

    expect(app(LanguageModel::class))->toBeInstanceOf($class);
})->with([
    'off' => [null, DisabledLanguageModel::class],
    'yandex' => ['yandexgpt', YandexGpt::class],
    'giga' => ['gigachat', GigaChat::class],
]);

it('is unavailable without keys', function () {
    expect(yandexGpt(['api_key' => null])->isAvailable())->toBeFalse()
        ->and(yandexGpt(['folder_id' => ''])->isAvailable())->toBeFalse()
        ->and(gigaChat(['auth_key' => null])->isAvailable())->toBeFalse()
        ->and(yandexGpt()->isAvailable())->toBeTrue();

    yandexGpt(['api_key' => null])->chat([['role' => 'user', 'content' => 'Привет']]);
})->throws(AiException::class);

describe('YandexGPT', function () {
    it('sends a completion request', function () {
        Http::fake(['llm.api.cloud.yandex.net/*' => Http::response([
            'result' => ['alternatives' => [['message' => ['role' => 'assistant', 'text' => 'Нужен сантехник.']]]],
        ])]);

        $answer = yandexGpt()->chat([
            ['role' => 'system', 'content' => 'Правила'],
            ['role' => 'user', 'content' => 'Течёт кран'],
        ]);

        expect($answer)->toBe('Нужен сантехник.');
        Http::assertSent(fn (Request $request) => $request->url() === 'https://llm.api.cloud.yandex.net/foundationModels/v1/completion'
            && $request->hasHeader('Authorization', 'Api-Key key')
            && $request['modelUri'] === 'gpt://b1gfolder/yandexgpt-lite/latest'
            && $request['messages'] === [['role' => 'system', 'text' => 'Правила'], ['role' => 'user', 'text' => 'Течёт кран']]);
    });

    it('uses different embedding models for documents and queries', function () {
        Http::fake(['llm.api.cloud.yandex.net/*' => Http::response(['embedding' => [0.1, 0.2]])]);

        expect(yandexGpt()->embedDocuments(['Заказ 1', 'Заказ 2']))->toBe([[0.1, 0.2], [0.1, 0.2]])
            ->and(yandexGpt()->embedQuery('запрос'))->toBe([0.1, 0.2]);

        Http::assertSentCount(3);
        Http::assertSent(fn (Request $request) => $request['modelUri'] === 'emb://b1gfolder/text-search-doc/latest' && $request['text'] === 'Заказ 1');
        Http::assertSent(fn (Request $request) => $request['modelUri'] === 'emb://b1gfolder/text-search-query/latest');
    });

    it('wraps http errors', function () {
        Http::fake(['*' => Http::response(['error' => 'quota'], 429)]);

        yandexGpt()->embedQuery('запрос');
    })->throws(AiException::class, 'YandexGPT недоступен');
});

describe('GigaChat', function () {
    beforeEach(function () {
        Http::fake([
            'ngw.devices.sberbank.ru:9443/*' => Http::response(['access_token' => 'token-1', 'expires_at' => (time() + 1800) * 1000]),
            'gigachat.devices.sberbank.ru/api/v1/chat/completions' => Http::response([
                'choices' => [['message' => ['role' => 'assistant', 'content' => 'Нужен электрик.']]],
            ]),
            'gigachat.devices.sberbank.ru/api/v1/embeddings' => Http::response(['data' => [
                ['embedding' => [0.3], 'index' => 1],
                ['embedding' => [0.1], 'index' => 0],
            ]]),
        ]);
    });

    it('exchanges the auth key for a cached access token', function () {
        $giga = gigaChat();

        expect($giga->chat([['role' => 'user', 'content' => 'Искрит розетка']]))->toBe('Нужен электрик.');
        $giga->chat([['role' => 'user', 'content' => 'Ещё вопрос']]);

        Http::assertSent(fn (Request $request) => str_contains($request->url(), '/api/v2/oauth')
            && $request->hasHeader('Authorization', 'Basic basic-key')
            && $request->hasHeader('RqUID')
            && $request['scope'] === 'GIGACHAT_API_PERS');
        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/chat/completions')
            && $request->hasHeader('Authorization', 'Bearer token-1')
            && $request['messages'] === [['role' => 'user', 'content' => 'Искрит розетка']]);
        // Токен запрошен один раз на два вопроса.
        expect(Http::recorded(fn (Request $request) => str_contains($request->url(), '/oauth')))->toHaveCount(1);
    });

    it('returns embeddings in input order with one request', function () {
        expect(gigaChat()->embedDocuments(['первый', 'второй']))->toBe([[0.1], [0.3]]);

        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/embeddings')
            && $request['input'] === ['первый', 'второй']
            && $request['model'] === 'Embeddings');
    });
});
