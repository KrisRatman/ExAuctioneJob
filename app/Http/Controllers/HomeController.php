<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Order;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('home', [
            'tree' => Category::tree(),
            'openOrdersCount' => Order::query()->open()->count(),
            // Свежие заказы — сразу показывают исполнителю, что на бирже есть работа.
            'latestOrders' => Order::query()->open()->with('categories')->withCount('bids')->latest()->limit(5)->get(),
        ]);
    }
}
