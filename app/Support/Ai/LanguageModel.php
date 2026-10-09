<?php

namespace App\Support\Ai;

use App\Exceptions\AiException;

/**
 * ИИ-провайдер: эмбеддинги для поиска по смыслу и чат для помощника.
 * Драйвер выбирается в config/ideajob.php (ai.driver).
 */
interface LanguageModel
{
    /** Ключи на месте — ИИ-функции можно показывать. */
    public function isAvailable(): bool;

    /** Идентификатор модели эмбеддингов: векторы разных моделей несравнимы. */
    public function embeddingModel(): string;

    /**
     * Векторы для индексируемых текстов (заказы, теги).
     *
     * @param  list<string>  $texts
     * @return list<list<float>>
     *
     * @throws AiException
     */
    public function embedDocuments(array $texts): array;

    /**
     * Вектор для поискового запроса пользователя.
     *
     * @return list<float>
     *
     * @throws AiException
     */
    public function embedQuery(string $text): array;

    /**
     * Ответ модели на диалог.
     *
     * @param  list<array{role: 'system'|'user'|'assistant', content: string}>  $messages
     *
     * @throws AiException
     */
    public function chat(array $messages): string;
}
