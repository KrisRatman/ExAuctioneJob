<?php

namespace App\Filament\Resources\Orders\RelationManagers;

use App\Models\Bid;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Предложения исполнителей по заказу — только просмотр.
 */
class BidsRelationManager extends RelationManager
{
    protected static string $relationship = 'bids';

    protected static ?string $title = 'Предложения';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('executor'))
            ->defaultSort('created_at')
            ->emptyStateHeading('Предложений пока нет')
            ->columns([
                TextColumn::make('executor.name')
                    ->label('Исполнитель')
                    ->description(fn (Bid $record) => $record->executor->email),
                TextColumn::make('offer_price')
                    ->label('Цена')
                    ->formatStateUsing(fn (int $state) => rub($state)),
                TextColumn::make('duration_days')
                    ->label('Срок, дней'),
                TextColumn::make('approach_description')
                    ->label('Подход')
                    ->limit(80)
                    ->wrap(),
                TextColumn::make('status')
                    ->label('Статус')
                    ->badge(),
                TextColumn::make('created_at')
                    ->label('Создано')
                    ->dateTime('d.m.Y H:i'),
            ]);
    }
}
