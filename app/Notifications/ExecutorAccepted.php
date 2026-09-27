<?php

namespace App\Notifications;

use App\Models\Conversation;

/**
 * Исполнителю: заказчик выбрал его. Клик ведёт сразу в чат.
 */
class ExecutorAccepted extends SiteNotification
{
    public function __construct(public Conversation $conversation) {}

    protected function title(): string
    {
        return 'Вас приняли на заказ';
    }

    protected function body(): string
    {
        return '«'.$this->conversation->order->title.'» — напишите заказчику в чат.';
    }

    protected function url(): string
    {
        return route('chats.show', $this->conversation);
    }

    protected function icon(): string
    {
        return 'heroicon-o-check-badge';
    }

    protected function extra(): array
    {
        return ['order_id' => $this->conversation->order_id, 'conversation_id' => $this->conversation->id];
    }
}
