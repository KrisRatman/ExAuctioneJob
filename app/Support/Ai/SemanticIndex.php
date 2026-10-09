<?php

namespace App\Support\Ai;

use App\Exceptions\AiException;
use App\Models\Embedding;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Векторный индекс поверх таблицы embeddings: индексация моделей и ранжирование по запросу.
 * Векторы хранятся нормированными — косинусное сходство сводится к скалярному произведению.
 */
class SemanticIndex
{
    public function __construct(private LanguageModel $model) {}

    public function isEnabled(): bool
    {
        return $this->model->isAvailable();
    }

    /**
     * Пересчитать вектор записи, если изменился её текст или модель эмбеддингов.
     *
     * @throws AiException
     */
    public function sync(Model&Embeddable $record): void
    {
        $this->syncMany([$record]);
    }

    /**
     * То же для пачки записей — эмбеддинги запрашиваются одним вызовом.
     *
     * @param  iterable<Model&Embeddable>  $records
     * @return int Сколько векторов пересчитано.
     *
     * @throws AiException
     */
    public function syncMany(iterable $records): int
    {
        $pending = [];

        foreach ($records as $record) {
            $text = $record->embeddingText();

            if ($text === null) {
                $record->embedding()->delete();

                continue;
            }

            $existing = $record->embedding()->first(['id', 'model', 'content_hash']);

            if ($existing?->content_hash === sha1($text) && $existing->model === $this->model->embeddingModel()) {
                continue;
            }

            $pending[] = [$record, $text];
        }

        if ($pending === []) {
            return 0;
        }

        $vectors = $this->model->embedDocuments(array_column($pending, 1));

        foreach ($pending as $i => [$record, $text]) {
            Embedding::query()->updateOrCreate(
                ['embeddable_type' => $record->getMorphClass(), 'embeddable_id' => $record->getKey()],
                [
                    'model' => $this->model->embeddingModel(),
                    'content_hash' => sha1($text),
                    'vector' => self::normalize($vectors[$i]),
                ],
            );
        }

        return count($pending);
    }

    /**
     * Записи класса $class, ближайшие по смыслу к запросу.
     *
     * @param  class-string<Model&Embeddable>  $class
     * @param  iterable<int>|null  $ids  Искать только среди этих записей.
     * @return array<int, float> id => сходство, от самого похожего.
     *
     * @throws AiException
     */
    public function rank(string $query, string $class, ?iterable $ids = null, float $minSimilarity = 0.0, ?int $limit = null): array
    {
        $query = trim($query);

        if ($query === '' || ($ids !== null && collect($ids)->isEmpty())) {
            return [];
        }

        $queryVector = $this->queryVector($query);

        $scores = Embedding::query()
            ->where('embeddable_type', (new $class)->getMorphClass())
            ->where('model', $this->model->embeddingModel())
            ->when($ids !== null, fn ($q) => $q->whereIn('embeddable_id', collect($ids)->all()))
            ->get(['embeddable_id', 'vector'])
            ->mapWithKeys(fn (Embedding $embedding) => [
                $embedding->embeddable_id => self::dot($queryVector, $embedding->vector),
            ])
            ->filter(fn (float $score) => $score >= $minSimilarity)
            ->sortDesc();

        return ($limit !== null ? $scores->take($limit) : $scores)->all();
    }

    /**
     * Вектор запроса кэшируется: Livewire пересчитывает выдачу на каждом запросе страницы.
     *
     * @return list<float>
     */
    private function queryVector(string $query): array
    {
        $key = 'ai:query:'.sha1($this->model->embeddingModel().'|'.mb_strtolower($query));

        return Cache::remember($key, now()->addDay(), fn () => self::normalize($this->model->embedQuery($query)));
    }

    /**
     * @param  list<float>  $vector
     * @return list<float>
     */
    public static function normalize(array $vector): array
    {
        $length = sqrt(array_sum(array_map(fn (float $x) => $x * $x, $vector)));

        if ($length == 0.0) {
            return $vector;
        }

        // 6 знаков хватает для сравнения и вдвое сокращает JSON.
        return array_map(fn (float $x) => round($x / $length, 6), $vector);
    }

    /**
     * @param  list<float>  $a
     * @param  list<float>  $b
     */
    private static function dot(array $a, array $b): float
    {
        if (count($a) !== count($b)) {
            return 0.0;
        }

        $sum = 0.0;

        foreach ($a as $i => $value) {
            $sum += $value * $b[$i];
        }

        return $sum;
    }
}
