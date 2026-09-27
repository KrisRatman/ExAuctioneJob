<div class="mx-auto max-w-6xl px-4 py-10">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight">Мои заказы</h1>
            <p class="mt-1 text-slate-500">Откройте заказ, чтобы сравнить предложения и выбрать исполнителя.</p>
        </div>
        <a href="{{ route('customer.orders.create') }}" class="btn-primary">
            <x-heroicon-o-plus class="size-4" stroke-width="2.5" /> Разместить заказ
        </a>
    </div>

    <div class="mt-8 flex gap-1 border-b border-slate-200" role="tablist">
        @foreach (['active' => 'Активные', 'archive' => 'Архив'] as $key => $label)
            <button type="button" wire:click="$set('tab', '{{ $key }}')" role="tab" aria-selected="{{ $tab === $key ? 'true' : 'false' }}"
                    @class([
                        '-mb-px border-b-2 px-4 py-2.5 text-sm font-bold transition',
                        'border-brand-600 text-brand-700' => $tab === $key,
                        'border-transparent text-slate-500 hover:text-ink' => $tab !== $key,
                    ])>
                {{ $label }} <span class="ml-1 text-slate-400">{{ $this->counts[$key] }}</span>
            </button>
        @endforeach
    </div>

    <div class="mt-6 space-y-3">
        @forelse ($this->orders as $order)
            <a href="{{ route('customer.orders.show', $order) }}" wire:key="order-{{ $order->id }}"
               class="card flex flex-col gap-4 p-5 transition hover:border-slate-300 hover:shadow-sm sm:flex-row sm:items-center">
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="badge {{ $order->status->badgeClasses() }}">{{ $order->status->getLabel() }}</span>
                        <span class="text-xs text-slate-400">{{ $order->created_at->translatedFormat('j F Y') }}</span>
                    </div>
                    <h2 class="mt-2 truncate text-lg font-bold">{{ $order->title }}</h2>
                    <div class="mt-2 flex flex-wrap gap-1.5">
                        @foreach ($order->categories as $tag)
                            <span class="tag">{{ $tag->name }}</span>
                        @endforeach
                    </div>
                </div>
                <div class="flex items-center justify-between gap-6 sm:flex-col sm:items-end sm:gap-1">
                    <div class="text-lg font-extrabold">{{ rub($order->starting_price) }}</div>
                    @if ($order->isOpen())
                        <div @class(['text-sm font-bold', 'text-brand-600' => $order->pending_bids_count > 0, 'text-slate-400' => $order->pending_bids_count === 0])>
                            @if ($order->pending_bids_count > 0)
                                {{ $order->pending_bids_count }} {{ plural($order->pending_bids_count, 'исполнитель готов', 'исполнителя готовы', 'исполнителей готовы') }} взяться
                            @else
                                Пока нет предложений
                            @endif
                        </div>
                    @elseif ($order->executor)
                        <div class="text-sm text-slate-500">Исполнитель: <span class="font-semibold text-ink">{{ $order->executor->name }}</span></div>
                    @endif
                </div>
            </a>
        @empty
            <div class="card px-6 py-14 text-center">
                <x-heroicon-o-clipboard-document-list class="mx-auto size-10 text-slate-300" />
                <p class="mt-3 font-bold">{{ $tab === 'archive' ? 'В архиве пусто' : 'У вас пока нет активных заказов' }}</p>
                @if ($tab === 'active')
                    <p class="mt-1 text-sm text-slate-500">Опишите задачу и назовите цену — исполнители предложат свои условия.</p>
                    <a href="{{ route('customer.orders.create') }}" class="btn-primary mt-5">Разместить заказ</a>
                @endif
            </div>
        @endforelse
    </div>

    <div class="mt-6">{{ $this->orders->links() }}</div>
</div>
