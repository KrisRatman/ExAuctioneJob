<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Новое сообщение в чате — в личные каналы обоих участников:
 * у собеседника появится сообщение и бейдж, у автора обновятся другие открытые вкладки.
 *
 * Отправляется сразу, мимо очереди: в чате задержка воркера заметна. Хранит только числа —
 * так событие не зависит от моделей и связей.
 */
class NewMessage implements ShouldBroadcastNow, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets;

    public int $conversationId;

    public int $messageId;

    public int $senderId;

    /** @var list<int> */
    public array $participantIds;

    /** Диалог сообщения должен быть загружен. */
    public function __construct(Message $message)
    {
        $this->conversationId = $message->conversation_id;
        $this->messageId = $message->id;
        $this->senderId = $message->sender_id;
        $this->participantIds = [$message->conversation->customer_id, $message->conversation->executor_id];
    }

    /** @return list<PrivateChannel> */
    public function broadcastOn(): array
    {
        return array_map(fn (int $id) => new PrivateChannel('user.'.$id), $this->participantIds);
    }

    public function broadcastAs(): string
    {
        return 'NewMessage';
    }

    /**
     * @return array{conversation_id: int, message_id: int, sender_id: int}
     */
    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversationId,
            'message_id' => $this->messageId,
            'sender_id' => $this->senderId,
        ];
    }
}
