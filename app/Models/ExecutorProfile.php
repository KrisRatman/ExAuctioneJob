<?php

namespace App\Models;

use Database\Factories\ExecutorProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Анкета исполнителя: описание и кэш рейтинга.
 */
#[Fillable(['user_id', 'description', 'rating_avg', 'reviews_count', 'completed_orders_count'])]
class ExecutorProfile extends Model
{
    /** @use HasFactory<ExecutorProfileFactory> */
    use HasFactory;

    protected $attributes = [
        'reviews_count' => 0,
        'completed_orders_count' => 0,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rating_avg' => 'float',
            'reviews_count' => 'integer',
            'completed_orders_count' => 'integer',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
