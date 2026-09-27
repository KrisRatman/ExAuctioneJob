<?php

namespace App\Actions;

use App\Enums\DisputeStatus;
use App\Enums\OrderStatus;
use App\Exceptions\AuctionException;
use App\Models\Dispute;
use App\Models\Order;
use App\Models\User;
use App\Notifications\DisputeOpened;
use Illuminate\Support\Facades\DB;

/**
 * Заказчик не согласен со сданной работой — спор уходит администратору.
 */
class OpenDispute
{
    public function handle(User $customer, Order $order, string $reason): Dispute
    {
        $dispute = DB::transaction(function () use ($customer, $order, $reason) {
            $order = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($order->customer_id !== $customer->id) {
                throw new AuctionException('Открыть спор может только автор заказа.');
            }

            if ($order->status !== OrderStatus::Delivered) {
                throw new AuctionException('Спор можно открыть только по сданной работе.');
            }

            $order->update(['status' => OrderStatus::Disputed]);

            return $order->disputes()->create([
                'opened_by' => $customer->id,
                'reason' => $reason,
                'status' => DisputeStatus::Open,
            ]);
        });

        $dispute->load(['order.executor', 'order.conversation']);
        $dispute->order->executor->notify(new DisputeOpened($dispute));

        return $dispute;
    }
}
