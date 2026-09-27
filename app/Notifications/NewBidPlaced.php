<?php

namespace App\Notifications;

use App\Models\Bid;

/**
 * Заказчику: по его заказу пришло новое предложение.
 */
class NewBidPlaced extends SiteNotification
{
    public function __construct(public Bid $bid) {}

    protected function title(): string
    {
        return 'Новое предложение: '.rub($this->bid->offer_price);
    }

    protected function body(): string
    {
        return $this->bid->executor->name.' предлагает сделать «'.$this->bid->order->title.'» за '
            .$this->bid->duration_days.' '.plural($this->bid->duration_days, 'день', 'дня', 'дней').'.';
    }

    protected function url(): string
    {
        return route('customer.orders.show', $this->bid->order_id);
    }

    protected function icon(): string
    {
        return 'heroicon-o-hand-raised';
    }

    protected function extra(): array
    {
        return ['order_id' => $this->bid->order_id];
    }
}
