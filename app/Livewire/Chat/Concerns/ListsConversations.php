<?php

namespace App\Livewire\Chat\Concerns;

use App\Models\Conversation;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;

/**
 * Список диалогов пользователя со счётчиком непрочитанных и обновление по Reverb.
 */
trait ListsConversations
{
    /** @return Collection<int, Conversation> */
    #[Computed]
    public function conversations(): Collection
    {
        $user = auth()->user();

        return Conversation::query()
            ->forUser($user)
            ->withUnreadCountFor($user)
            ->with(['order', 'customer', 'executor', 'latestMessage'])
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Новое сообщение в любом диалоге пользователя.
     *
     * @return array<string, string>
     */
    public function getListeners(): array
    {
        return ['echo-private:user.'.auth()->id().',.NewMessage' => 'onNewMessage'];
    }

    /** @param  array{conversation_id: int, message_id: int, sender_id: int}  $payload */
    public function onNewMessage(array $payload): void
    {
        unset($this->conversations);
    }
}
