<div class="mx-auto max-w-6xl px-4 py-6 sm:py-10">
    <x-page-header title="Лента заказов" :subtitle="$this->myCity ? 'Заказы в городе '.$this->myCity->name : null">
        <x-slot:actions>
            <label class="relative hidden w-72 sm:block">
                <span class="sr-only">Поиск по заказам</span>
                <x-heroicon-o-magnifying-glass class="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-slate-400" />
                <input type="search" wire:model.live.debounce.400ms="search" placeholder="Поиск по заказам" class="input pl-10">
            </label>
        </x-slot:actions>
    </x-page-header>

    @if ($this->myCity === null)
        <x-empty-state class="mt-6" icon="heroicon-o-map-pin" title="В профиле не указан город"
                       text="Работа очная — заказы показываются только из вашего города.">
            <a href="{{ route('executor.profile') }}" class="btn-primary">Указать город</a>
        </x-empty-state>
    @elseif ($this->myTags->isEmpty())
        <x-empty-state class="mt-6" icon="heroicon-o-tag" title="В профиле не отмечены специализации"
                       text="Заказы подбираются по совпадению тегов — без них лента пуста.">
            <a href="{{ route('executor.profile') }}" class="btn-primary">Выбрать специализации</a>
        </x-empty-state>
    @else
        <div class="mt-5 grid grid-cols-1 gap-6 lg:mt-6 lg:grid-cols-[15rem_minmax(0,1fr)] lg:items-start">
            {{-- Теги: колонка на десктопе, горизонтальная прокрутка на телефоне --}}
            <aside class="min-w-0 lg:sticky lg:top-24" aria-label="Фильтр по тегам">
                <label class="relative mb-3 block sm:hidden">
                    <span class="sr-only">Поиск по заказам</span>
                    <x-heroicon-o-magnifying-glass class="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-slate-400" />
                    <input type="search" wire:model.live.debounce.400ms="search" placeholder="Поиск по заказам" class="input pl-10">
                </label>

                <div class="-mx-4 flex gap-2 overflow-x-auto px-4 pb-1 [scrollbar-width:none] lg:card lg:mx-0 lg:flex-col lg:gap-0.5 lg:overflow-visible lg:p-2">
                    <div class="hidden px-3 pt-2 pb-1 text-xs font-extrabold tracking-wide text-slate-400 uppercase lg:block">Мои теги</div>
                    @foreach ([null => ['Все заказы', $this->totalCount]] + $this->myTags->mapWithKeys(fn ($t) => [$t->id => [$t->name, $t->open_orders_count]])->all() as $id => [$name, $count])
                        @php($active = $tag === ($id ?: null))
                        <button type="button" wire:key="filter-{{ $id ?: 'all' }}" wire:click="$set('tag', {{ $id ?: 'null' }})"
                                @class([
                                    'flex shrink-0 items-center justify-between gap-3 rounded-xl px-3 py-2 text-left text-sm font-semibold whitespace-nowrap transition lg:w-full',
                                    'bg-brand-600 text-white lg:bg-brand-50 lg:text-brand-700' => $active,
                                    'bg-white text-slate-600 ring-1 ring-slate-200 hover:text-ink lg:bg-transparent lg:ring-0 lg:hover:bg-slate-50' => ! $active,
                                ])>
                            <span class="truncate">{{ $name }}</span>
                            <span @class(['text-xs', 'text-white/80 lg:text-brand-600' => $active, 'text-slate-400' => ! $active])>{{ $count }}</span>
                        </button>
                    @endforeach
                    <a href="{{ route('executor.profile') }}" class="mt-1 hidden border-t border-slate-100 px-3 pt-3 pb-1 text-xs font-bold text-slate-500 hover:text-brand-600 lg:block">Изменить теги</a>
                </div>
            </aside>

            {{-- Заказы --}}
            <div>
                <div class="space-y-3" wire:loading.class="opacity-60" wire:target="tag, search">
                    @forelse ($this->orders as $order)
                        @php($myBid = $order->bids->first())
                        <article wire:key="order-{{ $order->id }}" data-order-card class="card group p-4 transition hover:border-slate-300 hover:shadow-sm sm:p-5">
                            <div class="flex items-start justify-between gap-4">
                                <button type="button" wire:click="open({{ $order->id }})" data-order="{{ $order->id }}" class="min-w-0 text-left">
                                    <h2 class="text-base font-bold group-hover:text-brand-600 sm:text-lg">{{ $order->title }}</h2>
                                </button>
                                <div class="shrink-0 text-right">
                                    <div class="text-lg font-extrabold whitespace-nowrap sm:text-xl">{{ rub($order->starting_price) }}</div>
                                    <div class="text-[11px] font-semibold text-slate-400">стартовая цена</div>
                                </div>
                            </div>

                            <p class="mt-2 line-clamp-2 text-sm leading-relaxed text-slate-600">{{ $order->description }}</p>

                            <div class="mt-3 flex flex-wrap gap-1.5">
                                @foreach ($order->categories as $orderTag)
                                    <span class="tag">{{ $orderTag->name }}</span>
                                @endforeach
                            </div>

                            <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-3">
                                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-500">
                                    <span class="inline-flex items-center gap-1"><x-heroicon-o-user class="size-3.5" /> {{ $order->customer->name }}</span>
                                    <span class="inline-flex items-center gap-1"><x-heroicon-o-clock class="size-3.5" /> {{ $order->created_at->diffForHumans() }}</span>
                                    <span class="inline-flex items-center gap-1"><x-heroicon-o-users class="size-3.5" /> {{ $order->bids_count }} {{ plural($order->bids_count, 'предложение', 'предложения', 'предложений') }}</span>
                                </div>
                                @if ($myBid)
                                    <button type="button" wire:click="open({{ $order->id }})" class="btn-secondary px-3 py-1.5 text-xs">
                                        <x-heroicon-m-check class="size-4 text-emerald-600" /> Ваша цена {{ rub($myBid->offer_price) }} · изменить
                                    </button>
                                @else
                                    <button type="button" wire:click="open({{ $order->id }})" class="btn-primary px-3 py-1.5 text-xs">Предложить цену</button>
                                @endif
                            </div>
                        </article>
                    @empty
                        <x-empty-state title="Подходящих заказов пока нет"
                                       text="Новые заказы из вашего города с вашими тегами появятся здесь. Добавьте специализации, чтобы видеть больше." >
                            <a href="{{ route('executor.profile') }}" class="btn-secondary">Изменить теги</a>
                        </x-empty-state>
                    @endforelse
                </div>

                <div class="mt-6">{{ $this->orders->links() }}</div>
            </div>
        </div>
    @endif

    {{-- Модалка: заказ и форма предложения --}}
    @if ($order = $this->openOrder)
        <div class="fixed inset-0 z-50 flex items-end justify-center bg-slate-900/50 sm:items-center sm:p-4"
             wire:key="modal-{{ $order->id }}" x-data x-on:keydown.escape.window="$wire.close()" role="dialog" aria-modal="true" aria-labelledby="modal-title">
            <div class="absolute inset-0" wire:click="close"></div>
            <div class="relative flex max-h-[94dvh] w-full max-w-2xl flex-col overflow-hidden rounded-t-2xl bg-white shadow-2xl sm:rounded-2xl"
                 x-data="{ price: @js($offerPrice), max: {{ $order->maxOfferPrice() }} }">
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-5 py-4 sm:px-6">
                    <div class="min-w-0">
                        <h2 id="modal-title" class="text-lg font-extrabold tracking-tight sm:text-xl">{{ $order->title }}</h2>
                        <div class="mt-1 text-xs text-slate-500">{{ $order->city->name }} · {{ $order->customer->name }} · {{ $order->bids_count }} {{ plural($order->bids_count, 'предложение', 'предложения', 'предложений') }}</div>
                    </div>
                    <button type="button" wire:click="close" class="-mr-1 rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-ink" aria-label="Закрыть">
                        <x-heroicon-o-x-mark class="size-6" />
                    </button>
                </div>

                <div class="overflow-y-auto">
                    <div class="space-y-4 px-5 py-4 sm:px-6">
                        <div class="whitespace-pre-line text-[15px] leading-relaxed text-slate-700">{{ $order->description }}</div>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach ($order->categories as $orderTag)
                                <span class="tag">{{ $orderTag->name }}</span>
                            @endforeach
                        </div>
                    </div>

                    <form wire:submit="submitBid" id="bid-form" class="space-y-4 border-t border-slate-100 bg-canvas px-5 py-5 sm:px-6">
                        <div class="flex flex-wrap items-baseline justify-between gap-2">
                            <h3 class="font-extrabold">{{ $order->bids->isNotEmpty() ? 'Ваше предложение' : 'Ваши условия' }}</h3>
                            <p class="text-xs text-slate-500">Стартовая {{ rub($order->starting_price) }} · можно до <span class="font-bold text-ink">{{ rub($order->maxOfferPrice()) }}</span></p>
                        </div>

                        <div class="grid grid-cols-2 gap-3 sm:gap-4">
                            <x-field label="Цена, ₽" for="offerPrice">
                                <input id="offerPrice" type="number" wire:model="offerPrice" x-on:input="price = $event.target.valueAsNumber" min="{{ config('ideajob.min_price') }}" max="{{ $order->maxOfferPrice() }}" step="1" inputmode="numeric" class="input no-spin"
                                       :class="price > max && 'border-brand-500 ring-4 ring-brand-100'">
                                <p x-show="price > max" x-cloak class="mt-1.5 text-sm font-medium text-brand-700">Не выше {{ rub($order->maxOfferPrice()) }}</p>
                            </x-field>
                            <x-field label="Срок, дней" for="durationDays">
                                <input id="durationDays" type="number" wire:model="durationDays" min="1" max="{{ config('ideajob.max_duration_days') }}" step="1" inputmode="numeric" class="input no-spin">
                            </x-field>
                        </div>

                        <x-field label="Как вы это сделаете" for="approach" hint="Опыт в похожих задачах, этапы, что нужно от заказчика.">
                            <textarea id="approach" wire:model="approach" rows="4" class="input"></textarea>
                        </x-field>
                    </form>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-slate-100 bg-white px-5 py-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] sm:px-6">
                    <button type="button" wire:click="close" class="btn-secondary">Отмена</button>
                    <button type="submit" form="bid-form" class="btn-primary" wire:loading.attr="disabled" :disabled="price > max">
                        {{ $order->bids->isNotEmpty() ? 'Сохранить' : 'Отправить предложение' }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
