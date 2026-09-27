<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\Order;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('home', [
            'tree' => Category::tree(),
            'openOrdersCount' => Order::query()->where('status', OrderStatus::Open)->count(),
        ]);
    }
}
