<?php

namespace App\Notifications;

use App\Models\Dispute;
use Illuminate\Support\Str;

/**
 * Исполнителю: заказчик оспорил результат, решение примет администратор.
 */
class DisputeOpened extends SiteNotification
{
    public function __construct(public Dispute $dispute) {}

    protected function title(): string
    {
        return 'Заказчик открыл спор';
    }

    protected function body(): string
    {
        return '«'.$this->dispute->order->title.'»: '.Str::limit($this->dispute->reason, 80);
    }

    protected function url(): string
    {
        return $this->dispute->order->conversation
            ? route('chats.show', $this->dispute->order->conversation)
            : route('executor.bids');
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
