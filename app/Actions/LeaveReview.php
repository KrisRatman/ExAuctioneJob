<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Exceptions\AuctionException;
use App\Models\Order;
use App\Models\Review;
use App\Models\User;
use App\Notifications\ReviewReceived;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Заказчик оценивает исполнителя после подтверждённого заказа — один раз на заказ.
 * Рейтинг в анкете пересчитывает ReviewObserver.
 */
class LeaveReview
{
    public function handle(User $customer, Order $order, int $rating, ?string $comment): Review
    {
        try {
            $review = DB::transaction(function () use ($customer, $order, $rating, $comment) {
                $order = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

                if ($order->customer_id !== $customer->id) {
                    throw new AuctionException('Оценить исполнителя может только автор заказа.');
                }

                // Спорные заказы (resolved) не оцениваются — они не смешиваются с обычной статистикой.
                if ($order->status !== OrderStatus::Completed) {
                    throw new AuctionException('Оценить можно только выполненный заказ.');
                }

                if ($order->review()->exists()) {
                    throw new AuctionException('Вы уже оценили этот заказ.');
                }

                return $order->review()->create([
                    'customer_id' => $customer->id,
                    'executor_id' => $order->executor_id,
                    'rating' => $rating,
                    'comment' => $comment,
                ]);
            });
        } catch (UniqueConstraintViolationException) {
            throw new AuctionException('Вы уже оценили этот заказ.');
        }

        $review->load(['executor', 'order']);
        $review->executor->notify(new ReviewReceived($review));

        return $review;
    }
}
