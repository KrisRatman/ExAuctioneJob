<?php

namespace App\Notifications;

use App\Models\Order;

/**
 * Исполнителю: заказчик принял работу.
 */
class OrderCompleted extends SiteNotification
{
    public function __construct(public Order $order) {}

    protected function title(): string
    {
        return 'Работа принята';
    }

    protected function body(): string
    {
        return 'Заказчик подтвердил выполнение «'.$this->order->title.'».';
    }

    protected function url(): string
    {
        return $this->order->conversation
            ? route('chats.show', $this->order->conversation)
            : route('executor.bids');
    }

    protected function icon(): string
    {
        return 'heroicon-o-trophy';
    }

    protected function extra(): array
    {
        return ['order_id' => $this->order->id];
    }
}
