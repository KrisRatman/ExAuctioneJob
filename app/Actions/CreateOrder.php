<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Заказчик публикует задачу на доску заказов.
 */
class CreateOrder
{
    /**
     * @param  array{title: string, description: string, starting_price: int, category_ids: list<int>}  $data
     */
    public function handle(User $customer, array $data): Order
    {
        return DB::transaction(function () use ($customer, $data) {
            $order = $customer->customerOrders()->create([
                'title' => $data['title'],
                'description' => $data['description'],
                'starting_price' => $data['starting_price'],
                'status' => OrderStatus::Open,
            ]);

            $order->categories()->attach($data['category_ids']);

            return $order;
        });
    }
}
