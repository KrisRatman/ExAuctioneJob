<?php

namespace App\Support\Ai;

use App\Models\Embedding;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * Модель, по тексту которой ищут по смыслу.
 */
interface Embeddable
{
    /** Текст для эмбеддинга; null — запись не индексируется. */
    public function embeddingText(): ?string;

    /**
     * Поля, при изменении которых вектор надо пересчитать.
     *
     * @return list<string>
     */
    public function embeddingAttributes(): array;

    /** @return MorphOne<Embedding, $this> */
    public function embedding(): MorphOne;
}
