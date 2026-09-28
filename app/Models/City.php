<?php

namespace App\Models;

use Database\Factories\CityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Город, где нужна работа. Заказ видят только мастера из того же города.
 */
#[Fillable(['name', 'slug'])]
class City extends Model
{
    /** @use HasFactory<CityFactory> */
    use HasFactory;

    /** @return HasMany<Order, $this> */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /** @return HasMany<ExecutorProfile, $this> */
    public function executorProfiles(): HasMany
    {
        return $this->hasMany(ExecutorProfile::class);
    }

    /**
     * Все города по алфавиту — для выбора в формах.
     *
     * @return Collection<int, City>
     */
    public static function options(): Collection
    {
        return static::query()->orderBy('name')->get(['id', 'name']);
    }
}
