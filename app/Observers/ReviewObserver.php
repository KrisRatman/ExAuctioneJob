<?php

namespace App\Observers;

use App\Actions\RecalculateExecutorStats;
use App\Models\Review;

/**
 * Рейтинг в анкете исполнителя — кэш: пересчитывается, когда отзыв появился или удалён модератором.
 */
class ReviewObserver
{
    public function __construct(private RecalculateExecutorStats $recalculate) {}

    public function created(Review $review): void
    {
        $this->recalculate->handle($review->executor_id);
    }

    public function deleted(Review $review): void
    {
        $this->recalculate->handle($review->executor_id);
    }
}
