<div class="mx-auto max-w-6xl px-4 py-10">
    <h1 class="text-3xl font-extrabold tracking-tight">Мои предложения</h1>
    <p class="mt-1 text-slate-500">Предложение можно изменить в ленте, пока заказчик не выбрал исполнителя.</p>

    <div class="mt-8 space-y-3">
        @forelse ($this->bids as $bid)
            <article wire:key="bid-{{ $bid->id }}" class="card flex flex-col gap-4 p-5 sm:flex-row sm:items-center">
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="badge {{ $bid->status->badgeClasses() }}">{{ $bid->status->getLabel() }}</span>
                        <span class="text-xs text-slate-400">Заказ: {{ $bid->order->status->getLabel() }} · {{ $bid->order->customer->name }}</span>
                    </div>
                    <h2 class="mt-2 truncate text-lg font-bold">{{ $bid->order->title }}</h2>
                    <div class="mt-2 flex flex-wrap gap-1.5">
                        @foreach ($bid->order->categories as $tag)
                            <span class="tag">{{ $tag->name }}</span>
                        @endforeach
                    </div>
                </div>
                <div class="flex items-center justify-between gap-6 sm:flex-col sm:items-end sm:gap-1">
                    <div class="text-right">
                        <div class="text-lg font-extrabold">{{ rub($bid->offer_price) }}</div>
                        <div class="text-xs text-slate-500">стартовая {{ rub($bid->order->starting_price) }} · {{ $bid->duration_days }} {{ plural($bid->duration_days, 'день', 'дня', 'дней') }}</div>
                    </div>
                    @if ($bid->status === \App\Enums\BidStatus::Accepted && $bid->order->conversation)
                        <a href="{{ route('chats.show', $bid->order->conversation) }}" class="btn-primary">
                            <x-heroicon-o-chat-bubble-left-right class="size-4" /> Чат с заказчиком
                        </a>
                    @endif
                </div>
            </article>
        @empty
            <div class="card px-6 py-14 text-center">
                <x-heroicon-o-paper-airplane class="mx-auto size-10 text-slate-300" />
                <p class="mt-3 font-bold">Вы ещё не делали предложений</p>
                <a href="{{ route('executor.feed') }}" class="btn-primary mt-5">Открыть ленту заказов</a>
            </div>
        @endforelse
    </div>

    <div class="mt-6">{{ $this->bids->links() }}</div>
</div>
