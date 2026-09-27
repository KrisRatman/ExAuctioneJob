{{-- Отзывы об исполнителе. Связь customer у отзывов должна быть загружена. --}}
@props(['reviews'])

<div {{ $attributes->class('space-y-2') }}>
    @forelse ($reviews as $review)
        <div wire:key="review-{{ $review->id }}" class="rounded-xl bg-white p-3 ring-1 ring-slate-200" data-review-item>
            <div class="flex flex-wrap items-center justify-between gap-2">
                <x-stars :rating="$review->rating" />
                <span class="text-xs text-slate-400">{{ $review->customer->name }} · {{ $review->created_at->translatedFormat('j F Y') }}</span>
            </div>
            @if ($review->comment)
                <p class="mt-1.5 text-sm leading-relaxed text-slate-700">{{ $review->comment }}</p>
            @endif
        </div>
    @empty
        <p class="text-sm text-slate-500">Отзывов пока нет.</p>
    @endforelse
</div>
