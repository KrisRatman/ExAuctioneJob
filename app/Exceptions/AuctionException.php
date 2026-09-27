<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Действие нарушает правила аукциона. Сообщение показывается пользователю как есть.
 */
class AuctionException extends RuntimeException
{
    public static function orderClosed(): self
    {
        return new self('Заказ уже не принимает предложения.');
    }

    public static function priceTooHigh(int $maxPrice): self
    {
        return new self('Цена не может быть выше '.rub($maxPrice).' — это стартовая цена заказчика плюс '.rub((int) config('ideajob.max_bid_markup')).'.');
    }
}
