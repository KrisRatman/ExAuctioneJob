<?php

namespace App\Actions;

use App\Enums\DisputeResolution;
use App\Enums\DisputeStatus;
use App\Enums\OrderStatus;
use App\Exceptions\AuctionException;
use App\Models\Dispute;
use App\Models\Order;
use App\Models\User;
use App\Notifications\DisputeResolved;
use Illuminate\Support\Facades\DB;

/**
 * Администратор закрывает спор: вернуть в работу, засчитать выполненным или отменить заказ.
 * Обе стороны получают уведомление с решением.
 */
class ResolveDispute
{
    public function handle(User $admin, Dispute $dispute, DisputeResolution $resolution, string $comment): Dispute
    {
        $dispute = DB::transaction(function () use ($admin, $dispute, $resolution, $comment) {
            $order = Order::query()->whereKey($dispute->order_id)->lockForUpdate()->firstOrFail();
            $dispute = Dispute::query()->whereKey($dispute->id)->lockForUpdate()->firstOrFail();

            if (! $admin->isAdmin()) {
                throw new AuctionException('Решать споры может только администратор.');
            }

            if (! $dispute->isOpen() || $order->status !== OrderStatus::Disputed) {
                throw new AuctionException('Этот спор уже решён.');
            }

            $dispute->update([
                'status' => DisputeStatus::Resolved,
                'resolution' => $resolution,
                'resolution_comment' => $comment,
                'resolved_by' => $admin->id,
                'resolved_at' => now(),
            ]);

            $order->update([
                'status' => $resolution->orderStatus(),
                // Вернули в работу — исполнитель сдаст заново.
                'delivered_at' => $resolution === DisputeResolution::ReturnToWork ? null : $order->delivered_at,
                'cancelled_at' => $resolution === DisputeResolution::Cancelled ? now() : null,
            ]);

            return $dispute;
        });

        $dispute->load(['order.customer', 'order.executor', 'order.conversation']);
        $dispute->order->customer->notify(new DisputeResolved($dispute));
        $dispute->order->executor->notify(new DisputeResolved($dispute));

        return $dispute;
    }
}
