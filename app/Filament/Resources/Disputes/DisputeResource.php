<?php

namespace App\Filament\Resources\Disputes;

use App\Enums\DisputeStatus;
use App\Filament\Resources\Disputes\Pages\ListDisputes;
use App\Filament\Resources\Disputes\Pages\ViewDispute;
use App\Models\Dispute;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
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
 * Очередь споров: администратор видит переписку, заказ и принятое предложение и выносит решение.
 */
class DisputeResource extends Resource
{
    protected static ?string $model = Dispute::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedScale;

    protected static string|UnitEnum|null $navigationGroup = 'Биржа';

    protected static ?int $navigationSort = 5;

    protected static ?string $modelLabel = 'спор';

    protected static ?string $pluralModelLabel = 'Споры';

    protected static bool $hasTitleCaseModelLabel = false;

    public static function getNavigationBadge(): ?string
    {
        $count = Dispute::query()->where('status', DisputeStatus::Open)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Ждут решения';
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Спор')
                    ->columnSpan(2)
                    ->schema([
                        TextEntry::make('reason')->label('Причина от заказчика')->prose(),
                        TextEntry::make('created_at')->label('Открыт')->dateTime('d.m.Y H:i'),
                        TextEntry::make('resolution')->label('Решение')->badge()->visible(fn (Dispute $record) => $record->resolution !== null),
                        TextEntry::make('resolution_comment')->label('Комментарий к решению')->visible(fn (Dispute $record) => $record->resolution !== null),
                        TextEntry::make('resolver.name')->label('Решил')->visible(fn (Dispute $record) => $record->resolved_by !== null)
                            ->helperText(fn (Dispute $record) => $record->resolved_at?->format('d.m.Y H:i')),
                    ]),
                Section::make('Заказ')
                    ->columnSpan(1)
                    ->schema([
                        TextEntry::make('status')->label('Статус спора')->badge(),
                        TextEntry::make('order.title')->label('Заказ')->weight('bold')
                            ->url(fn (Dispute $record) => route('filament.admin.resources.orders.view', $record->order_id)),
                        TextEntry::make('order.status')->label('Статус заказа')->badge(),
                        TextEntry::make('order.customer.name')->label('Заказчик')->helperText(fn (Dispute $record) => $record->order->customer->email),
                        TextEntry::make('order.executor.name')->label('Исполнитель')->helperText(fn (Dispute $record) => $record->order->executor?->email),
                        TextEntry::make('accepted_offer')->label('Принятое предложение')
                            ->state(fn (Dispute $record) => $record->order->acceptedBid
                                ? rub($record->order->acceptedBid->offer_price).' · '.$record->order->acceptedBid->duration_days.' дн.'
                                : null)
                            ->helperText(fn (Dispute $record) => $record->order->acceptedBid?->approach_description)
                            ->placeholder('—'),
                        TextEntry::make('order.delivered_at')->label('Сдано')->dateTime('d.m.Y H:i')->placeholder('—'),
                    ]),
                Section::make('Задача')
                    ->columnSpan(2)
                    ->collapsible()
                    ->schema([
                        TextEntry::make('order.description')->hiddenLabel()->prose(),
                    ]),
                Section::make('Переписка в чате')
                    ->columnSpanFull()
                    ->schema([
                        ViewEntry::make('chat')->hiddenLabel()->view('filament.disputes.chat-transcript'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['order.customer', 'order.executor']))
            // Открытые — сверху, внутри по дате.
            ->defaultSort(fn (Builder $query) => $query->orderByRaw('case when status = ? then 0 else 1 end', [DisputeStatus::Open->value])->orderByDesc('id'))
            ->columns([
                TextColumn::make('order.title')
                    ->label('Заказ')
                    ->description(fn (Dispute $record) => $record->order->customer->name.' → '.$record->order->executor?->name)
                    ->limit(50)
                    ->searchable(),
                TextColumn::make('reason')
                    ->label('Причина')
                    ->limit(70)
                    ->wrap(),
                TextColumn::make('status')
                    ->label('Статус')
                    ->badge(),
                TextColumn::make('resolution')
                    ->label('Решение')
                    ->badge()
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->label('Открыт')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Статус')->options(DisputeStatus::class),
            ])
            ->recordActions([
                ViewAction::make()->label('Рассмотреть'),
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDisputes::route('/'),
            'view' => ViewDispute::route('/{record}'),
        ];
    }
}
