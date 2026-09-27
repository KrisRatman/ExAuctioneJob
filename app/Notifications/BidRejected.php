<?php

namespace App\Notifications;

use App\Models\Order;

/**
 * Исполнителю: его предложение больше неактуально — выбран другой или заказ отменён.
 */
class BidRejected extends SiteNotification
{
    public const ReasonOtherExecutor = 'other_executor';

    public const ReasonCancelled = 'cancelled';

    public function __construct(public Order $order, public string $reason) {}

    protected function title(): string
    {
        return $this->reason === self::ReasonCancelled
            ? 'Заказ отменён'
            : 'Заказ отдан другому исполнителю';
    }

    protected function body(): string
    {
        return $this->reason === self::ReasonCancelled
            ? 'Заказчик снял «'.$this->order->title.'» с аукциона.'
            : 'По заказу «'.$this->order->title.'» заказчик выбрал другое предложение.';
    }

    protected function url(): string
    {
        return route('executor.bids');
    }

    protected function icon(): string
    {
        return 'heroicon-o-x-circle';
    }

    protected function extra(): array
    {
        return ['order_id' => $this->order->id];
    }
}
