<?php

namespace App\Filament\Resources\Orders;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Filament\Resources\Orders\RelationManagers\BidsRelationManager;
use App\Models\Order;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Заказы — только просмотр: статусы меняют заказчик и исполнитель.
 */
class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = 'Биржа';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'заказ';

    protected static ?string $pluralModelLabel = 'Заказы';

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $recordTitleAttribute = 'title';

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Задача')
                    ->columnSpan(2)
                    ->schema([
                        TextEntry::make('title')->label('Название')->weight('bold'),
                        TextEntry::make('categories.name')->label('Теги')->badge(),
                        TextEntry::make('description')->label('Описание')->prose(),
                    ]),
                Section::make('Заказ')
                    ->columnSpan(1)
                    ->schema([
                        TextEntry::make('status')->label('Статус')->badge(),
                        TextEntry::make('city.name')->label('Город'),
                        TextEntry::make('starting_price')->label('Стартовая цена')->formatStateUsing(fn (int $state) => rub($state)),
                        TextEntry::make('customer.name')->label('Заказчик')->helperText(fn (Order $record) => $record->customer->email),
                        TextEntry::make('executor.name')->label('Исполнитель')->placeholder('не выбран'),
                        TextEntry::make('created_at')->label('Создан')->dateTime('d.m.Y H:i'),
                        TextEntry::make('accepted_at')->label('Исполнитель выбран')->dateTime('d.m.Y H:i')->placeholder('—'),
                        TextEntry::make('cancelled_at')->label('Отменён')->dateTime('d.m.Y H:i')->visible(fn (Order $record) => $record->cancelled_at !== null),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['city', 'customer', 'executor'])->withCount('bids'))
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('title')
                    ->label('Заказ')
                    ->description(fn (Order $record) => $record->customer->name)
                    ->limit(60)
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Статус')
                    ->badge(),
                TextColumn::make('city.name')
                    ->label('Город'),
                TextColumn::make('starting_price')
                    ->label('Цена')
                    ->formatStateUsing(fn (int $state) => rub($state))
                    ->sortable(),
                TextColumn::make('bids_count')
                    ->label('Предложений')
                    ->sortable(),
                TextColumn::make('executor.name')
                    ->label('Исполнитель')
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Статус')->options(OrderStatus::class),
                SelectFilter::make('city')->label('Город')->relationship('city', 'name')->searchable()->preload(),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            BidsRelationManager::class,
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'view' => ViewOrder::route('/{record}'),
        ];
    }
}
