<?php

it('formats rubles with thin spaces', function () {
    expect(rub(15000))->toBe("15\u{202F}000\u{00A0}₽")
        ->and(rub(500))->toBe("500\u{00A0}₽");
});

it('picks the Russian plural form', function (int $count, string $expected) {
    expect(plural($count, 'день', 'дня', 'дней'))->toBe($expected);
})->with([
    [1, 'день'], [2, 'дня'], [4, 'дня'], [5, 'дней'], [11, 'дней'], [12, 'дней'],
    [14, 'дней'], [21, 'день'], [22, 'дня'], [25, 'дней'], [101, 'день'], [111, 'дней'],
]);
