<?php

namespace App\Notifications;

use App\Models\Review;

/**
 * Исполнителю: заказчик оставил оценку.
 */
class ReviewReceived extends SiteNotification
{
    public function __construct(public Review $review) {}

    protected function title(): string
    {
        return 'Новая оценка: '.str_repeat('★', $this->review->rating).str_repeat('☆', 5 - $this->review->rating);
    }

    protected function body(): string
    {
        return '«'.$this->review->order->title.'»'.($this->review->comment ? ': '.$this->review->comment : '');
    }

    protected function url(): string
    {
        return route('executor.profile');
    }

    protected function icon(): string
    {
        return 'heroicon-o-star';
    }
}
