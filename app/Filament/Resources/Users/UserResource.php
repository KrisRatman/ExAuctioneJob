<?php

namespace App\Filament\Resources\Users;

use App\Enums\UserRole;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'Биржа';

    protected static ?int $navigationSort = 20;

    protected static ?string $modelLabel = 'пользователь';

    protected static ?string $pluralModelLabel = 'Пользователи';

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $recordTitleAttribute = 'name';

    /** @return list<string> */
    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email', 'phone'];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    TextInput::make('name')->label('Имя')->required()->maxLength(255),
                    Select::make('role')
                        ->label('Роль')
                        ->options(UserRole::class)
                        ->disabled()
                        ->dehydrated(false)
                        ->helperText('Роль выбирается при регистрации.'),
                    TextInput::make('email')->label('Email')->email()->required()->maxLength(255)->unique(ignoreRecord: true),
                    TextInput::make('phone')->label('Телефон')->tel()->maxLength(20),
                ]),
            Section::make('Анкета исполнителя')
                ->columnSpanFull()
                ->relationship('executorProfile')
                ->visible(fn (?User $record) => $record?->isExecutor())
                ->schema([
                    Textarea::make('description')->label('О себе')->rows(5)->required()->maxLength(3000),
                ]),
            Section::make('Специализации')
                ->columnSpanFull()
                ->visible(fn (?User $record) => $record?->isExecutor())
                ->schema([
                    Select::make('categories')
                        ->hiddenLabel()
                        ->multiple()
                        ->relationship('categories', 'name', fn (Builder $query) => $query->whereNotNull('parent_id'))
                        ->preload(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount(['customerOrders', 'bids']))
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label('Имя')
                    ->description(fn (User $record) => $record->email)
                    ->searchable(['name', 'email', 'phone']),
                TextColumn::make('role')
                    ->label('Роль')
                    ->badge(),
                TextColumn::make('phone')
                    ->label('Телефон')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('activity')
                    ->label('Активность')
                    ->state(fn (User $record) => match (true) {
                        $record->isCustomer() => $record->customer_orders_count.' '.plural($record->customer_orders_count, 'заказ', 'заказа', 'заказов'),
                        $record->isExecutor() => $record->bids_count.' '.plural($record->bids_count, 'предложение', 'предложения', 'предложений'),
                        default => null,
                    })
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->label('Регистрация')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('role')->label('Роль')->options(UserRole::class),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
