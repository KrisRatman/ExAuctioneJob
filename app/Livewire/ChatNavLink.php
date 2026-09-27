<?php

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Пункт меню «Чаты» с бейджем непрочитанных сообщений.
 */
class ChatNavLink extends Component
{
    public string $linkClass = '';

    /** @return array<string, string> */
    public function getListeners(): array
    {
        return ['echo-private:user.'.auth()->id().',.NewMessage' => 'refreshCount'];
    }

    #[On('chat-read')]
    public function refreshCount(): void
    {
        unset($this->unreadCount);
    }

    #[Computed]
    public function unreadCount(): int
    {
        return auth()->user()->unreadMessagesCount();
    }

    public function render(): View
    {
        return view('livewire.chat-nav-link');
    }
}
