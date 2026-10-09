<?php

namespace App\Observers;

use App\Jobs\SyncEmbedding;
use App\Support\Ai\Embeddable;
use App\Support\Ai\LanguageModel;
use Illuminate\Database\Eloquent\Model;

/**
 * Держит векторный индекс в актуальном виде: текст изменился — вектор пересчитывается в очереди.
 * ИИ выключен — ничего не делает, индекс потом собирает ideajob:ai-index.
 */
class EmbeddingObserver
{
    public function __construct(private LanguageModel $model) {}

    public function saved(Model&Embeddable $record): void
    {
        if (! $this->model->isAvailable()) {
            return;
        }

        if ($record->wasRecentlyCreated || $record->wasChanged($record->embeddingAttributes())) {
            SyncEmbedding::dispatch($record::class, $record->getKey());
        }
    }

    public function deleted(Model&Embeddable $record): void
    {
        $record->embedding()->delete();
    }
}
