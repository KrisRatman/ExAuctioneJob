{{-- Пустое состояние списка: иконка, заголовок, пояснение и действие в слоте. --}}
@props(['icon' => 'heroicon-o-inbox', 'title', 'text' => null])

<div {{ $attributes->class('card px-6 py-14 text-center') }}>
    <span class="mx-auto grid size-14 place-items-center rounded-2xl bg-slate-100 text-slate-400">
        <x-dynamic-component :component="$icon" class="size-7" />
    </span>
    <p class="mt-4 font-bold">{{ $title }}</p>
    @if ($text)
        <p class="mx-auto mt-1 max-w-md text-sm text-slate-500">{{ $text }}</p>
    @endif
    @if ($slot->isNotEmpty())
        <div class="mt-5">{{ $slot }}</div>
    @endif
</div>
