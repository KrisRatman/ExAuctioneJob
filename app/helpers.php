<?php

if (! function_exists('rub')) {
    /** 15000 → «15 000 ₽» (неразрывные пробелы, цены — целые рубли). */
    function rub(int $amount): string
    {
        return number_format($amount, 0, ',', "\u{202F}")."\u{00A0}₽";
    }
}

if (! function_exists('plural')) {
    /**
     * Русское склонение после числа: plural(5, 'исполнитель', 'исполнителя', 'исполнителей') → «исполнителей».
     */
    function plural(int $count, string $one, string $few, string $many): string
    {
        $mod100 = abs($count) % 100;
        $mod10 = $mod100 % 10;

        return match (true) {
            $mod100 >= 11 && $mod100 <= 14 => $many,
            $mod10 === 1 => $one,
            $mod10 >= 2 && $mod10 <= 4 => $few,
            default => $many,
        };
    }
}
