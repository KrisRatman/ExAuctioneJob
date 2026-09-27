<div class="mx-auto max-w-6xl px-4 py-10">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight">Лента заказов</h1>
            <p class="mt-1 text-slate-500">Заказы с вашими тегами. Нажмите на заказ, чтобы предложить цену и срок.</p>
        </div>
        <label class="relative w-full sm:w-72">
            <span class="sr-only">Поиск по заказам</span>
            <x-heroicon-o-magnifying-glass class="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-slate-400" />
            <input type="search" wire:model.live.debounce.400ms="search" placeholder="Поиск по заказам" class="input pl-10">
        </label>
    </div>

    @if ($this->myTags->isEmpty())
        <div class="card mt-8 px-6 py-14 text-center">
            <p class="font-bold">В профиле не отмечены специализации</p>
            <p class="mt-1 text-sm text-slate-500">Без тегов лента пуста — заказы подбираются по совпадению тегов.</p>
            <a href="{{ route('executor.profile') }}" class="btn-primary mt-5">Выбрать специализации</a>
        </div>
    @else
        <div class="mt-6 flex flex-wrap gap-2" role="group" aria-label="Фильтр по тегу">
            <button type="button" wire:click="$set('tag', null)"
                    @class(['rounded-xl px-3 py-1.5 text-sm font-bold transition', 'bg-brand-600 text-white' => ! $tag, 'bg-white text-slate-600 ring-1 ring-slate-200 hover:ring-slate-300' => $tag])>
                Все мои теги
            </button>
            @foreach ($this->myTags as $myTag)
                <button type="button" wire:key="filter-{{ $myTag->id }}" wire:click="$set('tag', {{ $myTag->id }})"
                        @class(['rounded-xl px-3 py-1.5 text-sm font-bold transition', 'bg-brand-600 text-white' => $tag === $myTag->id, 'bg-white text-slate-600 ring-1 ring-slate-200 hover:ring-slate-300' => $tag !== $myTag->id])>
                    {{ $myTag->name }}
                </button>
            @endforeach
        </div>

        <div class="mt-6 space-y-3">
            @forelse ($this->orders as $order)
                @php($myBid = $order->bids->first())
                <button type="button" wire:key="order-{{ $order->id }}" wire:click="open({{ $order->id }})" data-order="{{ $order->id }}"
                        class="card block w-full p-5 text-left transition hover:border-slate-300 hover:shadow-sm">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2 text-xs text-slate-400">
                                <span>{{ $order->created_at->diffForHumans() }}</span>
                                @if ($myBid)
                                    <span class="badge bg-emerald-50 text-emerald-700 ring-emerald-200"><x-heroicon-m-check class="size-3.5" /> Вы откликнулись</span>
                                @endif
                            </div>
                            <h2 class="mt-1.5 text-lg font-bold">{{ $order->title }}</h2>
                            <p class="mt-1.5 line-clamp-2 text-sm text-slate-600">{{ $order->description }}</p>
                            <div class="mt-3 flex flex-wrap gap-1.5">
                                @foreach ($order->categories as $orderTag)
                                    <span class="tag">{{ $orderTag->name }}</span>
                                @endforeach
                            </div>
                        </div>
                        <div class="flex items-center justify-between gap-4 sm:flex-col sm:items-end sm:gap-1">
                            <div class="text-xl font-extrabold">{{ rub($order->starting_price) }}</div>
                            <div class="text-xs font-semibold text-slate-500">{{ $order->bids_count }} {{ plural($order->bids_count, 'предложение', 'предложения', 'предложений') }}</div>
                        </div>
                    </div>
                </button>
            @empty
                <div class="card px-6 py-14 text-center">
                    <x-heroicon-o-inbox class="mx-auto size-10 text-slate-300" />
                    <p class="mt-3 font-bold">Подходящих заказов пока нет</p>
                    <p class="mt-1 text-sm text-slate-500">Новые заказы с вашими тегами появятся здесь. Можно добавить специализации в <a href="{{ route('executor.profile') }}" class="font-semibold text-brand-600">профиле</a>.</p>
                </div>
            @endforelse
        </div>

        <div class="mt-6">{{ $this->orders->links() }}</div>
    @endif

    {{-- Модалка: заказ и форма предложения --}}
    @if ($order = $this->openOrder)
        <div class="fixed inset-0 z-50 flex items-end justify-center bg-slate-900/50 p-0 sm:items-center sm:p-4"
             wire:key="modal-{{ $order->id }}" x-data x-on:keydown.escape.window="$wire.close()" role="dialog" aria-modal="true" aria-labelledby="modal-title">
            <div class="absolute inset-0" wire:click="close"></div>
            <div class="relative max-h-[92vh] w-full max-w-2xl overflow-y-auto rounded-t-2xl bg-white shadow-2xl sm:rounded-2xl">
                <div class="sticky top-0 flex items-start justify-between gap-4 border-b border-slate-100 bg-white px-6 py-4">
                    <div>
                        <div class="text-xs text-slate-400">Заказчик: {{ $order->customer->name }} · {{ $order->bids_count }} {{ plural($order->bids_count, 'предложение', 'предложения', 'предложений') }}</div>
                        <h2 id="modal-title" class="mt-1 text-xl font-extrabold tracking-tight">{{ $order->title }}</h2>
                    </div>
                    <button type="button" wire:click="close" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-ink" aria-label="Закрыть">
                        <x-heroicon-o-x-mark class="size-6" />
                    </button>
                </div>

                <div class="space-y-5 px-6 py-5">
                    <div class="flex flex-wrap gap-1.5">
                        @foreach ($order->categories as $orderTag)
                            <span class="tag">{{ $orderTag->name }}</span>
                        @endforeach
                    </div>
                    <div class="whitespace-pre-line text-[15px] leading-relaxed text-slate-700">{{ $order->description }}</div>
                    <div class="flex flex-wrap gap-6 rounded-xl bg-canvas p-4">
                        <div>
                            <div class="text-xs font-semibold text-slate-500">Стартовая цена</div>
                            <div class="text-lg font-extrabold">{{ rub($order->starting_price) }}</div>
                        </div>
                        <div>
                            <div class="text-xs font-semibold text-slate-500">Можно предложить до</div>
                            <div class="text-lg font-extrabold text-brand-600">{{ rub($order->maxOfferPrice()) }}</div>
                        </div>
                    </div>
                </div>

                <form wire:submit="submitBid" class="space-y-5 border-t border-slate-100 bg-canvas/60 px-6 py-5"
                      x-data="{ price: @js($offerPrice), max: {{ $order->maxOfferPrice() }} }">
                    <h3 class="font-extrabold">{{ $order->bids->isNotEmpty() ? 'Ваше предложение' : 'Предложить свои условия' }}</h3>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-field label="Ваша цена, ₽" for="offerPrice">
                            <input id="offerPrice" type="number" wire:model="offerPrice" x-on:input="price = $event.target.valueAsNumber" min="{{ config('ideajob.min_price') }}" max="{{ $order->maxOfferPrice() }}" step="1" inputmode="numeric" class="input no-spin"
                                   :class="price > max && 'border-brand-500 ring-4 ring-brand-100'">
                            <p x-show="price > max" x-cloak class="mt-1.5 text-sm font-medium text-brand-700">Не выше {{ rub($order->maxOfferPrice()) }}</p>
                        </x-field>
                        <x-field label="Срок, дней" for="durationDays">
                            <input id="durationDays" type="number" wire:model="durationDays" min="1" max="{{ config('ideajob.max_duration_days') }}" step="1" inputmode="numeric" class="input no-spin">
                        </x-field>
                    </div>

                    <x-field label="Как вы это сделаете" for="approach" hint="Опыт в похожих задачах, этапы, что нужно от заказчика.">
                        <textarea id="approach" wire:model="approach" rows="5" class="input"></textarea>
                    </x-field>

                    <div class="flex items-center justify-end gap-3">
                        <button type="button" wire:click="close" class="btn-secondary">Отмена</button>
                        <button type="submit" class="btn-primary" wire:loading.attr="disabled" :disabled="price > max">
                            {{ $order->bids->isNotEmpty() ? 'Сохранить изменения' : 'Отправить предложение' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
