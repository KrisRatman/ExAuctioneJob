<?php

namespace App\Livewire\Chat;

use App\Actions\MarkConversationRead;
use App\Actions\SendMessage;
use App\Livewire\Chat\Concerns\ListsConversations;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Чат по заказу: слева диалоги, справа переписка. Новые сообщения приходят через Reverb.
 */
#[Layout('layouts.app')]
class Show extends Component
{
    use ListsConversations {
        onNewMessage as refreshConversationList;
    }

    /** Сколько последних сообщений показывать. */
    private const HISTORY_LIMIT = 200;

    #[Locked]
    public Conversation $conversation;

    public string $body = '';

    public function mount(Conversation $conversation, MarkConversationRead $markRead): void
    {
        // Чужой чат — как несуществующий.
        abort_unless($conversation->hasParticipant(auth()->user()), 404);

        $this->conversation = $conversation;
        $markRead->handle(auth()->user(), $conversation);
    }

    /** @return Collection<int, Message> */
    #[Computed]
    public function messages(): Collection
    {
        return $this->conversation->messages()
            ->latest('id')
            ->limit(self::HISTORY_LIMIT)
            ->get()
            ->reverse()
            ->values();
    }

    public function send(SendMessage $sendMessage): void
    {
        $data = $this->validate(['body' => ['required', 'string', 'max:5000']], [], ['body' => 'сообщение']);

        $body = trim($data['body']);

        if ($body === '') {
            return;
        }

        $sendMessage->handle(auth()->user(), $this->conversation, $body);

        $this->reset('body');
        unset($this->messages, $this->conversations);
        $this->dispatch('chat-scroll');
    }

    /** @param  array{conversation_id: int, message_id: int, sender_id: int}  $payload */
    public function onNewMessage(array $payload): void
    {
        $this->refreshConversationList($payload);

        if ($payload['conversation_id'] !== $this->conversation->id) {
            return;
        }

        if ($payload['sender_id'] !== auth()->id()) {
            app(MarkConversationRead::class)->handle(auth()->user(), $this->conversation);
            $this->dispatch('chat-read');
        }

        unset($this->messages);
        $this->dispatch('chat-scroll');
    }

    public function render(): View
    {
        $this->conversation->loadMissing(['order', 'customer', 'executor']);

        return view('livewire.chat.show')
            ->title('Чат: '.$this->conversation->order->title);
    }
}
