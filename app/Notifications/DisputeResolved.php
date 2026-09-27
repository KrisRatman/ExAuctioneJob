<?php

namespace App\Notifications;

use App\Models\Dispute;

/**
 * Обеим сторонам: администратор решил спор.
 */
class DisputeResolved extends SiteNotification
{
    public function __construct(public Dispute $dispute) {}

    protected function title(): string
    {
        return 'Спор решён: '.mb_strtolower($this->dispute->resolution->getLabel());
    }

    protected function body(): string
    {
        return '«'.$this->dispute->order->title.'». '.$this->dispute->resolution_comment;
    }

    protected function url(): string
    {
        $order = $this->dispute->order;

        if ($this->recipient->id === $order->customer_id) {
            return route('customer.orders.show', $order);
        }

        return $order->conversation ? route('chats.show', $order->conversation) : route('executor.bids');
    }

    protected function icon(): string
    {
        return 'heroicon-o-scale';
    }

    protected function extra(): array
    {
        return ['order_id' => $this->dispute->order_id];
    }
}
