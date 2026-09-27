<?php

namespace App\Actions;

use App\Enums\BidStatus;
use App\Enums\OrderStatus;
use App\Exceptions\AuctionException;
use App\Models\Order;
use App\Models\User;
use App\Notifications\BidRejected;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Заказчик снимает заказ с аукциона. Отменить можно только пока исполнитель не выбран.
 * Откликнувшиеся исполнители получают уведомление.
 */
class CancelOrder
{
    public function handle(User $customer, Order $order): Order
    {
        [$order, $rejectedExecutorIds] = DB::transaction(function () use ($customer, $order) {
            $order = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($order->customer_id !== $customer->id) {
                throw new AuctionException('Отменить заказ может только его автор.');
            }

            if (! $order->isOpen()) {
                throw new AuctionException('Отменить можно только заказ, по которому ещё не выбран исполнитель.');
            }

            $order->update(['status' => OrderStatus::Cancelled, 'cancelled_at' => now()]);

            $pendingBids = $order->bids()->where('status', BidStatus::Pending);
            $rejectedExecutorIds = (clone $pendingBids)->pluck('executor_id');
            $pendingBids->update(['status' => BidStatus::Rejected, 'updated_at' => now()]);

            return [$order, $rejectedExecutorIds];
        });

        Notification::send(
            User::query()->whereKey($rejectedExecutorIds)->get(),
            new BidRejected($order, BidRejected::ReasonCancelled),
        );

        return $order;
    }
}
