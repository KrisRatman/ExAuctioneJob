<?php

namespace App\Support\Ai;

use App\Exceptions\AiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * GigaChat API (Сбер). Ключ авторизации меняется на токен доступа на 30 минут — токен кэшируется.
 */
class GigaChat implements LanguageModel
{
    private const string OAUTH_URL = 'https://ngw.devices.sberbank.ru:9443/api/v2/oauth';

    private const string BASE_URL = 'https://gigachat.devices.sberbank.ru/api/v1';

    /**
     * @param  array{auth_key: ?string, scope: string, model: string, embedding_model: string, ca_bundle: ?string, temperature: float, max_tokens: int, timeout: int}  $config
     */
    public function __construct(private array $config) {}

    public function isAvailable(): bool
    {
        return filled($this->config['auth_key']);
    }

    public function embeddingModel(): string
    {
        return 'gigachat:'.$this->config['embedding_model'];
    }

    public function embedDocuments(array $texts): array
    {
        if ($texts === []) {
            return [];
        }

        $response = $this->post('/embeddings', [
            'model' => $this->config['embedding_model'],
            'input' => array_values($texts),
        ]);

        $vectors = collect($response['data'] ?? [])->sortBy('index')->pluck('embedding')->values();

        if ($vectors->count() !== count($texts)) {
            throw new AiException('GigaChat вернул не все эмбеддинги.');
        }

        return $vectors->map(fn (array $vector) => array_map(floatval(...), $vector))->all();
    }

    public function embedQuery(string $text): array
    {
        return $this->embedDocuments([$text])[0];
    }

    public function chat(array $messages): string
    {
        $response = $this->post('/chat/completions', [
            'model' => $this->config['model'],
            'messages' => $messages,
            'temperature' => $this->config['temperature'],
            'max_tokens' => $this->config['max_tokens'],
        ]);

        $text = $response['choices'][0]['message']['content'] ?? null;

        if (! is_string($text) || $text === '') {
            throw new AiException('GigaChat вернул пустой ответ.');
        }

        return $text;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function post(string $path, array $payload): array
    {
        try {
            return $this->http()
                ->withToken($this->accessToken())
                ->post(self::BASE_URL.$path, $payload)
                ->throw()
                ->json() ?? [];
        } catch (ConnectionException|RequestException $e) {
            throw new AiException('GigaChat недоступен: '.$e->getMessage(), previous: $e);
        }
    }

    /**
     * @throws ConnectionException
     * @throws RequestException
     */
    private function accessToken(): string
    {
        if (! $this->isAvailable()) {
            throw new AiException('GigaChat не настроен: нужен GIGACHAT_AUTH_KEY.');
        }

        $cacheKey = 'ai:gigachat-token:'.sha1((string) $this->config['auth_key']);

        if ($token = Cache::get($cacheKey)) {
            return $token;
        }

        $response = $this->http()
            ->asForm()
            ->withHeaders([
                'Authorization' => 'Basic '.$this->config['auth_key'],
                'RqUID' => (string) Str::uuid(),
            ])
            ->post(self::OAUTH_URL, ['scope' => $this->config['scope']])
            ->throw()
            ->json();

        $token = $response['access_token'] ?? null;

        if (! is_string($token) || $token === '') {
            throw new AiException('GigaChat не выдал токен доступа.');
        }

        // expires_at — в миллисекундах; минута запаса.
        $expiresAt = isset($response['expires_at']) ? (int) ($response['expires_at'] / 1000) : time() + 1800;
        Cache::put($cacheKey, $token, max(60, $expiresAt - time() - 60));

        return $token;
    }

    private function http(): PendingRequest
    {
        return Http::timeout($this->config['timeout'])
            ->acceptJson()
            ->when(filled($this->config['ca_bundle']), fn (PendingRequest $request) => $request->withOptions([
                'verify' => $this->config['ca_bundle'],
            ]));
    }
}
