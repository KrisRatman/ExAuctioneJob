<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum OrderStatus: string implements HasColor, HasLabel
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case Delivered = 'delivered';
    case Completed = 'completed';
    case Disputed = 'disputed';
    case Resolved = 'resolved';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::Open => 'На аукционе',
            self::InProgress => 'В работе',
            self::Delivered => 'Сдан на проверку',
            self::Completed => 'Выполнен',
            self::Disputed => 'Спор',
            self::Resolved => 'Спор решён',
            self::Cancelled => 'Отменён',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Open => 'danger',
            self::InProgress, self::Delivered => 'info',
            self::Completed, self::Resolved => 'success',
            self::Disputed => 'warning',
            self::Cancelled => 'gray',
        };
    }

    /** Классы бейджа на сайте: красный — только у заказа, который ждёт откликов. */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Open => 'bg-brand-50 text-brand-700 ring-brand-200',
            self::InProgress, self::Delivered => 'bg-sky-50 text-sky-700 ring-sky-200',
            self::Completed, self::Resolved => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            self::Disputed => 'bg-amber-50 text-amber-800 ring-amber-200',
            self::Cancelled => 'bg-slate-100 text-slate-600 ring-slate-200',
        };
    }
}
