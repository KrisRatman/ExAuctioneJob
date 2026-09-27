<?php

namespace App\Filament\Resources\Categories;

use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Models\Category;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use UnitEnum;

/**
 * Дерево тегов: разделы и подкатегории. Пользователи только выбирают из него.
 */
class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|UnitEnum|null $navigationGroup = 'Справочники';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'категория';

    protected static ?string $pluralModelLabel = 'Категории и теги';

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    TextInput::make('name')
                        ->label('Название')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Get $get, Set $set, ?string $state, string $operation) {
                            if ($operation === 'create' || blank($get('slug'))) {
                                $set('slug', Str::slug((string) $state));
                            }
                        }),
                    TextInput::make('slug')
                        ->label('Адрес (slug)')
                        ->required()
                        ->maxLength(255)
                        ->alphaDash()
                        ->unique(ignoreRecord: true),
                    Select::make('parent_id')
                        ->label('Раздел')
                        ->helperText(fn (?Category $record) => $record?->children()->exists()
                            ? 'В разделе есть теги — его нельзя сделать тегом другого раздела.'
                            : 'Пусто — это раздел. Выбран раздел — это тег, который выбирают заказчики и исполнители.')
                        ->relationship('parent', 'name', fn (Builder $query, ?Category $record) => $query
                            ->whereNull('parent_id')
                            ->when($record, fn ($q) => $q->whereKeyNot($record->id)))
                        // Дерево — строго два уровня.
                        ->disabled(fn (?Category $record) => (bool) $record?->children()->exists()),
                    TextInput::make('sort')
                        ->label('Порядок')
                        ->numeric()
                        ->integer()
                        ->minValue(0)
                        ->default(0),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            // Порядок дерева: раздел, за ним его теги.
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with('parent')
                ->withCount(['children', 'orders', 'executors'])
                ->orderByRaw('coalesce((select p.sort from categories p where p.id = categories.parent_id), categories.sort)')
                ->orderByRaw('coalesce(parent_id, id)')
                ->orderByRaw('parent_id is not null')
                ->orderBy('sort')
                ->orderBy('name'))
            ->paginated([25, 50, 100])
            ->defaultPaginationPageOption(100)
            ->columns([
                TextColumn::make('name')
                    ->label('Название')
                    ->formatStateUsing(fn (string $state, Category $record) => $record->isTag() ? '— '.$state : $state)
                    ->weight(fn (Category $record) => $record->isTag() ? null : 'bold')
                    ->searchable(),
                TextColumn::make('slug')
                    ->label('Адрес')
                    ->color('gray'),
                TextColumn::make('executors_count')
                    ->label('Исполнителей')
                    ->state(fn (Category $record) => $record->isTag() ? $record->executors_count : null)
                    ->placeholder(''),
                TextColumn::make('orders_count')
                    ->label('Заказов')
                    ->state(fn (Category $record) => $record->isTag() ? $record->orders_count : null)
                    ->placeholder(''),
                TextColumn::make('sort')
                    ->label('Порядок')
                    ->color('gray'),
            ])
            ->filters([
                TernaryFilter::make('is_tag')
                    ->label('Тип')
                    ->placeholder('Все')
                    ->trueLabel('Только теги')
                    ->falseLabel('Только разделы')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('parent_id'),
                        false: fn (Builder $query) => $query->whereNull('parent_id'),
                    ),
            ])
            ->recordActions([
                EditAction::make(),
                // Раздел с тегами и тег, которым уже пользуются, удалить нельзя.
                DeleteAction::make()
                    ->hidden(fn (Category $record) => $record->children_count > 0 || $record->orders_count > 0 || $record->executors_count > 0),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCategories::route('/'),
            'create' => CreateCategory::route('/create'),
            'edit' => EditCategory::route('/{record}/edit'),
        ];
    }
}
