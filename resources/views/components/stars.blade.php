{{-- Оценка звёздами: 1–5. --}}
@props(['rating'])

<span {{ $attributes->class('inline-flex items-center gap-0.5') }} title="{{ $rating }} из 5" aria-label="Оценка {{ $rating }} из 5">
    @foreach (range(1, 5) as $star)
        <x-heroicon-s-star :class="$star <= $rating ? 'size-4 text-amber-400' : 'size-4 text-slate-300'" />
    @endforeach
</span>
