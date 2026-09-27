<?php

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Колокольчик в шапке: последние уведомления, счётчик непрочитанных.
 * Новые приходят через Reverb; редкий опрос — запасной вариант, если WebSocket недоступен.
 */
class NotificationBell extends Component
{
    private const LIMIT = 8;

    /** @return array<string, string> */
    public function getListeners(): array
    {
        return ['echo-notification:user.'.auth()->id() => 'refreshBell'];
    }

    #[On('chat-read')]
    public function refreshBell(): void
    {
        unset($this->notifications, $this->unreadCount);
    }

    /** @return Collection<int, DatabaseNotification> */
    #[Computed]
    public function notifications(): Collection
    {
        return auth()->user()->notifications()->limit(self::LIMIT)->get();
    }

    #[Computed]
    public function unreadCount(): int
    {
        return auth()->user()->unreadNotifications()->count();
    }

    /** Клик по уведомлению: отметить прочитанным и перейти по ссылке. */
    public function open(string $id): void
    {
        $notification = auth()->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        $this->redirect($notification->data['url'] ?? auth()->user()->homeUrl());
    }

    public function markAllRead(): void
    {
        auth()->user()->unreadNotifications()->update(['read_at' => now()]);
        $this->refreshBell();
    }

    public function render(): View
    {
        return view('livewire.notification-bell');
    }
}
