<?php

namespace App\Actions;

use App\Enums\BidStatus;
use App\Enums\OrderStatus;
use App\Exceptions\AuctionException;
use App\Models\Bid;
use App\Models\Conversation;
use App\Models\Order;
use App\Models\User;
use App\Notifications\BidRejected;
use App\Notifications\ExecutorAccepted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Заказчик выбирает исполнителя: заказ уходит в работу, остальные предложения отклоняются,
 * открывается чат. Исполнитель и отклонённые получают уведомления.
 */
class AcceptBid
{
    public function handle(User $customer, Bid $bid): Conversation
    {
        [$conversation, $rejectedExecutorIds] = DB::transaction(function () use ($customer, $bid) {
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

            $otherBids = $order->bids()->whereKeyNot($bid->id)->where('status', BidStatus::Pending);
            $rejectedExecutorIds = (clone $otherBids)->pluck('executor_id');
            $otherBids->update(['status' => BidStatus::Rejected, 'updated_at' => now()]);

            $conversation = Conversation::query()->firstOrCreate(
                ['order_id' => $order->id],
                ['customer_id' => $order->customer_id, 'executor_id' => $bid->executor_id],
            );

            return [$conversation, $rejectedExecutorIds];
        });

        $conversation->load(['order', 'customer', 'executor']);

        $conversation->executor->notify(new ExecutorAccepted($conversation));
        Notification::send(
            User::query()->whereKey($rejectedExecutorIds)->get(),
            new BidRejected($conversation->order, BidRejected::ReasonOtherExecutor),
        );

        return $conversation;
    }
}
