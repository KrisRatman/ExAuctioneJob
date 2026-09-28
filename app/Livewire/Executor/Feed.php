<?php

namespace App\Livewire\Executor;

use App\Actions\PlaceBid;
use App\Enums\OrderStatus;
use App\Exceptions\AuctionException;
use App\Models\Category;
use App\Models\City;
use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Лента исполнителя: открытые заказы в его городе, у которых есть хотя бы один его тег.
 * Клик по заказу — модалка с описанием и формой предложения.
 */
#[Layout('layouts.app')]
#[Title('Лента заказов')]
class Feed extends Component
{
    use WithPagination;

    /** Фильтр по одному из своих тегов. */
    #[Url(except: null)]
    public ?int $tag = null;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    /** Заказ, открытый в модалке. */
    public ?int $openOrderId = null;

    public ?int $offerPrice = null;

    public string $approach = '';

    public ?int $durationDays = null;

    public function updatedTag(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /** Город из анкеты: лента показывает заказы только из него. */
    #[Computed]
    public function myCity(): ?City
    {
        return auth()->user()->executorProfile?->city;
    }

    /** @return Collection<int, Category> */
    #[Computed]
    public function myTags(): Collection
    {
        return auth()->user()->categories()
            ->withCount(['orders as open_orders_count' => fn ($q) => $q
                ->where('status', OrderStatus::Open)
                ->where('orders.city_id', $this->myCity?->id)])
            ->orderBy('name')
            ->get();
    }

    /** Сколько открытых заказов подходит под город и теги исполнителя. */
    #[Computed]
    public function totalCount(): int
    {
        return $this->visibleOrders()->count();
    }

    /** @return Builder<Order> */
    private function visibleOrders(): Builder
    {
        return Order::query()->open()->matchingExecutor(auth()->user());
    }

    /** @return LengthAwarePaginator<int, Order> */
    #[Computed]
    public function orders(): LengthAwarePaginator
    {
        $search = trim($this->search);

        return $this->visibleOrders()
            ->when($this->tag, fn (Builder $q) => $q->whereHas('categories', fn (Builder $c) => $c->whereKey($this->tag)))
            ->when($search !== '', fn (Builder $q) => $q->where(fn (Builder $w) => $w
                ->where('title', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")))
            ->with(['categories', 'customer', 'bids' => fn ($q) => $q->where('executor_id', auth()->id())])
            ->withCount('bids')
            ->latest()
            ->paginate(10);
    }

    #[Computed]
    public function openOrder(): ?Order
    {
        if ($this->openOrderId === null) {
            return null;
        }

        return $this->visibleOrders()
            ->with(['city', 'categories', 'customer', 'bids' => fn ($q) => $q->where('executor_id', auth()->id())])
            ->withCount('bids')
            ->find($this->openOrderId);
    }

    public function open(int $orderId): void
    {
        $this->resetValidation();
        $this->openOrderId = $orderId;
        unset($this->openOrder);

        if ($this->openOrder === null) {
            $this->openOrderId = null;
            $this->dispatch('toast', message: 'Заказ уже не принимает предложения.', type: 'error');

            return;
        }

        // Повторный клик — правка своего предложения.
        $myBid = $this->openOrder->bids->first();
        $this->offerPrice = $myBid?->offer_price;
        $this->approach = $myBid?->approach_description ?? '';
        $this->durationDays = $myBid?->duration_days;
    }

    public function close(): void
    {
        $this->reset('openOrderId', 'offerPrice', 'approach', 'durationDays');
        $this->resetValidation();
    }

    public function submitBid(PlaceBid $placeBid): void
    {
        $order = $this->openOrder;

        if ($order === null) {
            $this->close();
            $this->dispatch('toast', message: 'Заказ уже не принимает предложения.', type: 'error');

            return;
        }

        $data = $this->validate([
            'offerPrice' => ['required', 'integer', 'min:'.config('ideajob.min_price'), 'max:'.$order->maxOfferPrice()],
            'approach' => ['required', 'string', 'min:30', 'max:3000'],
            'durationDays' => ['required', 'integer', 'min:1', 'max:'.config('ideajob.max_duration_days')],
        ], [
            'offerPrice.max' => 'Цена не может быть выше '.rub($order->maxOfferPrice()).' (стартовая цена + '.rub((int) config('ideajob.max_bid_markup')).').',
        ]);

        $isUpdate = $order->bids->isNotEmpty();

        try {
            $placeBid->handle(auth()->user(), $order, [
                'offer_price' => (int) $data['offerPrice'],
                'approach_description' => trim($data['approach']),
                'duration_days' => (int) $data['durationDays'],
            ]);
        } catch (AuctionException $e) {
            $this->addError('offerPrice', $e->getMessage());

            return;
        }

        $this->close();
        unset($this->orders);
        $this->dispatch('toast', message: $isUpdate ? 'Предложение обновлено.' : 'Предложение отправлено заказчику.');
    }

    public function render(): View
    {
        return view('livewire.executor.feed');
    }
}
