<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Channels\BroadcastChannel;
use Illuminate\Notifications\Notification;

/**
 * Трансляция уведомления, которая не ломает действие пользователя.
 *
 * Уведомление к этому моменту уже в базе (колокольчик покажет его при загрузке страницы),
 * поэтому недоступный WebSocket-сервер — не повод прерывать принятие исполнителя или отправку сообщения.
 * Ошибка попадает в лог.
 */
class SafeBroadcastChannel extends BroadcastChannel
{
    /** @return array<int, mixed>|null */
    public function send($notifiable, Notification $notification)
    {
        return rescue(fn () => parent::send($notifiable, $notification));
    }
}
