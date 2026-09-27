<?php

namespace App\Models;

use App\Enums\BidStatus;
use Database\Factories\BidFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Предложение исполнителя по заказу: цена, срок и как он это сделает.
 */
#[Fillable(['order_id', 'executor_id', 'offer_price', 'approach_description', 'duration_days', 'status'])]
class Bid extends Model
{
    /** @use HasFactory<BidFactory> */
    use HasFactory;

    protected $attributes = [
        'status' => 'pending',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => BidStatus::class,
            'offer_price' => 'integer',
            'duration_days' => 'integer',
        ];
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return BelongsTo<User, $this> */
    public function executor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executor_id');
    }
}
