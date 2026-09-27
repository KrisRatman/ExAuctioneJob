<?php

namespace App\Models;

use Database\Factories\ConversationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Чат заказчика и исполнителя по одному заказу.
 */
#[Fillable(['order_id', 'customer_id', 'executor_id', 'last_message_at'])]
class Conversation extends Model
{
    /** @use HasFactory<ConversationFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
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

    /** @return HasMany<Message, $this> */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /** @return HasOne<Message, $this> */
    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function hasParticipant(User $user): bool
    {
        return $user->id === $this->customer_id || $user->id === $this->executor_id;
    }

    /** Собеседник пользователя. Связи customer и executor должны быть загружены. */
    public function otherParticipant(User $user): User
    {
        return $user->id === $this->customer_id ? $this->executor : $this->customer;
    }

    public function otherParticipantId(User $user): int
    {
        return $user->id === $this->customer_id ? $this->executor_id : $this->customer_id;
    }

    /** @param  Builder<Conversation>  $query */
    #[Scope]
    protected function forUser(Builder $query, User $user): void
    {
        $query->where(fn (Builder $q) => $q->where('customer_id', $user->id)->orWhere('executor_id', $user->id));
    }

    /**
     * Число непрочитанных сообщений для пользователя — подзапрос `unread_count`.
     *
     * @param  Builder<Conversation>  $query
     */
    #[Scope]
    protected function withUnreadCountFor(Builder $query, User $user): void
    {
        $query->withCount(['messages as unread_count' => fn (Builder $q) => $q
            ->whereNull('read_at')
            ->where('sender_id', '!=', $user->id)]);
    }
}
