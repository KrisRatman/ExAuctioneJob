<?php

namespace App\Jobs;

use App\Models\Embedding;
use App\Support\Ai\Embeddable;
use App\Support\Ai\SemanticIndex;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Пересчитать вектор записи в очереди: запрос к ИИ не тормозит сохранение заказа.
 * Хранит класс и id, а не модель: запись могли удалить, пока задача ждала.
 */
class SyncEmbedding implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [30, 120];

    /**
     * @param  class-string<Model&Embeddable>  $class
     */
    public function __construct(public string $class, public int $id)
    {
        $this->afterCommit();
    }

    public function handle(SemanticIndex $index): void
    {
        if (! $index->isEnabled()) {
            return;
        }

        $record = $this->class::query()->find($this->id);

        if ($record === null) {
            Embedding::query()
                ->where('embeddable_type', (new $this->class)->getMorphClass())
                ->where('embeddable_id', $this->id)
                ->delete();

            return;
        }

        $index->sync($record);
    }
}
