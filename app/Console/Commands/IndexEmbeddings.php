<?php

namespace App\Console\Commands;

use App\Exceptions\AiException;
use App\Models\Category;
use App\Models\Embedding;
use App\Models\Order;
use App\Support\Ai\SemanticIndex;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Собрать векторный индекс тегов и заказов. Неизменившиеся записи пропускаются (sha1 текста),
 * поэтому команду можно запускать сколько угодно — например, после смены драйвера ИИ.
 */
#[Signature('ideajob:ai-index')]
#[Description('Пересчитать векторы тегов и заказов для поиска по смыслу')]
class IndexEmbeddings extends Command
{
    public function handle(SemanticIndex $index): int
    {
        if (! $index->isEnabled()) {
            $this->components->error('ИИ не настроен: укажите IDEAJOB_AI_DRIVER и ключи провайдера в .env.');

            return self::FAILURE;
        }

        try {
            $tags = 0;
            Category::query()->tags()->with('parent')->chunkById(50, function ($chunk) use ($index, &$tags) {
                $tags += $index->syncMany($chunk);
            });

            $orders = 0;
            Order::query()->chunkById(50, function ($chunk) use ($index, &$orders) {
                $orders += $index->syncMany($chunk);
            });
        } catch (AiException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        // Векторы удалённых записей и разделов, которые раньше были тегами.
        $orphans = Embedding::query()
            ->where(fn ($q) => $q
                ->where('embeddable_type', (new Category)->getMorphClass())
                ->whereNotIn('embeddable_id', Category::query()->tags()->select('id')))
            ->orWhere(fn ($q) => $q
                ->where('embeddable_type', (new Order)->getMorphClass())
                ->whereNotIn('embeddable_id', Order::query()->select('id')))
            ->delete();

        $this->components->info("Пересчитано: тегов — {$tags}, заказов — {$orders}. Удалено лишних векторов: {$orphans}.");

        return self::SUCCESS;
    }
}
