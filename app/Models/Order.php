<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

/**
 * Задача заказчика. Пока статус open — принимает предложения исполнителей.
 */
#[Fillable(['customer_id', 'executor_id', 'city_id', 'title', 'description', 'starting_price', 'status', 'accepted_at', 'delivered_at', 'completed_at', 'cancelled_at'])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    protected $attributes = [
        'status' => 'open',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'starting_price' => 'integer',
            'accepted_at' => 'datetime',
            'delivered_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /** @return BelongsTo<User, $this> */
    public function executor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executor_id');
    }

    /**
     * Город, где нужно выполнить работу.
     *
     * @return BelongsTo<City, $this>
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /** @return BelongsToMany<Category, $this> */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'order_category');
    }

    /** @return HasMany<Bid, $this> */
    public function bids(): HasMany
    {
        return $this->hasMany(Bid::class);
    }

    /** @return HasOne<Conversation, $this> */
    public function conversation(): HasOne
    {
        return $this->hasOne(Conversation::class);
    }

    /** @return HasOne<Bid, $this> */
    public function acceptedBid(): HasOne
    {
        return $this->hasOne(Bid::class)->where('status', 'accepted');
    }

    /** @return HasMany<Dispute, $this> */
    public function disputes(): HasMany
    {
        return $this->hasMany(Dispute::class);
    }

    /**
     * Последний спор — открытый или решённый.
     *
     * @return HasOne<Dispute, $this>
     */
    public function latestDispute(): HasOne
    {
        return $this->hasOne(Dispute::class)->latestOfMany();
    }

    /** @return HasOne<Review, $this> */
    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    public function isOpen(): bool
    {
        return $this->status === OrderStatus::Open;
    }

    /** Заказчик может оценить исполнителя: заказ подтверждён, отзыва ещё нет. Связь review должна быть загружена. */
    public function canBeReviewed(): bool
    {
        return $this->status === OrderStatus::Completed && $this->review === null;
    }

    /** Максимальная цена, которую может предложить исполнитель. */
    public function maxOfferPrice(): int
    {
        return $this->starting_price + (int) config('ideajob.max_bid_markup');
    }

    /**
     * Заказы в городе исполнителя, у которых есть хотя бы один тег из его профиля.
     * Исполнитель без города не видит ничего: работа очная.
     *
     * @param  Builder<Order>  $query
     */
    #[Scope]
    protected function matchingExecutor(Builder $query, User $executor): void
    {
        $query->where(
            'orders.city_id',
            ExecutorProfile::query()->select('city_id')->where('user_id', $executor->id),
        )->whereHas('categories', fn (Builder $q) => $q->whereIn(
            'categories.id',
            DB::table('executor_category')->select('category_id')->where('user_id', $executor->id),
        ));
    }

    /** @param  Builder<Order>  $query */
    #[Scope]
    protected function open(Builder $query): void
    {
        $query->where('status', OrderStatus::Open);
    }
}
