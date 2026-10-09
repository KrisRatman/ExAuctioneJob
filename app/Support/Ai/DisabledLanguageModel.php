<?php

namespace App\Support\Ai;

use App\Exceptions\AiException;

/**
 * ИИ не настроен: функции скрыты, поиск работает по совпадению слов.
 */
class DisabledLanguageModel implements LanguageModel
{
    public function isAvailable(): bool
    {
        return false;
    }

    public function embeddingModel(): string
    {
        return 'disabled';
    }

    public function embedDocuments(array $texts): array
    {
        throw new AiException('ИИ не настроен.');
    }

    public function embedQuery(string $text): array
    {
        throw new AiException('ИИ не настроен.');
    }

    public function chat(array $messages): string
    {
        throw new AiException('ИИ не настроен.');
    }
}
