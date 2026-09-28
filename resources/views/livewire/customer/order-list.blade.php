<div class="mx-auto max-w-6xl px-4 py-6 sm:py-10">
    <x-page-header title="Мои заказы" />

    <div class="mt-5 flex gap-6 border-b border-slate-200" role="tablist">
        @foreach (['active' => 'Активные', 'archive' => 'Архив'] as $key => $label)
            <button type="button" wire:click="$set('tab', '{{ $key }}')" role="tab" aria-selected="{{ $tab === $key ? 'true' : 'false' }}"
                    @class([
                        '-mb-px border-b-2 pb-3 text-sm font-bold transition',
                        'border-brand-600 text-ink' => $tab === $key,
                        'border-transparent text-slate-500 hover:text-ink' => $tab !== $key,
                    ])>
                {{ $label }} <span class="ml-0.5 font-semibold text-slate-400">{{ $this->counts[$key] }}</span>
            </button>
        @endforeach
    </div>

    <div class="mt-4 space-y-3">
        @forelse ($this->orders as $order)
            <a href="{{ route('customer.orders.show', $order) }}" wire:key="order-{{ $order->id }}"
               class="card group block p-4 transition hover:border-slate-300 hover:shadow-sm sm:p-5">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <span class="badge {{ $order->status->badgeClasses() }}">{{ $order->status->getLabel() }}</span>
                        <h2 class="mt-2 text-base font-bold group-hover:text-brand-600 sm:text-lg">{{ $order->title }}</h2>
                    </div>
                    <div class="shrink-0 text-right text-lg font-extrabold whitespace-nowrap sm:text-xl">{{ rub($order->starting_price) }}</div>
                </div>

                <div class="mt-3 flex flex-wrap gap-1.5">
                    @foreach ($order->categories as $tag)
                        <span class="tag">{{ $tag->name }}</span>
                    @endforeach
                </div>

                <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-3 text-xs">
                    <span class="text-slate-500">{{ $order->city->name }} · {{ $order->created_at->translatedFormat('j F Y') }}</span>
                    @if ($order->isOpen())
                        @if ($order->pending_bids_count > 0)
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-50 px-2.5 py-1 font-bold text-brand-700">
                                <x-heroicon-s-users class="size-3.5" />
                                {{ $order->pending_bids_count }} {{ plural($order->pending_bids_count, 'исполнитель готов', 'исполнителя готовы', 'исполнителей готовы') }} взяться
                            </span>
                        @else
                            <span class="font-semibold text-slate-400">Ждём предложений</span>
                        @endif
                    @elseif ($order->executor)
                        <span class="inline-flex items-center gap-2 text-slate-500">
                            <x-avatar :name="$order->executor->name" size="xs" />
                            <span class="font-semibold text-ink">{{ $order->executor->name }}</span>
                        </span>
                    @endif
                </div>
            </a>
        @empty
            @if ($tab === 'archive')
                <x-empty-state icon="heroicon-o-archive-box" title="В архиве пусто" text="Здесь будут выполненные и отменённые заказы." />
            @else
                <x-empty-state icon="heroicon-o-clipboard-document-list" title="Активных заказов нет"
                               text="Опишите задачу и назовите цену — исполнители предложат свои условия.">
                    <a href="{{ route('customer.orders.create') }}" class="btn-primary">Разместить заказ</a>
                </x-empty-state>
            @endif
        @endforelse
    </div>

    <div class="mt-6">{{ $this->orders->links() }}</div>
</div>
