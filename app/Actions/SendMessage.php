<?php

namespace App\Actions;

use App\Events\NewMessage;
use App\Exceptions\AuctionException;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Notifications\NewMessageReceived;
use Illuminate\Support\Facades\DB;

/**
 * Сообщение в чат по заказу. Собеседник получает сообщение в реальном времени
 * и одно уведомление в колокольчик на диалог (старое непрочитанное заменяется новым).
 */
class SendMessage
{
    public function handle(User $sender, Conversation $conversation, string $body): Message
    {
        if (! $conversation->hasParticipant($sender)) {
            throw new AuctionException('Писать в этот чат могут только заказчик и исполнитель заказа.');
        }

        $message = DB::transaction(function () use ($sender, $conversation, $body) {
            $message = $conversation->messages()->create([
                'sender_id' => $sender->id,
                'body' => $body,
            ]);

            $conversation->update(['last_message_at' => $message->created_at]);

            return $message;
        });

        $message->setRelation('conversation', $conversation)->setRelation('sender', $sender);

        $recipient = User::query()->findOrFail($conversation->otherParticipantId($sender));

        $recipient->unreadNotifications()
            ->where('type', NewMessageReceived::class)
            ->where('data->conversation_id', $conversation->id)
            ->delete();

        $recipient->notify(new NewMessageReceived($message));

        // Недоступный WebSocket-сервер не должен ронять отправку: сообщение уже сохранено,
        // собеседник увидит его при следующей загрузке страницы.
        rescue(fn () => NewMessage::dispatch($message));

        return $message;
    }
}
