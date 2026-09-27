<?php

namespace App\Livewire\Customer;

use App\Actions\AcceptBid;
use App\Actions\CancelOrder;
use App\Enums\BidStatus;
use App\Exceptions\AuctionException;
use App\Models\Bid;
use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Карточка заказа заказчика: слева заказ, справа предложения исполнителей.
 */
#[Layout('layouts.app')]
class OrderShow extends Component
{
    #[Locked]
    public Order $order;

    /** Предложение, чья карточка исполнителя развёрнута. */
    public ?int $selectedBidId = null;

    public function mount(Order $order): void
    {
        // Чужой заказ — как несуществующий.
        abort_unless($order->customer_id === auth()->id(), 404);

        $this->order = $order;
    }

    /** @return Collection<int, Bid> */
    #[Computed]
    public function bids(): Collection
    {
        return $this->order->bids()
            ->with(['executor.executorProfile', 'executor.categories'])
            // Сначала принятое, потом ждущие решения, отклонённые — в конце.
            ->orderByRaw('case status when ? then 0 when ? then 1 else 2 end', [BidStatus::Accepted->value, BidStatus::Pending->value])
            ->oldest()
            ->get();
    }

    #[Computed]
    public function selectedBid(): ?Bid
    {
        return $this->bids->firstWhere('id', $this->selectedBidId);
    }

    public function selectBid(int $bidId): void
    {
        $this->selectedBidId = $this->selectedBidId === $bidId ? null : $bidId;
    }

    public function accept(int $bidId, AcceptBid $acceptBid): void
    {
        $bid = $this->order->bids()->with('executor')->findOrFail($bidId);

        try {
            $this->order = $acceptBid->handle(auth()->user(), $bid);
        } catch (AuctionException $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');

            return;
        }

        unset($this->bids, $this->selectedBid);

        session()->flash('status', 'Исполнитель '.$bid->executor->name.' принят. Остальные предложения отклонены.');

        $this->redirectRoute('customer.orders.show', $this->order);
    }

    public function cancel(CancelOrder $cancelOrder): void
    {
        try {
            $this->order = $cancelOrder->handle(auth()->user(), $this->order);
        } catch (AuctionException $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');

            return;
        }

        session()->flash('status', 'Заказ отменён.');

        $this->redirectRoute('customer.orders.show', $this->order);
    }

    public function render(): View
    {
        $this->order->loadMissing(['categories', 'executor']);

        return view('livewire.customer.order-show')->title($this->order->title);
    }
}
