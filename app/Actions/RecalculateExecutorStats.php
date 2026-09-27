<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Models\ExecutorProfile;
use App\Models\Order;
use App\Models\Review;

/**
 * Пересчитывает кэш в анкете исполнителя: средний рейтинг, число отзывов и выполненных заказов.
 * Заказы, закрытые через спор, в статистику не входят — там статус resolved.
 */
class RecalculateExecutorStats
{
    public function handle(int $executorId): void
    {
        $reviews = Review::query()->where('executor_id', $executorId);

        ExecutorProfile::query()->where('user_id', $executorId)->update([
            'rating_avg' => (clone $reviews)->avg('rating'),
            'reviews_count' => (clone $reviews)->count(),
            'completed_orders_count' => Order::query()
                ->where('executor_id', $executorId)
                ->where('status', OrderStatus::Completed)
                ->count(),
        ]);
    }
}
