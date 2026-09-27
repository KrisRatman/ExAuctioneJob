<?php

namespace App\Filament\Resources\Reviews;

use App\Filament\Resources\Reviews\Pages\ListReviews;
use App\Models\Review;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Модерация отзывов: удаление неуместного отзыва сразу пересчитывает рейтинг исполнителя.
 */
class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;

    protected static string|UnitEnum|null $navigationGroup = 'Биржа';

    protected static ?int $navigationSort = 30;

    protected static ?string $modelLabel = 'отзыв';

    protected static ?string $pluralModelLabel = 'Отзывы';

    protected static bool $hasTitleCaseModelLabel = false;

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['executor', 'customer', 'order']))
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('executor.name')
                    ->label('Исполнитель')
                    ->description(fn (Review $record) => 'от '.$record->customer->name)
                    ->searchable(),
                TextColumn::make('rating')
                    ->label('Оценка')
                    ->formatStateUsing(fn (int $state) => str_repeat('★', $state).str_repeat('☆', 5 - $state))
                    ->color('warning')
                    ->sortable(),
                TextColumn::make('comment')
                    ->label('Отзыв')
                    ->placeholder('без текста')
                    ->limit(90)
                    ->wrap(),
                TextColumn::make('order.title')
                    ->label('Заказ')
                    ->limit(40)
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Дата')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('rating')->label('Оценка')->options([5 => '5', 4 => '4', 3 => '3', 2 => '2', 1 => '1']),
            ])
            ->recordActions([
                DeleteAction::make()->modalDescription('Рейтинг исполнителя пересчитается автоматически.'),
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReviews::route('/'),
        ];
    }
}
