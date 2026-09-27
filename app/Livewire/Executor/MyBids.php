<?php

namespace App\Livewire\Executor;

use App\Models\Bid;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Мои предложения')]
class MyBids extends Component
{
    use WithPagination;

    /** @return LengthAwarePaginator<int, Bid> */
    #[Computed]
    public function bids(): LengthAwarePaginator
    {
        return auth()->user()->bids()
            ->with(['order.categories', 'order.customer'])
            ->latest('updated_at')
            ->paginate(15);
    }

    public function render(): View
    {
        return view('livewire.executor.my-bids');
    }
}
