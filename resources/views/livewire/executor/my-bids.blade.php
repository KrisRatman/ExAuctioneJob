<div class="mx-auto max-w-6xl px-4 py-6 sm:py-10">
    <x-page-header title="Мои предложения" />

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
        @forelse ($this->bids as $bid)
            @php($working = $this->isWorking($bid))
            <article wire:key="bid-{{ $bid->id }}" class="card p-4 sm:p-5">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            @if ($bid->status === \App\Enums\BidStatus::Accepted)
                                <span class="badge {{ $bid->order->status->badgeClasses() }}">{{ $bid->order->status->getLabel() }}</span>
                            @else
                                <span class="badge {{ $bid->status->badgeClasses() }}">{{ $bid->status->getLabel() }}</span>
                            @endif
                        </div>
                        <h2 class="mt-2 text-base font-bold sm:text-lg">{{ $bid->order->title }}</h2>
                    </div>
                    <div class="shrink-0 text-right">
                        <div class="text-lg font-extrabold whitespace-nowrap sm:text-xl">{{ rub($bid->offer_price) }}</div>
                        <div class="text-[11px] font-semibold text-slate-400">{{ $bid->duration_days }} {{ plural($bid->duration_days, 'день', 'дня', 'дней') }}</div>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-3">
                    <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-500">
                        <span class="inline-flex items-center gap-1"><x-heroicon-o-user class="size-3.5" /> {{ $bid->order->customer->name }}</span>
                        <span>стартовая {{ rub($bid->order->starting_price) }}</span>
                    </div>
                    @if ($bid->status === \App\Enums\BidStatus::Accepted && $bid->order->conversation)
                        <a href="{{ route('chats.show', $bid->order->conversation) }}" @class(['px-3 py-1.5 text-xs', 'btn-primary' => $working, 'btn-secondary' => ! $working])>
                            <x-heroicon-o-chat-bubble-left-right class="size-4" /> Чат с заказчиком
                        </a>
                    @elseif ($bid->status === \App\Enums\BidStatus::Pending)
                        <a href="{{ route('executor.feed') }}" class="text-xs font-bold text-slate-500 hover:text-brand-600">Изменить в ленте</a>
                    @endif
                </div>
            </article>
        @empty
            @if ($tab === 'archive')
                <x-empty-state icon="heroicon-o-archive-box" title="В архиве пусто" text="Здесь будут отклонённые предложения и закрытые заказы." />
            @else
                <x-empty-state icon="heroicon-o-paper-airplane" title="Активных предложений нет" text="Найдите подходящий заказ и предложите свою цену.">
                    <a href="{{ route('executor.feed') }}" class="btn-primary">Открыть ленту заказов</a>
                </x-empty-state>
            @endif
        @endforelse
    </div>

    <div class="mt-6">{{ $this->bids->links() }}</div>
</div>
