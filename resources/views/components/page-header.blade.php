{{-- Заголовок страницы: название, короткое пояснение (необязательно) и действия справа. --}}
@props(['title', 'subtitle' => null])

<div {{ $attributes->class('flex flex-wrap items-end justify-between gap-x-6 gap-y-3') }}>
    <div class="min-w-0">
        <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl">{{ $title }}</h1>
        @if ($subtitle)
            <p class="mt-1 text-sm text-slate-500 sm:text-base">{{ $subtitle }}</p>
        @endif
    </div>
    @if (isset($actions) && $actions->isNotEmpty())
        <div class="flex shrink-0 items-center gap-2">{{ $actions }}</div>
    @endif
</div>
