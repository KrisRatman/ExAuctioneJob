<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Решение администратора по спору.
 */
enum DisputeResolution: string implements HasColor, HasLabel
{
    /** Исполнитель дорабатывает — заказ снова «в работе». */
    case ReturnToWork = 'return_to_work';

    /** Работа засчитана как выполненная. */
    case Completed = 'completed';

    /** Заказ отменён. */
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::ReturnToWork => 'Возвращён в работу',
            self::Completed => 'Засчитан как выполненный',
            self::Cancelled => 'Заказ отменён',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::ReturnToWork => 'info',
            self::Completed => 'success',
            self::Cancelled => 'gray',
        };
    }

    /** Статус заказа после решения. */
    public function orderStatus(): OrderStatus
    {
        return match ($this) {
            self::ReturnToWork => OrderStatus::InProgress,
            self::Completed, self::Cancelled => OrderStatus::Resolved,
        };
    }
}
