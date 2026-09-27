<div class="mx-auto max-w-6xl px-4 py-10">
    @php($pendingCount = $this->bids->where('status', \App\Enums\BidStatus::Pending)->count())

    <a href="{{ route('customer.orders.index') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-slate-500 hover:text-ink">
        <x-heroicon-m-arrow-left class="size-4" /> Мои заказы
    </a>

    <div class="mt-4 grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.15fr)] lg:items-start">
        {{-- Слева: заказ --}}
        <section class="card p-6 lg:sticky lg:top-24" aria-labelledby="order-title">
            <div class="flex flex-wrap items-center gap-2">
                <span class="badge {{ $order->status->badgeClasses() }}">{{ $order->status->getLabel() }}</span>
                <span class="text-xs text-slate-400">Опубликован {{ $order->created_at->translatedFormat('j F Y, H:i') }}</span>
            </div>
            <h1 id="order-title" class="mt-3 text-2xl font-extrabold tracking-tight">{{ $order->title }}</h1>

            <dl class="mt-5 grid grid-cols-2 gap-4 rounded-xl bg-canvas p-4">
                <div>
                    <dt class="text-xs font-semibold text-slate-500">Стартовая цена</dt>
                    <dd class="mt-0.5 text-xl font-extrabold">{{ rub($order->starting_price) }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500">Предложений</dt>
                    <dd class="mt-0.5 text-xl font-extrabold">{{ $this->bids->count() }}</dd>
                </div>
            </dl>

            <div class="mt-5 flex flex-wrap gap-1.5">
                @foreach ($order->categories as $tag)
                    <span class="tag">{{ $tag->name }}</span>
                @endforeach
            </div>

            <div class="mt-5 whitespace-pre-line text-[15px] leading-relaxed text-slate-700">{{ $order->description }}</div>

            @if ($order->executor)
                <div class="mt-6 flex items-center gap-3 rounded-xl border border-sky-200 bg-sky-50 p-4">
                    <x-heroicon-o-user-circle class="size-8 text-sky-600" />
                    <div class="text-sm">
                        <div class="text-slate-500">Исполнитель</div>
                        <div class="font-bold">{{ $order->executor->name }}</div>
                    </div>
                </div>
            @endif

            @if ($order->isOpen())
                <div class="mt-6 border-t border-slate-100 pt-5">
                    <button type="button" wire:click="cancel"
                            wire:confirm="Снять заказ с аукциона? {{ $pendingCount ? 'Исполнители, которые откликнулись, получат отказ.' : '' }}"
                            class="text-sm font-semibold text-slate-500 hover:text-brand-700">
                        Отменить заказ
                    </button>
                </div>
            @endif
        </section>

        {{-- Справа: предложения --}}
        <section aria-labelledby="bids-title">
            <div class="flex items-baseline justify-between gap-4">
                <h2 id="bids-title" class="text-xl font-extrabold tracking-tight">Предложения</h2>
                @if ($order->isOpen() && $pendingCount)
                    <span class="text-sm font-bold text-brand-600">{{ $pendingCount }} {{ plural($pendingCount, 'исполнитель готов', 'исполнителя готовы', 'исполнителей готовы') }} взяться</span>
                @endif
            </div>

            <div class="mt-4 space-y-3">
                @forelse ($this->bids as $bid)
                    @php($isSelected = $selectedBidId === $bid->id)
                    @php($profile = $bid->executor->executorProfile)
                    <article wire:key="bid-{{ $bid->id }}" data-bid="{{ $bid->id }}"
                             @class([
                                 'card overflow-hidden transition',
                                 'border-brand-300 ring-4 ring-brand-50' => $isSelected,
                                 'border-emerald-300' => $bid->status === \App\Enums\BidStatus::Accepted,
                                 'opacity-60' => $bid->status === \App\Enums\BidStatus::Rejected,
                             ])>
                        <button type="button" wire:click="selectBid({{ $bid->id }})" class="flex w-full items-center gap-4 p-4 text-left hover:bg-slate-50" aria-expanded="{{ $isSelected ? 'true' : 'false' }}">
                            <span class="grid size-11 shrink-0 place-items-center rounded-full bg-slate-900 font-bold text-white">{{ mb_substr($bid->executor->name, 0, 1) }}</span>
                            <span class="min-w-0 flex-1">
                                <span class="flex flex-wrap items-center gap-2">
                                    <span class="truncate font-bold">{{ $bid->executor->name }}</span>
                                    @if ($bid->status !== \App\Enums\BidStatus::Pending)
                                        <span class="badge {{ $bid->status->badgeClasses() }}">{{ $bid->status->getLabel() }}</span>
                                    @endif
                                </span>
                                <x-rating :profile="$profile" class="mt-1" />
                            </span>
                            <span class="shrink-0 text-right">
                                <span class="block text-lg font-extrabold">{{ rub($bid->offer_price) }}</span>
                                <span class="block text-xs font-semibold text-slate-500">{{ $bid->duration_days }} {{ plural($bid->duration_days, 'день', 'дня', 'дней') }}</span>
                            </span>
                            <x-heroicon-m-chevron-down :class="\Illuminate\Support\Arr::toCssClasses(['size-5 shrink-0 text-slate-400 transition', 'rotate-180' => $isSelected])" />
                        </button>

                        @if ($isSelected)
                            <div class="space-y-5 border-t border-slate-100 bg-canvas/60 p-5">
                                <div>
                                    <h3 class="text-xs font-extrabold uppercase tracking-wide text-slate-400">Как сделает</h3>
                                    <p class="mt-1.5 whitespace-pre-line text-sm leading-relaxed text-slate-700">{{ $bid->approach_description }}</p>
                                </div>
                                <div>
                                    <h3 class="text-xs font-extrabold uppercase tracking-wide text-slate-400">Об исполнителе</h3>
                                    <p class="mt-1.5 whitespace-pre-line text-sm leading-relaxed text-slate-700">{{ $profile?->description }}</p>
                                    <div class="mt-3 flex flex-wrap gap-1.5">
                                        @foreach ($bid->executor->categories as $tag)
                                            <span class="tag">{{ $tag->name }}</span>
                                        @endforeach
                                    </div>
                                </div>
                                @if ($order->isOpen() && $bid->status === \App\Enums\BidStatus::Pending)
                                    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 pt-4">
                                        <span class="text-xs text-slate-500">Остальные предложения будут отклонены.</span>
                                        <button type="button" wire:click="accept({{ $bid->id }})"
                                                wire:confirm="Принять исполнителя {{ $bid->executor->name }} за {{ rub($bid->offer_price) }}?"
                                                wire:loading.attr="disabled" class="btn-primary">
                                            <x-heroicon-o-check class="size-4" stroke-width="2.5" /> Принять исполнителя
                                        </button>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </article>
                @empty
                    <div class="card px-6 py-14 text-center">
                        <x-heroicon-o-inbox class="mx-auto size-10 text-slate-300" />
                        <p class="mt-3 font-bold">Предложений пока нет</p>
                        <p class="mt-1 text-sm text-slate-500">Заказ уже в ленте исполнителей с подходящими тегами. Загляните чуть позже.</p>
                    </div>
                @endforelse
            </div>
        </section>
    </div>
</div>
