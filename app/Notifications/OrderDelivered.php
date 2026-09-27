<?php

namespace App\Notifications;

use App\Models\Order;

/**
 * Заказчику: исполнитель сдал работу — пора проверить.
 */
class OrderDelivered extends SiteNotification
{
    public function __construct(public Order $order) {}

    protected function title(): string
    {
        return 'Работа сдана — проверьте';
    }

    protected function body(): string
    {
        return '«'.$this->order->title.'» — исполнитель '.$this->order->executor->name.'. Подтвердите выполнение или откройте спор.';
    }

    protected function url(): string
    {
        return route('customer.orders.show', $this->order);
    }

    protected function icon(): string
    {
        return 'heroicon-o-inbox-arrow-down';
    }

    protected function extra(): array
    {
        return ['order_id' => $this->order->id];
    }
}
