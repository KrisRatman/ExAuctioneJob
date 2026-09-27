<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Заказчик, исполнитель или администратор — роль выбирается при регистрации и не меняется.
 */
#[Fillable(['name', 'email', 'phone', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    /** @return HasOne<ExecutorProfile, $this> */
    public function executorProfile(): HasOne
    {
        return $this->hasOne(ExecutorProfile::class);
    }

    /**
     * Теги специализации исполнителя.
     *
     * @return BelongsToMany<Category, $this>
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'executor_category');
    }

    /**
     * Заказы, которые разместил заказчик.
     *
     * @return HasMany<Order, $this>
     */
    public function customerOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'customer_id');
    }

    /**
     * Заказы, на которые исполнителя приняли.
     *
     * @return HasMany<Order, $this>
     */
    public function executorOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'executor_id');
    }

    /** @return HasMany<Bid, $this> */
    public function bids(): HasMany
    {
        return $this->hasMany(Bid::class, 'executor_id');
    }

    public function isCustomer(): bool
    {
        return $this->role === UserRole::Customer;
    }

    public function isExecutor(): bool
    {
        return $this->role === UserRole::Executor;
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    /** Куда вести пользователя после входа. */
    public function homeUrl(): string
    {
        return match ($this->role) {
            UserRole::Customer => route('customer.orders.index'),
            UserRole::Executor => route('executor.feed'),
            UserRole::Admin => url('/admin'),
        };
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isAdmin();
    }
}
