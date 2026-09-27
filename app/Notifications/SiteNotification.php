<?php

namespace App\Notifications;

use App\Models\User;
use App\Notifications\Channels\SafeBroadcastChannel;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

/**
 * Уведомление для колокольчика на сайте: пишется в таблицу notifications
 * и транслируется в приватный канал `user.{id}` через Reverb (сбой трансляции не мешает записи).
 *
 * Данные у всех одинаковой формы, поэтому колокольчик рисует их без разбора типов.
 */
abstract class SiteNotification extends Notification
{
    /** Заголовок в колокольчике. */
    abstract protected function title(): string;

    /** Текст под заголовком. */
    abstract protected function body(): string;

    /** Куда ведёт клик по уведомлению. */
    abstract protected function url(): string;

    /** Heroicon: `heroicon-o-...`. */
    abstract protected function icon(): string;

    /**
     * Дополнительные поля — например, id диалога для склейки уведомлений о сообщениях.
     *
     * @return array<string, mixed>
     */
    protected function extra(): array
    {
        return [];
    }

    /** @return list<string> */
    public function via(User $notifiable): array
    {
        return ['database', SafeBroadcastChannel::class];
    }

    /**
     * @return array{title: string, body: string, url: string, icon: string}
     */
    public function toArray(User $notifiable): array
    {
        return [
            'title' => $this->title(),
            'body' => $this->body(),
            'url' => $this->url(),
            'icon' => $this->icon(),
            ...$this->extra(),
        ];
    }

    public function toBroadcast(User $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    /** Короткое имя события на фронте вместо полного имени класса. */
    public function broadcastType(): string
    {
        return class_basename($this);
    }
}
