<?php

namespace App\Actions;

use App\Enums\BidStatus;
use App\Exceptions\AuctionException;
use App\Models\Bid;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Исполнитель делает предложение по заказу или правит своё, пока заказ на аукционе.
 */
class PlaceBid
{
    /**
     * @param  array{offer_price: int, approach_description: string, duration_days: int}  $data
     */
    public function handle(User $executor, Order $order, array $data): Bid
    {
        return DB::transaction(function () use ($executor, $order, $data) {
            // Блокировка заказа: параллельное принятие исполнителя не пропустит ставку в закрытый заказ.
            $order = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if (! $order->isOpen()) {
                throw AuctionException::orderClosed();
            }

            if (! $executor->isExecutor() || ! Order::query()->whereKey($order->id)->matchingExecutor($executor)->exists()) {
                throw new AuctionException('Этот заказ не подходит под теги вашего профиля.');
            }

            if ($data['offer_price'] > $order->maxOfferPrice()) {
                throw AuctionException::priceTooHigh($order->maxOfferPrice());
            }

            return Bid::query()->updateOrCreate(
                ['order_id' => $order->id, 'executor_id' => $executor->id],
                [
                    'offer_price' => $data['offer_price'],
                    'approach_description' => $data['approach_description'],
                    'duration_days' => $data['duration_days'],
                    'status' => BidStatus::Pending,
                ],
            );
        });
    }
}
