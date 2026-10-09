<?php

namespace App\Support\Ai;

use App\Exceptions\AiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

/**
 * YandexGPT (Yandex AI Studio, Foundation Models API).
 * Эмбеддинги разные для документа и запроса: text-search-doc / text-search-query.
 */
class YandexGpt implements LanguageModel
{
    private const string BASE_URL = 'https://llm.api.cloud.yandex.net/foundationModels/v1';

    /**
     * @param  array{api_key: ?string, folder_id: ?string, model: string, temperature: float, max_tokens: int, timeout: int}  $config
     */
    public function __construct(private array $config) {}

    public function isAvailable(): bool
    {
        return filled($this->config['api_key']) && filled($this->config['folder_id']);
    }

    public function embeddingModel(): string
    {
        return 'yandexgpt:text-search';
    }

    public function embedDocuments(array $texts): array
    {
        // API принимает один текст на запрос.
        return array_map(fn (string $text) => $this->embed($text, 'text-search-doc'), $texts);
    }

    public function embedQuery(string $text): array
    {
        return $this->embed($text, 'text-search-query');
    }

    public function chat(array $messages): string
    {
        $response = $this->post('/completion', [
            'modelUri' => 'gpt://'.$this->config['folder_id'].'/'.$this->config['model'],
            'completionOptions' => [
                'stream' => false,
                'temperature' => $this->config['temperature'],
                'maxTokens' => (string) $this->config['max_tokens'],
            ],
            'messages' => array_map(fn (array $message) => [
                'role' => $message['role'],
                'text' => $message['content'],
            ], $messages),
        ]);

        $text = $response['result']['alternatives'][0]['message']['text'] ?? null;

        if (! is_string($text) || $text === '') {
            throw new AiException('YandexGPT вернул пустой ответ.');
        }

        return $text;
    }

    /** @return list<float> */
    private function embed(string $text, string $model): array
    {
        $response = $this->post('/textEmbedding', [
            'modelUri' => 'emb://'.$this->config['folder_id'].'/'.$model.'/latest',
            'text' => $text,
        ]);

        if (! isset($response['embedding']) || ! is_array($response['embedding'])) {
            throw new AiException('YandexGPT не вернул эмбеддинг.');
        }

        return array_map(floatval(...), $response['embedding']);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function post(string $path, array $payload): array
    {
        if (! $this->isAvailable()) {
            throw new AiException('YandexGPT не настроен: нужны YANDEXGPT_API_KEY и YANDEXGPT_FOLDER_ID.');
        }

        try {
            return $this->http()->post(self::BASE_URL.$path, $payload)->throw()->json() ?? [];
        } catch (ConnectionException|RequestException $e) {
            throw new AiException('YandexGPT недоступен: '.$e->getMessage(), previous: $e);
        }
    }

    private function http(): PendingRequest
    {
        return Http::timeout($this->config['timeout'])
            ->acceptJson()
            ->withHeaders([
                'Authorization' => 'Api-Key '.$this->config['api_key'],
                'x-folder-id' => $this->config['folder_id'],
            ]);
    }
}
