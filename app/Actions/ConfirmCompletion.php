<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Exceptions\AuctionException;
use App\Models\Order;
use App\Models\User;
use App\Notifications\OrderCompleted;
use Illuminate\Support\Facades\DB;

/**
 * Заказчик принимает работу: заказ выполнен, у исполнителя растёт счётчик выполненных заказов.
 */
class ConfirmCompletion
{
    public function __construct(private RecalculateExecutorStats $recalculate) {}

    public function handle(User $customer, Order $order): Order
    {
        $order = DB::transaction(function () use ($customer, $order) {
            $order = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($order->customer_id !== $customer->id) {
                throw new AuctionException('Принять работу может только автор заказа.');
            }

            if ($order->status !== OrderStatus::Delivered) {
                throw new AuctionException('Подтвердить можно только сданную работу.');
            }

            $order->update(['status' => OrderStatus::Completed, 'completed_at' => now()]);

            return $order;
        });

        $this->recalculate->handle($order->executor_id);

        $order->load(['executor', 'conversation']);
        $order->executor->notify(new OrderCompleted($order));

        return $order;
    }
}
