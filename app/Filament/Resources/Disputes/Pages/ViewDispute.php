<?php

namespace App\Filament\Resources\Disputes\Pages;

use App\Actions\ResolveDispute;
use App\Enums\DisputeResolution;
use App\Exceptions\AuctionException;
use App\Filament\Resources\Disputes\DisputeResource;
use App\Models\Dispute;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

/**
 * @property Dispute $record
 */
class ViewDispute extends ViewRecord
{
    protected static string $resource = DisputeResource::class;

    public function getTitle(): string
    {
        return 'Спор по заказу «'.$this->record->order->title.'»';
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->decisionAction(DisputeResolution::ReturnToWork, 'Вернуть в работу', Heroicon::OutlinedArrowUturnLeft, 'info',
                'Исполнитель доработает результат и сдаст заново.'),
            $this->decisionAction(DisputeResolution::Completed, 'Засчитать выполненным', Heroicon::OutlinedCheckCircle, 'success',
                'Заказ закроется как выполненный через спор (в рейтинг исполнителя не входит).'),
            $this->decisionAction(DisputeResolution::Cancelled, 'Отменить заказ', Heroicon::OutlinedXCircle, 'danger',
                'Заказ закроется как отменённый.'),
        ];
    }

    private function decisionAction(DisputeResolution $resolution, string $label, Heroicon $icon, string $color, string $description): Action
    {
        return Action::make($resolution->value)
            ->label($label)
            ->icon($icon)
            ->color($color)
            ->visible(fn () => $this->record->isOpen())
            ->modalHeading($label.'?')
            ->modalDescription($description.' Обе стороны получат уведомление с вашим комментарием.')
            ->schema([
                Textarea::make('comment')
                    ->label('Комментарий к решению (увидят заказчик и исполнитель)')
                    ->required()
                    ->minLength(10)
                    ->maxLength(2000)
                    ->rows(4),
            ])
            ->action(function (array $data, ResolveDispute $resolveDispute) use ($resolution) {
                try {
                    $resolveDispute->handle(auth()->user(), $this->record, $resolution, trim($data['comment']));
                } catch (AuctionException $e) {
                    Notification::make()->title($e->getMessage())->danger()->send();

                    return;
                }

                $this->record->refresh();
                Notification::make()->title('Спор решён: '.mb_strtolower($resolution->getLabel()))->success()->send();
            });
    }
}
