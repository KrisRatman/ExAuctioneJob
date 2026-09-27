<?php

namespace App\Models;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Узел дерева: раздел (parent_id = null) или тег — подкатегория,
 * которую выбирают заказчик и исполнитель.
 */
#[Fillable(['parent_id', 'name', 'slug', 'sort'])]
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    protected $attributes = [
        'sort' => 0,
    ];

    /** @return BelongsTo<Category, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /** @return HasMany<Category, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('sort')->orderBy('name');
    }

    /** @return BelongsToMany<Order, $this> */
    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class, 'order_category');
    }

    /** @return BelongsToMany<User, $this> */
    public function executors(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'executor_category');
    }

    public function isTag(): bool
    {
        return $this->parent_id !== null;
    }

    /** @param  Builder<Category>  $query */
    #[Scope]
    protected function roots(Builder $query): void
    {
        $query->whereNull('parent_id');
    }

    /** @param  Builder<Category>  $query */
    #[Scope]
    protected function tags(Builder $query): void
    {
        $query->whereNotNull('parent_id');
    }

    /**
     * Разделы с тегами — для выбора тегов в формах.
     *
     * @return Collection<int, Category>
     */
    public static function tree(): Collection
    {
        return static::query()
            ->roots()
            ->whereHas('children')
            ->with('children')
            ->orderBy('sort')
            ->orderBy('name')
            ->get();
    }
}
