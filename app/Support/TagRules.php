<?php

namespace App\Support;

use Illuminate\Validation\Rule;

/**
 * Правила валидации выбранных тегов: только подкатегории, не разделы.
 */
class TagRules
{
    /** @return list<mixed> */
    public static function executor(): array
    {
        return ['required', 'array', 'min:'.config('ideajob.executor_tags.min'), 'max:'.config('ideajob.executor_tags.max')];
    }

    /** @return list<mixed> */
    public static function order(): array
    {
        return ['required', 'array', 'min:'.config('ideajob.order_tags.min'), 'max:'.config('ideajob.order_tags.max')];
    }

    /** @return list<mixed> */
    public static function each(): array
    {
        return ['integer', 'distinct', Rule::exists('categories', 'id')->whereNotNull('parent_id')];
    }
}
