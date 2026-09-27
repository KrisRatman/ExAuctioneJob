<?php

namespace App\Notifications;

use App\Models\Message;
use Illuminate\Support\Str;

/**
 * Собеседнику: новое сообщение в чате. По одному диалогу в колокольчике
 * висит только последнее непрочитанное (см. SendMessage).
 */
class NewMessageReceived extends SiteNotification
{
    public function __construct(public Message $message) {}

    protected function title(): string
    {
        return 'Новое сообщение';
    }

    protected function body(): string
    {
        return $this->message->sender->name.': '.Str::limit($this->message->body, 90);
    }

    protected function url(): string
    {
        return route('chats.show', $this->message->conversation_id);
    }

    protected function icon(): string
    {
        return 'heroicon-o-chat-bubble-left-right';
    }

    protected function extra(): array
    {
        return ['conversation_id' => $this->message->conversation_id];
    }
}
