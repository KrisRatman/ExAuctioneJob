<?php

namespace App\Livewire\Customer;

use App\Enums\BidStatus;
use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Мои заказы')]
class OrderList extends Component
{
    use WithPagination;

    /** Активные — ещё не закрытые заказы, архив — выполненные и отменённые. */
    #[Url(except: 'active')]
    public string $tab = 'active';

    /** @return list<OrderStatus> */
    public static function archiveStatuses(): array
    {
        return [OrderStatus::Completed, OrderStatus::Resolved, OrderStatus::Cancelled];
    }

    public function updatedTab(): void
    {
        $this->resetPage();
    }

    /** @return LengthAwarePaginator<int, Order> */
    #[Computed]
    public function orders(): LengthAwarePaginator
    {
        return auth()->user()->customerOrders()
            ->when(
                $this->tab === 'archive',
                fn ($q) => $q->whereIn('status', self::archiveStatuses()),
                fn ($q) => $q->whereNotIn('status', self::archiveStatuses()),
            )
            ->with(['categories', 'executor'])
            ->withCount(['bids as pending_bids_count' => fn ($q) => $q->where('status', BidStatus::Pending)])
            ->latest()
            ->paginate(10);
    }

    /** @return array{active: int, archive: int} */
    #[Computed]
    public function counts(): array
    {
        $archived = auth()->user()->customerOrders()->whereIn('status', self::archiveStatuses())->count();

        return [
            'active' => auth()->user()->customerOrders()->count() - $archived,
            'archive' => $archived,
        ];
    }

    public function render(): View
    {
        return view('livewire.customer.order-list');
    }
}
