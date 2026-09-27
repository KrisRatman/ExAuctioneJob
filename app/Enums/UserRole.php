<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum UserRole: string implements HasColor, HasLabel
{
    case Customer = 'customer';
    case Executor = 'executor';
    case Admin = 'admin';

    public function getLabel(): string
    {
        return match ($this) {
            self::Customer => 'Заказчик',
            self::Executor => 'Исполнитель',
            self::Admin => 'Администратор',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Customer => 'info',
            self::Executor => 'success',
            self::Admin => 'danger',
        };
    }
}
