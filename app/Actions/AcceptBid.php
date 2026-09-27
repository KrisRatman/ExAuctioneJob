<?php

namespace App\Actions;

use App\Enums\BidStatus;
use App\Enums\OrderStatus;
use App\Exceptions\AuctionException;
use App\Models\Bid;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Заказчик выбирает исполнителя: заказ уходит в работу, остальные предложения отклоняются.
 */
class AcceptBid
{
    public function handle(User $customer, Bid $bid): Order
    {
        return DB::transaction(function () use ($customer, $bid) {
            $order = Order::query()->whereKey($bid->order_id)->lockForUpdate()->firstOrFail();

            if ($order->customer_id !== $customer->id) {
                throw new AuctionException('Принять исполнителя может только автор заказа.');
            }

            if (! $order->isOpen()) {
                throw new AuctionException('Исполнитель по этому заказу уже выбран или заказ закрыт.');
            }

            $bid = Bid::query()->whereKey($bid->id)->where('order_id', $order->id)->firstOrFail();

            if ($bid->status !== BidStatus::Pending) {
                throw new AuctionException('Это предложение уже неактуально.');
            }

            $order->update([
                'executor_id' => $bid->executor_id,
                'status' => OrderStatus::InProgress,
                'accepted_at' => now(),
            ]);

            $bid->update(['status' => BidStatus::Accepted]);

            $order->bids()
                ->whereKeyNot($bid->id)
                ->where('status', BidStatus::Pending)
                ->update(['status' => BidStatus::Rejected, 'updated_at' => now()]);

            return $order;
        });
    }
}
