<x-layout>
    <section class="border-b border-slate-200 bg-white">
        <div class="mx-auto grid max-w-6xl gap-10 px-4 py-14 md:grid-cols-[1.2fr_1fr] md:items-center md:py-20">
            <div>
                <span class="badge bg-brand-50 text-brand-700 ring-brand-200">
                    {{ $openOrdersCount }} {{ plural($openOrdersCount, 'заказ ждёт', 'заказа ждут', 'заказов ждут') }} исполнителей
                </span>
                <h1 class="mt-4 text-4xl font-extrabold leading-tight tracking-tight text-balance sm:text-[2.75rem]">
                    Назовите задачу и&nbsp;цену&nbsp;— <span class="text-brand-600">исполнители предложат свои условия</span>
                </h1>
                <p class="mt-5 max-w-xl text-lg text-slate-600">
                    Не нужно листать сотни услуг. Опишите, что нужно сделать, и выберите исполнителя по цене, сроку и рейтингу.
                </p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('register', ['role' => 'customer']) }}" class="btn-primary px-6 py-3 text-base">Разместить заказ</a>
                    <a href="{{ route('register', ['role' => 'executor']) }}" class="btn-secondary px-6 py-3 text-base">Стать исполнителем</a>
                </div>
            </div>

            <ol class="card divide-y divide-slate-100 p-2">
                @foreach ([
                    ['Заказчик публикует задачу', 'Название, описание, теги и стартовая цена.'],
                    ['Исполнители предлагают условия', 'Заказ видят только специалисты с подходящими тегами.'],
                    ['Заказчик выбирает лучшего', 'Сравнивает цену, срок, рейтинг и подход к задаче.'],
                ] as [$step, $text])
                    <li class="flex gap-4 p-4">
                        <span class="grid size-8 shrink-0 place-items-center rounded-full bg-brand-600 text-sm font-extrabold text-white">{{ $loop->iteration }}</span>
                        <div>
                            <div class="font-bold">{{ $step }}</div>
                            <div class="mt-0.5 text-sm text-slate-500">{{ $text }}</div>
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    <section class="mx-auto max-w-6xl px-4 py-14">
        <h2 class="text-2xl font-extrabold tracking-tight">Направления</h2>
        <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
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
