<?php

namespace App\Actions;

use App\Models\Conversation;
use App\Models\User;
use App\Notifications\NewMessageReceived;

/**
 * Пользователь открыл чат: сообщения собеседника и уведомления о них прочитаны.
 */
class MarkConversationRead
{
    /** @return int сколько сообщений отмечено прочитанными */
    public function handle(User $reader, Conversation $conversation): int
    {
        $marked = $conversation->messages()
            ->whereNull('read_at')
            ->where('sender_id', '!=', $reader->id)
            ->update(['read_at' => now()]);

        $reader->unreadNotifications()
            ->where('type', NewMessageReceived::class)
            ->where('data->conversation_id', $conversation->id)
            ->update(['read_at' => now()]);

        return $marked;
    }
}
