{{--
    Аватар с инициалом. Цвет стабильный для имени и спокойный — красный остаётся для акцентов.
    Размеры: sm (32px), md (40px), lg (48px).
--}}
@props(['name', 'size' => 'md'])

@php
    $palettes = [
        'bg-sky-100 text-sky-800',
        'bg-emerald-100 text-emerald-800',
        'bg-amber-100 text-amber-800',
        'bg-violet-100 text-violet-800',
        'bg-teal-100 text-teal-800',
        'bg-slate-200 text-slate-700',
        'bg-orange-100 text-orange-800',
        'bg-indigo-100 text-indigo-800',
    ];
    $color = $palettes[crc32($name) % count($palettes)];
    $sizeClasses = match ($size) {
        'xs' => 'size-6 text-[11px]',
        'sm' => 'size-8 text-sm',
        'lg' => 'size-12 text-lg',
        default => 'size-10 text-base',
    };
@endphp

<span {{ $attributes->class(['grid shrink-0 place-items-center rounded-full font-extrabold', $color, $sizeClasses]) }} aria-hidden="true">{{ mb_strtoupper(mb_substr($name, 0, 1)) }}</span>
