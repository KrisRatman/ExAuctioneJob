<?php

namespace Tests\Fakes;

use App\Exceptions\AiException;
use App\Support\Ai\LanguageModel;

/**
 * ИИ без сети: «эмбеддинг» — мешок основ слов (первые 5 букв), так что тексты с общими словами
 * похожи, а «холодильник» и «холодильника» совпадают. Чат возвращает заготовленный ответ и запоминает запросы.
 */
class FakeLanguageModel implements LanguageModel
{
    private const int DIMENSIONS = 512;

    public int $embeddedDocuments = 0;

    /** @var list<list<array{role: string, content: string}>> */
    public array $chats = [];

    public string $reply = 'Вам нужен мастер по ремонту холодильников.';

    public bool $failing = false;

    public function isAvailable(): bool
    {
        return true;
    }

    public function embeddingModel(): string
    {
        return 'fake';
    }

    public function embedDocuments(array $texts): array
    {
        $this->guard();
        $this->embeddedDocuments += count($texts);

        return array_map($this->vector(...), $texts);
    }

    public function embedQuery(string $text): array
    {
        $this->guard();

        return $this->vector($text);
    }

    public function chat(array $messages): string
    {
        $this->guard();
        $this->chats[] = $messages;

        return $this->reply;
    }

    /** Системное сообщение последнего запроса к чату: инструкции + найденная справка. */
    public function lastSystemPrompt(): string
    {
        return end($this->chats)[0]['content'] ?? '';
    }

    private function guard(): void
    {
        if ($this->failing) {
            throw new AiException('Провайдер недоступен.');
        }
    }

    /** @return list<float> */
    private function vector(string $text): array
    {
        $vector = array_fill(0, self::DIMENSIONS, 0.0);

        preg_match_all('/\p{L}{3,}/u', mb_strtolower($text), $words);

        foreach (array_unique(array_map(fn (string $word) => mb_substr($word, 0, 5), $words[0])) as $stem) {
            $vector[crc32($stem) % self::DIMENSIONS] = 1.0;
        }

        return $vector;
    }
}
