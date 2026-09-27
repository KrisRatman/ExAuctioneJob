{{-- Средний рейтинг исполнителя и число выполненных заказов (кэш из анкеты). --}}
@props(['profile'])

<span {{ $attributes->class('inline-flex flex-wrap items-center gap-x-3 gap-y-1 text-xs font-semibold text-slate-500') }}>
    @if ($profile?->rating_avg)
        <span class="inline-flex items-center gap-1 text-ink" title="Средняя оценка по {{ $profile->reviews_count }} {{ plural($profile->reviews_count, 'отзыву', 'отзывам', 'отзывам') }}">
            <x-heroicon-s-star class="size-4 text-amber-400" />
            {{ number_format($profile->rating_avg, 1, ',', '') }}
        </span>
    @else
        <span class="inline-flex items-center gap-1">
            <x-heroicon-o-star class="size-4 text-slate-300" /> Нет оценок
        </span>
    @endif
    <span>{{ $profile?->completed_orders_count ?? 0 }} {{ plural($profile?->completed_orders_count ?? 0, 'заказ выполнен', 'заказа выполнено', 'заказов выполнено') }}</span>
</span>
