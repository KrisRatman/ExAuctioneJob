<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Нормированный вектор текста тега или заказа — для поиска по смыслу.
 */
#[Fillable(['embeddable_type', 'embeddable_id', 'model', 'content_hash', 'vector'])]
class Embedding extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'vector' => 'array',
        ];
    }

    /** @return MorphTo<Model, $this> */
    public function embeddable(): MorphTo
    {
        return $this->morphTo();
    }
}
