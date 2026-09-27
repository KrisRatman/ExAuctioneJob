<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

// Личный канал: уведомления и новые сообщения чата слушает только сам пользователь.
Broadcast::channel('user.{id}', fn (User $user, int $id) => $user->id === $id);
