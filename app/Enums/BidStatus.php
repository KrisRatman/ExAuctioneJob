<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum BidStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Rejected = 'rejected';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'Ждёт решения',
            self::Accepted => 'Принято',
            self::Rejected => 'Отклонено',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Accepted => 'success',
            self::Rejected => 'gray',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Pending => 'bg-amber-50 text-amber-800 ring-amber-200',
            self::Accepted => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            self::Rejected => 'bg-slate-100 text-slate-600 ring-slate-200',
        };
    }
}
