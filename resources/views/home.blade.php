<x-layout>
    <section class="border-b border-slate-200 bg-white">
        <div class="mx-auto grid max-w-6xl gap-10 px-4 py-12 md:grid-cols-[1.15fr_1fr] md:items-center md:py-16">
            <div>
                <h1 class="text-3xl leading-tight font-extrabold tracking-tight text-balance sm:text-[2.75rem]">
                    Назовите задачу и&nbsp;цену&nbsp;— <span class="text-brand-600">исполнители предложат свои условия</span>
                </h1>
                <p class="mt-4 max-w-xl text-base text-slate-600 sm:text-lg">
                    Без каталога услуг и переписки с десятком фрилансеров. Сравните цену, срок и рейтинг — и выберите одного.
                </p>
                <div class="mt-7 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ route('register', ['role' => 'customer']) }}" class="btn-primary px-6 py-3 text-base">Разместить заказ</a>
                    <a href="{{ route('register', ['role' => 'executor']) }}" class="btn-secondary px-6 py-3 text-base">Стать исполнителем</a>
                </div>
            </div>

            <ol class="space-y-4">
                @foreach ([
                    ['Опишите задачу', 'Название, описание, до трёх тегов и стартовая цена.', 'heroicon-o-pencil-square'],
                    ['Получите предложения', 'Заказ видят только специалисты с подходящими тегами.', 'heroicon-o-hand-raised'],
                    ['Выберите исполнителя', 'По цене, сроку, рейтингу и подходу к задаче — дальше общение в чате.', 'heroicon-o-check-badge'],
                ] as [$step, $text, $icon])
                    <li class="flex gap-4">
                        <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-600">
                            <x-dynamic-component :component="$icon" class="size-6" />
                        </span>
                        <div>
                            <div class="font-bold"><span class="text-slate-400">{{ $loop->iteration }}.</span> {{ $step }}</div>
                            <div class="mt-0.5 text-sm text-slate-500">{{ $text }}</div>
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    @if ($latestOrders->isNotEmpty())
        <section class="mx-auto max-w-6xl px-4 pt-12" aria-labelledby="latest-title">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 id="latest-title" class="text-2xl font-extrabold tracking-tight">Свежие заказы</h2>
                    <p class="mt-1 text-sm text-slate-500">Всего на аукционе: {{ $openOrdersCount }}</p>
                </div>
                <a href="{{ route('register', ['role' => 'executor']) }}" class="text-sm font-bold text-brand-600 hover:text-brand-700">Предложить свою цену →</a>
            </div>

            <div class="card mt-5 divide-y divide-slate-100">
                @foreach ($latestOrders as $order)
                    <div class="flex flex-col gap-2 p-4 sm:flex-row sm:items-center sm:gap-6 sm:px-5">
                        <div class="min-w-0 flex-1">
                            <div class="font-bold">{{ $order->title }}</div>
                            <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                                @foreach ($order->categories as $tag)
                                    <span class="tag">{{ $tag->name }}</span>
                                @endforeach
                                <span class="text-xs text-slate-400">· {{ $order->created_at->diffForHumans() }}</span>
                            </div>
                        </div>
                        <div class="flex items-baseline justify-between gap-4 sm:block sm:text-right">
                            <div class="text-lg font-extrabold whitespace-nowrap">{{ rub($order->starting_price) }}</div>
                            <div class="text-xs text-slate-500">{{ $order->bids_count }} {{ plural($order->bids_count, 'предложение', 'предложения', 'предложений') }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <section class="mx-auto max-w-6xl px-4 py-12" aria-labelledby="categories-title">
        <h2 id="categories-title" class="text-2xl font-extrabold tracking-tight">Направления</h2>
        <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($tree as $root)
                <div class="card p-5">
                    <div class="font-bold">{{ $root->name }}</div>
                    <div class="mt-3 flex flex-wrap gap-1.5">
                        @foreach ($root->children as $tag)
                            <span class="tag">{{ $tag->name }}</span>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </section>
</x-layout>
