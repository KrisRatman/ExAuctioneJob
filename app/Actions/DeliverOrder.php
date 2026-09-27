<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Exceptions\AuctionException;
use App\Models\Order;
use App\Models\User;
use App\Notifications\OrderDelivered;
use Illuminate\Support\Facades\DB;

/**
 * Исполнитель отмечает работу сданной — заказчик проверяет результат.
 */
class DeliverOrder
{
    public function handle(User $executor, Order $order): Order
    {
        $order = DB::transaction(function () use ($executor, $order) {
            $order = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($order->executor_id !== $executor->id) {
                throw new AuctionException('Сдать работу может только исполнитель заказа.');
            }

            if ($order->status !== OrderStatus::InProgress) {
                throw new AuctionException('Сдать можно только заказ в работе.');
            }

            $order->update(['status' => OrderStatus::Delivered, 'delivered_at' => now()]);

            return $order;
        });

        $order->load(['customer', 'executor']);
        $order->customer->notify(new OrderDelivered($order));

        return $order;
    }
}
