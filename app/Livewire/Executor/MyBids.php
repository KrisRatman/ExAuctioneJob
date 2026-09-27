<?php

namespace App\Livewire\Executor;

use App\Enums\BidStatus;
use App\Enums\OrderStatus;
use App\Models\Bid;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Мои предложения')]
class MyBids extends Component
{
    use WithPagination;

    /** Активные — ждут решения или заказ в работе; архив — отклонённые и закрытые. */
    #[Url(except: 'active')]
    public string $tab = 'active';

    /** @return list<OrderStatus> */
    private static function workingStatuses(): array
    {
        return [OrderStatus::InProgress, OrderStatus::Delivered, OrderStatus::Disputed];
    }

    public function updatedTab(): void
    {
        $this->resetPage();
    }

    /** @param  Builder<Bid>  $query */
    private function applyTab(Builder $query, string $tab): Builder
    {
        $isActive = fn (Builder $q) => $q
            ->where('status', BidStatus::Pending)
            ->orWhere(fn (Builder $accepted) => $accepted
                ->where('status', BidStatus::Accepted)
                ->whereHas('order', fn (Builder $order) => $order->whereIn('status', self::workingStatuses())));

        return $tab === 'archive'
            ? $query->whereNot($isActive)
            : $query->where($isActive);
    }

    /** @return LengthAwarePaginator<int, Bid> */
    #[Computed]
    public function bids(): LengthAwarePaginator
    {
        return $this->applyTab(auth()->user()->bids()->getQuery(), $this->tab)
            ->with(['order.categories', 'order.customer', 'order.conversation'])
            ->latest('updated_at')
            ->paginate(15);
    }

    /** @return array{active: int, archive: int} */
    #[Computed]
    public function counts(): array
    {
        return [
            'active' => $this->applyTab(auth()->user()->bids()->getQuery(), 'active')->count(),
            'archive' => $this->applyTab(auth()->user()->bids()->getQuery(), 'archive')->count(),
        ];
    }

    public function isWorking(Bid $bid): bool
    {
        return $bid->status === BidStatus::Accepted && in_array($bid->order->status, self::workingStatuses(), true);
    }

    public function render(): View
    {
        return view('livewire.executor.my-bids');
    }
}
