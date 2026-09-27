<?php

namespace App\Livewire\Chat;

use App\Livewire\Chat\Concerns\ListsConversations;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Чаты')]
class Index extends Component
{
    use ListsConversations;

    public function render(): View
    {
        return view('livewire.chat.index');
    }
}
