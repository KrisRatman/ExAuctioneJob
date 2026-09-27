<?php

namespace App\Livewire;

use App\Actions\ConfirmCompletion;
use App\Actions\DeliverOrder;
use App\Actions\LeaveReview;
use App\Actions\OpenDispute;
use App\Exceptions\AuctionException;
use App\Models\Order;
use Closure;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Панель завершения заказа — в карточке заказа и в чате:
 * исполнитель сдаёт работу, заказчик подтверждает или открывает спор, потом оценивает исполнителя.
 */
class OrderWorkflow extends Component
{
    #[Locked]
    public Order $order;

    /** Куда вернуться после действия — страница, на которой встроена панель. */
    #[Locked]
    public string $returnUrl;

    /** Компактный вид для шапки чата. */
    #[Locked]
    public bool $compact = false;

    public bool $showDisputeForm = false;

    public string $disputeReason = '';

    public ?int $rating = null;

    public string $comment = '';

    public function mount(Order $order, string $returnUrl, bool $compact = false): void
    {
        $user = auth()->user();
        abort_unless($user->id === $order->customer_id || $user->id === $order->executor_id, 404);

        $this->order = $order;
        $this->returnUrl = $returnUrl;
        $this->compact = $compact;
    }

    /**
     * Вторая сторона сдала или приняла работу — панель обновится без перезагрузки.
     *
     * @return array<string, string>
     */
    public function getListeners(): array
    {
        return ['echo-notification:user.'.auth()->id() => 'onNotification'];
    }

    /** @param  array<string, mixed>  $notification */
    public function onNotification(array $notification): void
    {
        if (($notification['order_id'] ?? null) === $this->order->id) {
            $this->order->refresh()->unsetRelations();
        }
    }

    public function deliver(DeliverOrder $deliverOrder): void
    {
        $this->run(fn () => $deliverOrder->handle(auth()->user(), $this->order), 'Работа сдана. Заказчик получил уведомление.');
    }

    public function confirm(ConfirmCompletion $confirmCompletion): void
    {
        $this->run(fn () => $confirmCompletion->handle(auth()->user(), $this->order), 'Заказ выполнен. Оцените работу исполнителя.');
    }

    public function openDispute(OpenDispute $openDispute): void
    {
        $data = $this->validate(
            ['disputeReason' => ['required', 'string', 'min:20', 'max:3000']],
            [],
            ['disputeReason' => 'причина'],
        );

        $this->run(fn () => $openDispute->handle(auth()->user(), $this->order, trim($data['disputeReason'])), 'Спор открыт. Администратор изучит переписку и примет решение.');
    }

    public function review(LeaveReview $leaveReview): void
    {
        $data = $this->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ], ['rating.required' => 'Поставьте оценку от 1 до 5 звёзд.'], ['comment' => 'отзыв']);

        $this->run(
            fn () => $leaveReview->handle(auth()->user(), $this->order, (int) $data['rating'], trim((string) $data['comment']) ?: null),
            'Спасибо за оценку!',
        );
    }

    /** Выполнить действие и перезагрузить страницу, чтобы все её части показали новый статус. */
    private function run(Closure $action, string $message): void
    {
        try {
            $action();
        } catch (AuctionException $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');

            return;
        }

        session()->flash('status', $message);
        $this->redirect($this->returnUrl);
    }

    public function render(): View
    {
        $this->order->loadMissing(['executor', 'review', 'latestDispute']);

        return view('livewire.order-workflow', [
            'isCustomer' => auth()->id() === $this->order->customer_id,
        ]);
    }
}
