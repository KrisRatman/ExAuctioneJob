<?php

namespace App\Filament\Resources\Users\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    /** @return array<string, Tab> */
    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Все'),
            'customers' => Tab::make('Заказчики')->modifyQueryUsing(fn (Builder $query) => $query->where('role', UserRole::Customer)),
            'executors' => Tab::make('Исполнители')->modifyQueryUsing(fn (Builder $query) => $query->where('role', UserRole::Executor)),
        ];
    }
}
